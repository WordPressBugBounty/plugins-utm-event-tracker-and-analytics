<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('wpforms')) {
	return;
}

/**
 * Add custom merge tags for gravity form
 * 
 * @since 1.0.0
 * @return array
 */
function wpforms_add_smart_tags($tags) {
	$parameters = Utils::get_all_parameters();
	foreach ($parameters as $key => $label) {
		$tags['utm_event_tracker_' . $key] = esc_html__('UTM Event Tracker', 'utm-event-tracker-and-analytics') . ' - ' . esc_html($label);
	}

	return $tags;
}
add_filter('wpforms_smart_tags', '\UTM_Event_Tracker\wpforms_add_smart_tags');

/**
 * Replace smart tags value
 * 
 * @since 1.0.0
 * @return string
 */
function wpforms_smart_tags_value($content, $tag) {
	foreach (Utils::get_parameters_data() as $utm_key => $utm_value) {
		$smart_tag_key  = 'utm_event_tracker_' . $utm_key;
		if ($smart_tag_key === $tag) {
			$content = str_replace('{' . $smart_tag_key . '}', $utm_value, $content);
		}
	}

	return $content;
}
add_filter('wpforms_smart_tag_process', '\UTM_Event_Tracker\wpforms_smart_tags_value', 100, 2);

/** 
 * Add event after form submission
 * 
 * @since 1.0.0
 * @return void
 */
function wpforms_process_complete($fields, $entry, $form_data) {
	if (Session::is_available()) {
		utm_event_tracker_add_event('wpforms_submission', array(
			'title' => esc_html__('Form Submit - WPForms', 'utm-event-tracker-and-analytics'),
			'meta_data' => array(
				'form_id' => $form_data['id']
			)
		));

		$data = array();
		foreach ($fields as $field_item) {
			$data[$field_item['name']] = $field_item['value'];
		}

		Webhook::get_instance()->send($data);
	}

	if (Google_Analytics::is_send_event_active()) {
		$events = Google_Analytics::get_instance()->get_events('wpforms');

		foreach ($events as $event) {
			$event = new Google_Analytics_Event($event);
			$event->extra_data = $form_data;
			$conditions_result = array_map(function ($condition) use ($form_data) {
				return ($form_data['id'] == $condition['value']);
			}, $event->get_conditions());

			if ($event->condition_matched($conditions_result)) {
				$event->send_event();
			}
		}
	}
}
add_action('wpforms_process_complete', '\UTM_Event_Tracker\wpforms_process_complete', 10, 3);

/**
 * Add google analytics events
 * 
 * @since 1.1.3
 * @return array
 */
function wpforms_ga4_event($events) {
	$events['wpforms'] = array(
		'event_group' => 'form_submit',
		'event_type' => 'form_submission',
		'condition_template' => 'global_form_template',
		'condition_type_default_value' => 'form_id',
		'title' => esc_html__('WPForms', 'utm-event-tracker-and-analytics'),
		'single_title' => esc_html__('WP Form Submit', 'utm-event-tracker-and-analytics'),
	);

	return $events;
}
add_filter('utm_event_tracker/google_analytics/plugins_events', '\UTM_Event_Tracker\wpforms_ga4_event');
