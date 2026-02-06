<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Forminator')) {
	return;
}

/**
 * Replace value
 * 
 * @since 1.1.3
 * @return string
 */
function forminator_replace_value($html) {
	foreach (Utils::get_parameters_data() as $utm_key => $utm_value) {
		$search_string = sprintf('value="{utm_event_tracker:%s}"', esc_attr($utm_key));
		$replace_value = sprintf('value="%s"', esc_attr($utm_value));
		$html = str_replace($search_string, $replace_value, $html);
	}

	return $html;
}
add_filter('forminator_field_markup', '\UTM_Event_Tracker\forminator_replace_value');

/**
 * After save entry
 * 
 * @since 1.1.3
 */
function forminator_after_save_entry($form_id, $response) {
	if (!isset($response['success']) || true !== $response['success']) {
		return;
	}

	if (Session::is_available()) {
		$form_entry_model = \Forminator_Form_Entry_Model::get_latest_entry_by_form_id($form_id);

		utm_event_tracker_add_event('forminator_form_submit', array(
			'title' => esc_html__('Form Submit - Forminator', 'utm-event-tracker-and-analytics'),
			'meta_data' => array(
				'form_id' => $response['form_id'],
				'entry_id' => $form_entry_model->entry_id
			)
		));

		$form_data = array();
		foreach ($form_entry_model->meta_data as $key => $meta_value) {
			$form_data[$key] = $meta_value['value'];
		}

		unset($form_data['_forminator_user_ip']);

		Webhook::get_instance()->send($form_data);
	}

	if (Google_Analytics::is_send_event_active()) {
		$compare_data['form_id'] = $form_id;

		$events = Google_Analytics::get_instance()->get_events('forminator_form_submit');
		foreach ($events as $event) {
			$event = new Google_Analytics_Event($event);
			$event->extra_data = $form_id;

			if ($event->check_global_form_template_conditions($compare_data)) {
				$event->send_event();
			}
		}
	}
}
add_action('forminator_form_after_save_entry', '\UTM_Event_Tracker\forminator_after_save_entry', 12, 3);

/**
 * Add google analytics events
 * 
 * @since 1.1.3
 * @return array
 */
function forminator_add_ga4_event($events) {
	$events['forminator_form_submit'] = array(
		'event_group' => 'form_submit',
		'event_type' => 'form_submission',
		'condition_template' => 'global_form_template',
		'condition_type_default_value' => 'form_id',
		'title' => esc_html__('Forminator', 'utm-event-tracker-and-analytics'),
		'single_title' => esc_html__('Forminator Form Submit', 'utm-event-tracker-and-analytics'),
	);

	return $events;
}
add_filter('utm_event_tracker/google_analytics/plugins_events', '\UTM_Event_Tracker\forminator_add_ga4_event');
