<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('FrmHooksController')) {
	return;
}

/**
 * Handle form submit
 * 
 * @since 1.0.0
 */
function formidable_add_submit_event($params, $errors, $form) {
	$fields = \FrmFieldsHelper::get_form_fields($form->id, $errors);

	if (Session::is_available()) {
		utm_event_tracker_add_event('formidable_form_submit', array(
			'title' => esc_html__('Form Submit - Formidable', 'utm-event-tracker-and-analytics'),
			'meta_data' => array(
				'form_id' => $form->id,
				'entry_id' => $params['id'],
			)
		));

		$form_data = array();
		foreach ($fields as $field) {
			if (isset($_POST['item_meta'][$field->id])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if (is_array($_POST['item_meta'][$field->id])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$form_data[$field->name] = array_map('sanitize_text_field', wp_unslash($_POST['item_meta'][$field->id])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				} else {
					$form_data[$field->name] = sanitize_text_field(wp_unslash($_POST['item_meta'][$field->id])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}
			}
		}

		Webhook::get_instance()->send($form_data);
	}

	if (Google_Analytics::is_send_event_active()) {
		$compare_data['form_id'] = $form->id;
		foreach ($fields as $field_data) {
			if (!isset($_POST['item_meta'][$field_data->id])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			if (is_array($_POST['item_meta'][$field_data->id])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$field_value = array_map('sanitize_text_field', wp_unslash($_POST['item_meta'][$field_data->id])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				$field_value = sanitize_text_field(wp_unslash($_POST['item_meta'][$field_data->id])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			$compare_data['field:' . $field_data->field_key] = $field_value;
		}

		$events = Google_Analytics::get_instance()->get_events('formidable_form');
		foreach ($events as $event) {
			$event = new Google_Analytics_Event($event);
			$event->extra_data = $form;

			$conditions = array_filter($event->get_conditions(), fn($item) => !empty($item['type']));
			$conditions_result = array_map(function ($condition) use ($compare_data) {
				if ('form_id' == $condition['type']) {
					return (isset($compare_data['form_id']) && $compare_data['form_id'] == $condition['value']);
				}

				if ('field_key' == $condition['type'] && !empty($condition['field_key'])) {
					$field_key = 'field:' . $condition['field_key'];
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
add_action('frm_process_entry', '\UTM_Event_Tracker\formidable_add_submit_event', 12, 3);

/**
 * Replace smart value
 * 
 * @since 1.1.0
 */
function formidable_replace_smart_value($value) {
	foreach (Utils::get_parameters_data() as $utm_key => $utm_value) {
		$value = str_replace('{utm_event_tracker:' . $utm_key . '}', $utm_value, $value);
	}

	return $value;
}
add_filter('frm_get_default_value', '\UTM_Event_Tracker\formidable_replace_smart_value');

/**
 * Add google analytics events
 * 
 * @since 1.1.3
 * @return array
 */
function formidable_add_ga4_event($events) {
	$events['formidable_form'] = array(
		'event_group' => 'form_submit',
		'event_type' => 'form_submission',
		'condition_template' => 'formidable_form_submit',
		'title' => esc_html__('Formidable', 'utm-event-tracker-and-analytics'),
		'single_title' => esc_html__('Formidable Form Submit', 'utm-event-tracker-and-analytics'),
	);

	return $events;
}
add_filter('utm_event_tracker/google_analytics/plugins_events', '\UTM_Event_Tracker\formidable_add_ga4_event');

/**
 * Add event condition template
 * 
 * @since 1.1.3
 * @return void
 */
function formidable_ga4_event_condition_template() { ?>
	<table class="table-event-item-condition" v-if="current_condition_template == 'formidable_form_submit'">
		<tr>
			<th><?php esc_html_e('Type', 'utm-event-tracker-and-analytics'); ?></th>
			<td>
				<select v-model="condition.type">
					<option value=""><?php esc_html_e('Choose a type', 'utm-event-tracker-and-analytics'); ?></option>
					<option value="form_id"><?php esc_html_e('Form ID', 'utm-event-tracker-and-analytics'); ?></option>
					<option value="field_key"><?php esc_html_e('Field key', 'utm-event-tracker-and-analytics'); ?></option>
				</select>
			</td>
		</tr>

		<tr v-if="condition.type == 'field_key'">
			<th>
				<?php esc_html_e('Field key', 'utm-event-tracker-and-analytics'); ?>
			</th>
			<td>
				<input type="text" v-model="condition.field_key" placeholder="<?php esc_html_e('Enter field key', 'utm-event-tracker-and-analytics'); ?>">
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
add_action('utm_event_tracker/google_analytics/event_condition_template', '\UTM_Event_Tracker\formidable_ga4_event_condition_template');
