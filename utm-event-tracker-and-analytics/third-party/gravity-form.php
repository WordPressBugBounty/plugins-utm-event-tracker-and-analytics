<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('GFForms')) {
	return;
}

/**
 * Add custom merge tags for gravity form
 * 
 * @since 1.0.0
 * @return array
 */
function gravity_form_utm_merge_tags($tags) {
	$parameters = Utils::get_all_parameters();
	foreach ($parameters as $key => $label) {
		$tags[] = array(
			'tag' => sprintf('{utm_event_tracker:%s}', $key),
			'label' => sprintf('%s - %s', esc_html__('UTM Event Tracker', 'utm-event-tracker-and-analytics'), $label)
		);
	}

	return $tags;
}
add_filter('gform_custom_merge_tags', '\UTM_Event_Tracker\gravity_form_utm_merge_tags');

/**
 * Replace custom merge tags
 * 
 * @since 1.0.0
 * @return stringadmin
 */
function gravity_form_replace_merge_tags($text) {
	foreach (Utils::get_parameters_data() as $utm_key => $utm_value) {
		$text = str_replace("{utm_event_tracker:{$utm_key}}", $utm_value, $text);
	}

	return $text;
}
add_filter('gform_replace_merge_tags', '\UTM_Event_Tracker\gravity_form_replace_merge_tags');

/**
 * Add action after gravity form submission
 * 
 * @since 1.0.0
 * @return void
 */
function gravity_form_submission($entry, $form) {
	if (Session::is_available()) {
		utm_event_tracker_add_event('gravity_form_submission', array(
			'title' => esc_html__('Form Submit - Gravity', 'utm-event-tracker-and-analytics'),
			'meta_data' => array(
				'form_id' => $form['id'],
				'entry_id' => $entry['id'],
			)
		));

		$data = array();
		foreach ($form['fields'] as $field) {
			$inputs = $field->get_entry_inputs();
			if (is_array($inputs)) {
				foreach ($inputs as $input) {
					$value = rgar($entry, (string) $input['id']);
					$label = isset($input['adminLabel']) && '' != $input['adminLabel'] ? $input['adminLabel'] : 'input_' . $input['id'];
					$data[$label] = $value;
				}
			} else {
				$value = rgar($entry, (string) $field->id);
				$label = isset($field->adminLabel) && '' != $field->adminLabel ? $field->adminLabel : 'input_' . $field->id;
				$data[$label] = $value;
			}
		}

		Webhook::get_instance()->send($data);
	}

	if (Google_Analytics::is_send_event_active()) {
		$events = Google_Analytics::get_instance()->get_events('gravity_form');

		foreach ($events as $event) {
			$event = new Google_Analytics_Event($event);
			$event->extra_data = $form;
			$conditions_result = array_map(function ($condition) use ($form) {
				return $form['id'] == $condition['value'];
			}, $event->get_conditions());

			if ($event->condition_matched($conditions_result)) {
				$event->send_event();
			}
		}
	}
}
add_action('gform_after_submission', '\UTM_Event_Tracker\gravity_form_submission', 12, 2);


/**
 * Add google analytics events
 * 
 * @since 1.1.3
 * @return array
 */
function gform_add_ga4_event($events) {
	$events['gravity_form'] = array(
		'event_group' => 'form_submit',
		'event_type' => 'form_submission',
		'condition_template' => 'global_form_template',
		'condition_type_default_value' => 'form_id',
		'title' => esc_html__('Gravity Form', 'utm-event-tracker-and-analytics'),
		'single_title' => esc_html__('Gravity Form Submit', 'utm-event-tracker-and-analytics'),
	);

	return $events;
}
add_filter('utm_event_tracker/google_analytics/plugins_events', '\UTM_Event_Tracker\gform_add_ga4_event');
