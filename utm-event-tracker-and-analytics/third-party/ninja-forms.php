<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('\NF_Abstracts_MergeTags')) {
	return;
}

final class Ninja_Forms_Tags extends \NF_Abstracts_MergeTags {

	/**
	 * ID of ninja form merge tag
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	protected $id = 'utm_event_tracker_merge_tags';

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __('UTM Event Tracker', 'utm-event-tracker-and-analytics');
		$this->ninja_form_tags();
	}

	/**
	 * Set ninja form tags
	 * 
	 * @since 1.0.0
	 */
	public function ninja_form_tags() {
		$parameters = Utils::get_all_parameters();
		foreach ($parameters as $key => $label) {
			$this->merge_tags[$key] = array(
				'id' => $key,
				'tag' => '{utm_event_tracker:' . $key . '}',
				'label' => $label,
				'callback' => 'replace_tag_' . $key
			);
		}
	}

	/**
	 * Hold the dynamic method calling
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public function __call($name, $arguments) {
		$utm_parameter = str_replace('replace_tag_', '', $name);
		return Session::get_current_session()->get($utm_parameter);
	}
}

Ninja_Forms()->merge_tags['utm_event_tracker_merge_tags'] = new Ninja_Forms_Tags();

/**
 * Add event after submitting ninja form
 * 
 * @since 1.0.0
 */
function ninja_forms_submission($form_data) {
	if (Session::is_available()) {
		utm_event_tracker_add_event('ninja_form_submit', array(
			'title' => esc_html__('Form Submit - Ninja', 'utm-event-tracker-and-analytics'),
			'meta_data' => array(
				'form_id' => $form_data['form_id']
			)
		));

		$data = array();
		foreach ($form_data['fields_by_key'] as $field) {
			if (isset($field['key'])) {
				$data[$field['key']] = $field['value'];
			}
		}

		Webhook::get_instance()->send($data);
	}

	if (Google_Analytics::is_send_event_active()) {
		$events = Google_Analytics::get_instance()->get_events('ninja_form');

		foreach ($events as $event) {
			$event = new Google_Analytics_Event($event);
			$event->extra_data = $form_data;

			$conditions_result = $event->check_global_form_template_conditions($form_data);
			if ($event->condition_matched($conditions_result)) {
				$event->send_event();
			}
		}
	}
}
add_action('ninja_forms_after_submission', '\UTM_Event_Tracker\ninja_forms_submission');


/**
 * Add google analytics events
 * 
 * @since 1.1.3
 * @return array
 */
function ninja_forms_ga4_event($events) {
	$events['ninja_form'] = array(
		'event_group' => 'form_submit',
		'event_type' => 'form_submission',
		'condition_template' => 'global_form_template',
		'condition_type_default_value' => 'form_id',
		'title' => esc_html__('Ninja Form', 'utm-event-tracker-and-analytics'),
		'single_title' => esc_html__('Ninja Form Submit', 'utm-event-tracker-and-analytics'),
	);

	return $events;
}
add_filter('utm_event_tracker/google_analytics/plugins_events', '\UTM_Event_Tracker\ninja_forms_ga4_event');
