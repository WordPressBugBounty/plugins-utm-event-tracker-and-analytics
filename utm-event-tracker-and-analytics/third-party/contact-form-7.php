<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!defined('WPCF7_VERSION')) {
	return;
}

class Contact_Form_7 {

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action('wpcf7_init', [$this, 'add_tags']);
		add_action('wpcf7_mail_sent', [$this, 'wpcf7_submit']);
		add_action('wpcf7_admin_init', array($this, 'add_utm_tag_generator'), 52);
		add_filter('utm_event_tracker/google_analytics/plugins_events', array($this, 'add_ga4_event'));
	}

	/**
	 * Add tag
	 * 
	 * @since 1.0.0
	 */
	public function add_tags() {
		wpcf7_add_form_tag(array('utm_event_tracker', 'utm_event_tracker*'), array($this, 'add_tag'), array('name-attr' => true));
	}

	/**
	 * Add tag
	 * 
	 * @since 1.0.0
	 */
	public function add_tag($tag) {
		$tag = new \WPCF7_FormTag($tag);
		if (empty($tag->name)) {
			return '';
		}

		$atts = array();
		$atts['id'] = $tag->get_id_option();
		$atts['name'] = $tag->name;

		$parameters = $tag->get_option('param');
		if (false === $parameters || count($parameters) == 0) {
			return;
		}

		$param = sanitize_text_field(reset($parameters));

		if (Session::is_available()) {
			$session = Session::get_current_session();

			$value = $session->get($param);
			if ('landing_page' == $param) {
				$value = $session->get_landing_page_url();
			}

			$atts['value'] = $value;
		}

		return !empty($atts['value']) ? sprintf('<input type="hidden" %s>', wpcf7_format_atts($atts)) : '';
	}

	/**
	 * Add tag generator item
	 * 
	 * @since 1.0.0
	 */
	public function add_utm_tag_generator() {
		$tag_generator = \WPCF7_TagGenerator::get_instance();
		$tag_generator->add('utm_event_tracker', __('UTM Event Tracker', 'utm-event-tracker-and-analytics'), array($this, 'utm_tag_generator'));
	}

	/**
	 * Add event after submitting the contact form 7
	 * 
	 * @since 1.0.0
	 */
	public function wpcf7_submit() {
		$submission = \WPCF7_Submission::get_instance();
		if (Session::is_available()) {
			utm_event_tracker_add_event('contact_form_7_submit', array(
				'title' => esc_html__('Form Submit - Contact Form 7', 'utm-event-tracker-and-analytics'),
				'meta_data' => array(
					'form_id' => $submission->get_contact_form()->id()
				)
			));

			$posted_data = $submission->get_posted_data();
			Webhook::get_instance()->send($posted_data);
		}

		if (Google_Analytics::is_send_event_active()) {
			$compare_data['form_id'] = $submission->get_contact_form()->id();

			$posted_data = $submission->get_posted_data();
			foreach ($posted_data as $field_key => $field_value) {
				$compare_data['field:' . $field_key] = $field_value;
			}

			$events = Google_Analytics::get_instance()->get_events('contact_form_7_submit');
			foreach ($events as $event) {
				$event = new Google_Analytics_Event($event);
				$event->extra_data = $submission;

				$conditions = array_filter($event->get_conditions(), fn($item) => !empty($item['type']));
				$conditions_result = array_map(function ($condition) use ($compare_data) {
					if ('form_id' == $condition['type']) {
						return (isset($compare_data['form_id']) && $compare_data['form_id'] == $condition['value']);
					}

					if ('form_field' == $condition['type'] && !empty($condition['field_name'])) {
						$field_key = 'field:' . $condition['field_name'];
						return isset($compare_data[$field_key]) && $compare_data[$field_key] == $condition['value'];
					}

					return false;
				}, $conditions);

				if ($event->condition_matched($conditions_result)) {
					$event->send_event();
				}
			}
		}
	}

	/**
	 * Tag generator popup for UTM event tracker
	 * 
	 * @since 1.0.0
	 */
	public function utm_tag_generator($contact_form, $args = '') {
		$args = wp_parse_args($args, array());
		$parameters = Utils::get_all_parameters(); ?>
		<div class="control-box">

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr($args['content'] . '-name'); ?>"><?php echo esc_html(__('Name', 'utm-event-tracker-and-analytics')); ?></label></th>
						<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" /></td>
					</tr>

					<tr>
						<th scope="row"><label for="<?php echo esc_attr($args['content'] . '-param'); ?>"><?php echo esc_html(__('UTM Parameter', 'utm-event-tracker-and-analytics')); ?></label></th>
						<td>
							<select id="<?php echo esc_attr($args['content'] . '-param'); ?>">
								<?php
								foreach ($parameters as $key => $label) {
									printf('<option value="%s">%s</option>', esc_attr($key), esc_html($label));
								}
								?>
							</select>
							<input id="utm-event-tracker-param-holder" type="hidden" name="param" class="option" value="utm_campaign">
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="insert-box">
			<input type="text" name="utm_event_tracker" class="tag code" readonly="readonly" onfocus="this.select()" />
			<div class="submitbox">
				<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'utm-event-tracker-and-analytics')); ?>" />
			</div>
			<p class="description mail-tag">
				<label for="<?php echo esc_attr($args['content'] . '-mailtag'); ?>">
					<?php echo sprintf(
						/* translators: %s for tag */
						esc_html(__('To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'utm-event-tracker-and-analytics')),
						'<strong><span class="mail-tag"></span></strong>'
					);  ?>
					<input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($args['content'] . '-mailtag'); ?>" />
				</label>
			</p>
		</div>

		<script>
			(function($) {
				$('#tag-generator-panel-utm_event_tracker-param').on('change', function() {
					const value = $(this).val();
					$('#utm-event-tracker-param-holder').val(value)
				}).trigger('change')

			})(jQuery)
		</script>
	<?php
	}

	/**
	 * Add GA4 event
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public function add_ga4_event($events) {
		$events['contact_form_7_submit'] = array(
			'event_group' => 'form_submit',
			'event_type' => 'form_submission',
			'condition_template' => 'contact_form_7',
			'title' => esc_html__('Contact Form 7', 'utm-event-tracker-and-analytics'),
			'single_title' => esc_html__('Contact Form 7 Submit', 'utm-event-tracker-and-analytics'),
		);

		return $events;
	}
}

new Contact_Form_7();


/**
 * Add event condition template
 * 
 * @since 1.1.3
 * @return void
 */
function contact_form7_ga4_event_condition_template() { ?>
	<table class="table-event-item-condition" v-if="current_condition_template == 'contact_form_7'">
		<tr>
			<th><?php esc_html_e('Type', 'utm-event-tracker-and-analytics'); ?></th>
			<td>
				<select v-model="condition.type">
					<option value=""><?php esc_html_e('Choose a condition', 'utm-event-tracker-and-analytics'); ?></option>
					<option value="form_id"><?php esc_html_e('Form ID', 'utm-event-tracker-and-analytics'); ?></option>
					<option value="form_field"><?php esc_html_e('Form Field', 'utm-event-tracker-and-analytics'); ?></option>
				</select>
			</td>
		</tr>

		<tr v-if="condition.type == 'form_field'">
			<th><?php esc_html_e('Field', 'utm-event-tracker-and-analytics'); ?></th>
			<td>
				<input type="text" v-model="condition.field_name" placeholder="<?php esc_html_e('enter field name', 'utm-event-tracker-and-analytics'); ?>">
			</td>
		</tr>

		<tr v-if="condition.type.length">
			<th><?php esc_html_e('Value', 'utm-event-tracker-and-analytics'); ?></th>
			<td>
				<input type="text" v-model="condition.value" placeholder="<?php esc_html_e('enter value', 'utm-event-tracker-and-analytics'); ?>">
			</td>
		</tr>
	</table>
<?php
}
add_action('utm_event_tracker/google_analytics/event_condition_template', '\UTM_Event_Tracker\contact_form7_ga4_event_condition_template');
