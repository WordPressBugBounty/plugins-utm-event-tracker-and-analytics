<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!defined('ELEMENTOR_VERSION')) {
	return;
}

/**
 * Replace placeholder
 * 
 * @since 1.1.1
 * @return array
 */
function elementor_replace_placehoder($item) {
	if (empty($item['field_value'])) {
		return $item;
	}

	foreach (Utils::get_parameters_data() as $utm_key => $utm_value) {
		$item['field_value'] = str_replace('{utm_event_tracker:' . $utm_key . '}', $utm_value, $item['field_value']);
	}

	return $item;
}
add_filter('elementor_pro/forms/render/item', '\UTM_Event_Tracker\elementor_replace_placehoder');

/**
 * Handle form of elementor
 * 
 * @since 1.1.2
 * @return void
 */
function elementor_form_handle_submit($record) {
	if (Session::is_available()) {
		utm_event_tracker_add_event('elementor_form_submit', array(
			'title' => esc_html__('Form Submit - Elementor', 'utm-event-tracker-and-analytics'),
			'meta_data' => array(
				'form_name' => $record->get_form_settings('form_name'),
				'form_data' => $record->get_formatted_data(),
			)
		));

		Webhook::get_instance()->send($record->get_formatted_data());
	}

	if (Google_Analytics::is_send_event_active()) {
		$compare_data['form_name'] = $record->get_form_settings('form_name');
		foreach ($record->get('fields') as $field_id => $field_data) {
			$compare_data['field:' . $field_id] = $field_data['value'];
		}

		$events = Google_Analytics::get_instance()->get_events('elementor_form');

		foreach ($events as $event) {
			$event = new Google_Analytics_Event($event);

			$conditions = array_filter($event->get_conditions(), fn($item) => !empty($item['field_id']));
			$conditions_result = array_map(function ($condition) use ($compare_data) {
				if (empty($condition['type'])) {
					return false;
				}

				if ('form_name' == $condition['type']) {
					return (isset($compare_data['form_name']) && $compare_data['form_name'] == $condition['value']);
				}

				if ('field_id' == $condition['type']) {
					$field_key = 'field:' . $condition['field_id'];
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
add_action('elementor_pro/forms/new_record', '\UTM_Event_Tracker\elementor_form_handle_submit');

/**
 * Add google analytics events
 * 
 * @since 1.1.3
 * @return array
 */
function elementor_add_ga4_event($events) {
	$events['elementor_form'] = array(
		'event_group' => 'form_submit',
		'event_type' => 'form_submission',
		'condition_template' => 'elementor_form_submit',
		'title' => esc_html__('Elementor', 'utm-event-tracker-and-analytics'),
		'single_title' => esc_html__('Elementor Form Submit', 'utm-event-tracker-and-analytics'),
	);

	return $events;
}
add_filter('utm_event_tracker/google_analytics/plugins_events', '\UTM_Event_Tracker\elementor_add_ga4_event');

/**
 * Add event condition template for elementor for submission
 * 
 * @since 1.1.3
 * @return void
 */
function elementor_ga4_event_condition_template() { ?>
	<table class="table-event-item-condition" v-if="current_condition_template == 'elementor_form_submit'">
		<tr>
			<th><?php esc_html_e('Type', 'utm-event-tracker-and-analytics'); ?></th>
			<td>
				<select v-model="condition.type">
					<option value=""><?php esc_html_e('Choose a type', 'utm-event-tracker-and-analytics'); ?></option>
					<option value="form_name"><?php esc_html_e('Form Name', 'utm-event-tracker-and-analytics'); ?></option>
					<option value="field_id"><?php esc_html_e('Field ID', 'utm-event-tracker-and-analytics'); ?></option>
				</select>

			</td>
		</tr>

		<tr v-if="condition.type == 'field_id'">
			<th>
				<?php esc_html_e('Field ID', 'utm-event-tracker-and-analytics'); ?>
			</th>
			<td>
				<input type="text" v-model="condition.field_id" placeholder="<?php esc_html_e('Enter elementor field ID', 'utm-event-tracker-and-analytics'); ?>">
			</td>
		</tr>

		<tr v-if="condition.type">
			<th>
				<?php esc_html_e('Value', 'utm-event-tracker-and-analytics'); ?>
			</th>
			<td>
				<input type="text" v-model="condition.value" placeholder="<?php esc_html_e('Enter value', 'utm-event-tracker-and-analytics'); ?>">
			</td>
		</tr>
	</table>
<?php
}
add_action('utm_event_tracker/google_analytics/event_condition_template', '\UTM_Event_Tracker\elementor_ga4_event_condition_template');
