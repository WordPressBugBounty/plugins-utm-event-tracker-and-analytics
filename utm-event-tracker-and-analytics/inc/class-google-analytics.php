<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Google analytics class
 */
class Google_Analytics {

	/**
	 * The single instance of the class.
	 *
	 * @var Google_Analytics
	 * @since 1.1.3
	 */
	protected static $_instance = null;

	/**
	 * Admin Instance.
	 *
	 * @since 1.1.3
	 * @return Google_Analytics - Main instance.
	 */
	public static function get_instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get google analytics events groups
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public static function get_event_groups() {
		return apply_filters('utm_event_tracker/google_analytics/event_groups', array(
			'form_submit' => esc_html__('Form Submission', 'utm-event-tracker-and-analytics'),
			'woocommerce' => esc_html__('WooCommerce', 'utm-event-tracker-and-analytics'),
			'easy_digital_downloads' => esc_html__('Easy Digital Downloads', 'utm-event-tracker-and-analytics'),
			'others' => esc_html__('Others', 'utm-event-tracker-and-analytics'),
		));
	}

	/**
	 * Get plugins events for google analytics
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public static function get_plugins_events($custom_events = true) {
		$events = apply_filters('utm_event_tracker/google_analytics/plugins_events', array());

		$custom_events = Settings::get_instance()->get_custom_events();
		foreach ($custom_events as $custom_event_item) {
			$event_key = 'custom_event_' . $custom_event_item['event_type'];
			$events[$event_key] = $custom_event_item;
		}

		$event_item_params = apply_filters('utm_event_tracker/google_analytics/plugin_event_params', array(
			'title' => '',
			'priority' => 10,
			'event_type' => '',
			'event_group' => '',
			'disable_settings' => false,
			'settings_disability' => array(),
			'condition_template' => '',
		));

		return array_map(function ($item) use ($event_item_params) {
			return wp_parse_args($item, $event_item_params);
		}, $events);
	}

	/**
	 * Get google analytics event item default params
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public static function get_event_item_params() {
		return apply_filters('utm_event_tracker/google_analytics/event_item_params', array(
			'event_name' => '',
			'custom_event_key' => '',
			'disabled' => true,
			'conditions' => array(),
			'match_condition' => 'any',
			'custom_params' => array(),
		));
	}

	/**
	 * Get events of a group
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public static function get_group_events($group_key) {
		$group_events = [];
		foreach (self::get_plugins_events() as $key => $event) {
			if (empty($key)) {
				continue;
			}

			if (isset($event['event_group']) && $group_key == $event['event_group']) {
				$group_events[$key] = $event;
			}

			if (empty($group_key) && empty($event['event_group'])) {
				$group_events[$key] = $event;
			}
		}

		uasort($group_events, function ($a, $b) {
			return $a['priority'] > $b['priority'] ? 1 : -1;
		});

		return $group_events;
	}

	/** 
	 * Check if send event activated
	 * 
	 * @since 1.1.3
	 * @return boolean
	 */
	public static function is_send_event_active() {
		return Settings::get_instance()->get('send_google_analytics_event', false);
	}

	/** 
	 * Constructor 
	 * 
	 * @since 1.1.3
	 */
	public function __construct() {
		add_action('admin_footer', array($this, 'add_events_templates'));
		add_action('admin_footer', array($this, 'add_settings_templates'));
		add_action('utm_event_tracker/google_analytics/events_settings_rows', array($this, 'send_event_without_session_options'), 5);
		add_action('utm_event_tracker/google_analytics/event_condition_template', array($this, 'global_form_submit_template'));
	}

	/** 
	 * Get client ID
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public static function get_client_id() {
		if (!self::is_send_event_active()) {
			return time() . wp_rand(1000, 9999);
		}

		if (isset($_COOKIE['_ga'])) {
			$ga_cookie_parts = explode('.', sanitize_text_field(wp_unslash($_COOKIE['_ga'])));
			if (count($ga_cookie_parts) >= 4) {
				return $ga_cookie_parts[2] . '.' . $ga_cookie_parts[3];
			}
		}

		$transient_key = Utils::get_client_key('google_analytics_client_id');
		$client_id = get_transient($transient_key);
		if (!empty($client_id)) {
			return $client_id;
		}

		$client_id = time() . wp_rand(1000, 9999);
		set_transient($transient_key, $client_id, 365 * DAY_IN_SECONDS);

		return $client_id;
	}

	/** 
	 * Get plugin event by event name
	 * 
	 * @since 1.1.3
	 * @return false|array
	 */
	public function get_plugins_event($event_name) {
		if (!isset(self::get_plugins_events()[$event_name])) {
			return false;
		}

		return self::get_plugins_events()[$event_name];
	}

	/** 
	 * Check if has event
	 * 
	 * @since 1.1.3
	 * @return boolean
	 */
	public function has_event($event_name) {
		if (!isset(self::get_plugins_events()[$event_name])) {
			return false;
		}

		return in_array($event_name, wp_list_pluck(Settings::get_instance()->get_google_analytics_events(), 'event_name'));
	}

	/** 
	 * Get event by key
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public function get_events($event_name) {
		if (!$this->has_event($event_name)) {
			return array();
		}

		$events = array_filter(Settings::get_instance()->get_google_analytics_events(), function ($item) use ($event_name) {
			return $event_name == $item['event_name'];
		});

		return $events;
	}

	/** 
	 * Add events templates
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function add_events_templates() { ?>
		<template id="utm-event-tracker-google-analytics-events">
			<table class="table-utm-event-tracker-repeater">
				<thead>
					<tr>
						<th class="column-event"><?php esc_html_e('Event', 'utm-event-tracker-and-analytics'); ?></th>
						<th class="column-min"></th>
						<th class="column-min"><?php esc_html_e('Disable', 'utm-event-tracker-and-analytics'); ?></th>
						<th class="column-min"></th>
					</tr>
				</thead>

				<tbody>
					<tr v-for="(event, event_index) in events" :key="'event_item_' + event_index">
						<td>
							<select v-model="event.event_name" required>
								<option value=""><?php esc_html_e('Choose an event', 'utm-event-tracker-and-analytics'); ?></option>
								<?php
								$options_html = '';
								foreach (self::get_group_events('') as $event_key => $event_data) {
									$options_html .= sprintf('<option value="%s">%s</option>', esc_attr($event_key), esc_html($event_data['title']));
								}

								$options_group_html = '';
								foreach (self::get_event_groups() as $group_key => $group_label) {
									$group_events = self::get_group_events($group_key);
									if (count($group_events) == 0) {
										continue;
									}

									if (count($group_events) == 1) {
										foreach ($group_events as $key => $event) {
											$option_label = !empty($event['single_title']) ? $event['single_title'] : $event['title'];
											$options_html .= '<option value="' . esc_attr($key) . '">' . esc_html($option_label) . ' </option>';
										}
									} else {
										$options_group_html .= '<optgroup label="' . esc_attr($group_label) . '">';
										foreach ($group_events as $key => $event) {
											$options_group_html .= '<option value="' . esc_attr($key) . '">' . esc_html($event['title']) . ' </option>';
										}
										$options_group_html .= '</optgroup>';
									}
								} ?>

								<?php echo wp_kses($options_html . $options_group_html, array(
									'option' => array('value' => true),
									'optgroup' => array('label' => true)
								)); ?>

								<optgroup v-if="custom_events.length > 0" label="<?php esc_html_e('Custom Events', 'utm-event-tracker-and-analytics'); ?>">
									<option v-for="(event_item, event_item_key) in custom_events" :key="event_item_key" :value="'custom_event_' + event_item.event_type">{{event_item.title}}</option>
								</optgroup>
							</select>
						</td>

						<td>
							<a :class="{'btn-event-settings dashicons dashicons-admin-generic': true, 'disabled': !has_settings_ability(event_index)}" @click.prevent="open_settings(event_index)" href="#"></a>
						</td>

						<td>
							<input v-model="event.disabled" type="checkbox">
						</td>

						<td>
							<a class="dashicons dashicons-trash btn-remove-ga4-event" @click.prevent="delete_event(event_index)" href="#"></a>
						</td>
					</tr>

					<tr class="no-event-row" v-if="events.length == 0">
						<td colspan="4">
							<button style="font-size: 15px;" class="button button-bordered" @click.prevent="add_event()">
								<?php esc_html_e('Add an event', 'utm-event-tracker-and-analytics'); ?>
							</button>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="field-note field-note-red">
				<?php printf(
					/* translators: %1$s: Link open, %2$s: Link close */
					esc_html__('%1$sHere%2$s, you will find instructions on how to view all fired events to check the result.', 'utm-event-tracker-and-analytics'),
					'<a target="_blank" href="https://support.google.com/analytics/answer/9271392">',
					'</a>'
				); ?>
			</p>

			<div style="margin-top: 10px;" v-if="events.length > 0">
				<button class="button button-primary" @click.prevent="add_event()">
					<?php esc_html_e('Add Event', 'utm-event-tracker-and-analytics'); ?>
					<span class="dashicons dashicons-lock" v-if="events.length >= 1 && is_free"></span>
				</button>
			</div>

			<event-settings-modal v-if="item_no !== null" :event-no="item_no"></event-settings-modal>
		</template>
	<?php
	}

	/** 
	 * Add settings template
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function add_settings_templates() { ?>
		<template id="utm-event-tracker-google-analytics-event-settings">
			<div id="utm-event-tracker-event-items-modal" class="utm-event-tracker-modal">
				<div class="utm-modal-container">
					<a @click.prevent="close_settings()" class="btn-close-modal dashicons dashicons-no-alt" href="#"></a>

					<div class="utm-modal-body">
						<table class="form-table table-event-item-settings">
							<tr v-if="has_ability('custom_event_key')">
								<th>
									<label for="event-key"><?php esc_html_e('Event Key', 'utm-event-tracker-and-analytics'); ?></label>
									<div class="utm-event-tracker-tooltip" style="top: 2px;">
										<div>
											<?php
											printf(
												/* translators: %1$s link open, %2$s: link close */
												esc_html__('%1$sHere%2$s, you can find all the recommended Google Analytics events.', 'utm-event-tracker-and-analytics'),
												'<a target="_blank" href="https://support.google.com/analytics/answer/9267735">',
												'</a>',
											); ?>
										</div>
									</div>

									<p class="field-note"><?php esc_html_e('Don\'t use spaces or hyphens. Here is an example "my_event_key"', 'utm-event-tracker-and-analytics'); ?></p>
								</th>
								<td>
									<input class="full-width" type="text" id="event-key" v-model="custom_event_key">

									<p class="field-note">
										<template v-if="current_event_type"><?php esc_html_e('The default event key is "{{current_event_type}}."', 'utm-event-tracker-and-analytics'); ?></template>
										<?php esc_html_e('Enter a custom event key to override the default one.', 'utm-event-tracker-and-analytics'); ?>
									</p>
								</td>
							</tr>

							<tr v-if="has_ability('conditions') && current_condition_template">
								<th class="vtop">
									<?php esc_html_e('Conditions', 'utm-event-tracker-and-analytics'); ?>
									<p class="field-note"><?php esc_html_e('Send this event if it matches any or all of the conditions.', 'utm-event-tracker-and-analytics'); ?></p>
								</th>
								<td>
									<ul class="utm-event-tracker-repeater-list" v-if="get_current_conditions.length">
										<template v-for="(condition, index) in conditions" :key="'event_condition_item' + index">
											<li v-if="current_condition_template == condition?.condition_template">
												<?php do_action('utm_event_tracker/google_analytics/event_condition_template') ?>
												<div class="tools">
													<a @click.prevent="remove_condition(index)" href="#" class="dashicons dashicons-remove"></a>
													<a @click.prevent="add_condition(index)" href="#" class="dashicons dashicons-insert"></a>
												</div>
											</li>
										</template>
									</ul>

									<div class="condition-match-relation" v-if="get_current_conditions.length > 1">
										<label>
											<input type="radio" value="any" v-model="match_condition">
											<?php esc_html_e('Match any', 'utm-event-tracker-and-analytics'); ?>
										</label>

										<label>
											<input type="radio" value="all" v-model="match_condition">
											<?php esc_html_e('Match all', 'utm-event-tracker-and-analytics'); ?>
										</label>
									</div>

									<a v-if="get_current_conditions.length == 0" class="button" href="#" @click.prevent="add_condition(0)"><?php esc_html_e('Add condition', 'utm-event-tracker-and-analytics'); ?></a>
								</td>
							</tr>

							<tr>
								<th>
									<?php esc_html_e('Custom Parameters', 'utm-event-tracker-and-analytics'); ?>
									<div class="utm-event-tracker-tooltip">
										<div><?php esc_html_e('All custom parameters will be transmitted to the Google Analytics 4 event.', 'utm-event-tracker-and-analytics'); ?></div>
									</div>
									<p class="field-note"><?php esc_html_e("Don't use spaces or hyphens in the key field.", 'utm-event-tracker-and-analytics'); ?></p>
								</th>
								<td>
									<ul class="utm-event-tracker-repeater-list list-inline" v-if="custom_params.length">
										<li v-for="(param, index) in custom_params" :key="'event_custom_param_' + index">
											<input type="text" v-model="param.key" placeholder="<?php esc_html_e('key', 'utm-event-tracker-and-analytics'); ?>">
											<input type="text" v-model="param.value" placeholder="<?php esc_html_e('value', 'utm-event-tracker-and-analytics'); ?>">

											<div class="tools">
												<a @click.prevent="add_custom_param(index)" href="#" class="dashicons dashicons-insert"></a>
												<a @click.prevent="remove_custom_param(index)" href="#" class="dashicons dashicons-remove"></a>
											</div>
										</li>
									</ul>

									<a v-if="custom_params.length == 0" class="button" href="#" @click.prevent="add_custom_param(0)"><?php esc_html_e('Add custom parameter', 'utm-event-tracker-and-analytics'); ?></a>
								</td>
							</tr>
						</table>
					</div>

					<div class="utm-modal-footer">
						<button class="button button-primary" @click.prevent="close_settings(true)"><?php esc_html_e('Save & Close', 'utm-event-tracker-and-analytics') ?></button>
					</div>
				</div>
			</div>
		</template>
	<?php
	}

	/** 
	 * Add options for events
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function send_event_without_session_options() { ?>
		<tr v-if="send_google_analytics_event">
			<th>
				<label for="send_event_without_session"><?php esc_html_e('Event Session', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('By default, an event cannot be submitted without a session.', 'utm-event-tracker-and-analytics') ?></p>
			</th>
			<td>
				<label>
					<input id="send_event_without_session" type="checkbox" disabled>
					<?php esc_html_e('Send events without session', 'utm-event-tracker-and-analytics'); ?>
				</label>
				<?php Utils::get_field_note(esc_html__('Track and send events to Google Analytics even if the visitor arrives without UTM parameters in the URL or a session.', 'utm-event-tracker-and-analytics'), '', 'ga4+events', 'event+without+session'); ?>
			</td>
		</tr>
	<?php
	}

	/** 
	 * Add condition template for major forms
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function global_form_submit_template() { ?>
		<table class="table-event-item-condition" v-if="current_condition_template == 'global_form_template'">
			<tr>
				<th>
					<?php esc_html_e('Form ID', 'utm-event-tracker-and-analytics'); ?>
				</th>
				<td>
					<input type="number" v-model="condition.value" placeholder="<?php esc_html_e('Enter form ID', 'utm-event-tracker-and-analytics'); ?>">
				</td>
			</tr>
		</table>
<?php
	}
}

Google_Analytics::get_instance();
