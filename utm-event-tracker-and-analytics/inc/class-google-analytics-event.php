<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Google analytics event class
 */
class Google_Analytics_Event {

	/**
	 * Hold the plugin event
	 * 
	 * @var array
	 * @since 1.1.3
	 */
	private $plugin_event = false;

	/**
	 * Hold the session
	 * 
	 * @var Session
	 * @since 1.1.3
	 */
	private $session = null;

	/**
	 * Hold the event data
	 * 
	 * @var array
	 * @since 1.1.3
	 */
	private $event_data = false;

	/**
	 * Hold event item conditions
	 * 
	 * @var array
	 * @since 1.1.3
	 */
	private $conditions = array();

	/**
	 * Hold conditon match relation
	 * 
	 * @var string
	 * @since 1.1.3
	 */
	private $match_condition = 'any';

	/**
	 * Hold custom parameters of event item
	 * 
	 * @var array
	 * @since 1.1.3
	 */
	private $custom_params = [];

	/**
	 * Set and hold extra data for filter from another source
	 * 
	 * @var mixed
	 * @since 1.1.3
	 */
	public $extra_data = false;

	/** 
	 * Constructor 
	 * 
	 * @since 1.1.3
	 */
	public function __construct($event, $private_plugin_event = false) {
		$event = wp_parse_args($event, Google_Analytics::get_event_item_params());
		if (empty($event['event_name'])) {
			return;
		}

		$plugin_event = Google_Analytics::get_instance()->get_plugins_event($event['event_name']);
		if (is_array($private_plugin_event)) {
			$plugin_event = $private_plugin_event;
		}

		if (false === $plugin_event) {
			return;
		}

		$this->event_data = $event;
		$this->plugin_event = $plugin_event;

		$this->set_conditions($event['conditions']);
		$this->set_custom_params($event['custom_params']);
		$this->match_condition = trim($event['match_condition']);
	}

	/** 
	 * Magic method isset
	 * 
	 * @since 1.1.3
	 * @return boolean
	 */
	public function __isset($key) {
		return isset($this->event_data[$key]);
	}

	/** 
	 * Get event data by key
	 * 
	 * @since 1.1.3
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->event_data[$key]) ? $this->event_data[$key] : null;
	}

	/** 
	 * Get event name of this event
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public function get_event_name() {
		return trim($this->event_name);
	}

	/** 
	 * Check the setting disability
	 * 
	 * @since 1.1.3
	 * @return boolean
	 */
	public function is_disabled_setting($key) {
		if (!is_array($this->plugin_event)) {
			return true;
		}

		if (!isset($this->plugin_event['disable_settings']) || !isset($this->plugin_event['settings_disability'])) {
			return true;
		}

		if (true === $this->plugin_event['disable_settings'] || !is_array($this->plugin_event['settings_disability'])) {
			return true;
		}

		return in_array($key, $this->plugin_event['settings_disability']);
	}

	/** 
	 * Set event key
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function set_event_key($event_key) {
		$this->plugin_event['event_type'] = $event_key;
	}

	/** 
	 * Get event key
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public function get_event_key() {
		$event_key = $this->plugin_event['event_type'];
		if (!$this->is_disabled_setting('custom_event_key') && !empty($this->custom_event_key)) {
			$event_key = $this->custom_event_key;
		}

		return Utils::sanitize_event_key($event_key);
	}

	/** 
	 * Set the session for this event
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function set_session($session) {
		$this->session = $session;
	}

	/** 
	 * Get the session for this event
	 * 
	 * @since 1.1.3
	 * @return Session
	 */
	public function get_session() {
		if (!is_a($this->session, 'UTM_Event_Tracker\Session')) {
			$this->session = Session::get_current_session();
		}

		return $this->session;
	}

	/** 
	 * Set conditions
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function set_conditions($conditions) {
		if (!is_array($conditions) || empty($this->plugin_event['condition_template']) || $this->is_disabled_setting('conditions')) {
			return;
		}

		$conditions = array_map(function ($item) {
			$item = wp_parse_args($item, array(
				'type' => '',
				'value' => null,
				'condition_template' => '',
			));

			foreach ($item as $item_key => $item_value) {
				if (is_scalar($item_value)) {
					$item[$item_key] = trim($item_value);
				}
			}

			return $item;
		}, $conditions);

		$this->conditions = wp_list_filter($conditions, array('condition_template' => $this->plugin_event['condition_template']));
	}

	/** 
	 * Get conditions of send event item
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public function get_conditions() {
		return $this->conditions;
	}

	/** 
	 * Set custom params
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public function set_custom_params($custom_params) {
		if (!is_array($custom_params) || $this->is_disabled_setting('custom_params')) {
			return;
		}

		$custom_params = array_map(function ($item) {
			$item = wp_parse_args($item, array(
				'key' => '',
				'value' => '',
			));

			$item['key'] = Utils::sanitize_event_key($item['key']);

			return $item;
		}, $custom_params);

		$this->custom_params = array_filter($custom_params, function ($item) {
			return !empty($item['key']);
		});
	}

	/** 
	 * Check if debug mode 
	 * 
	 * @since 1.1.3
	 * @return boolean
	 */
	public function is_debug_mode() {
		return false;
	}

	/** 
	 * Check if conditions matched
	 * 
	 * @since 1.1.3
	 * @return boolean
	 */
	public function condition_matched($conditions_result) {
		if (count($conditions_result) === 0) {
			return true;
		}

		$matched_conditions = array_filter($conditions_result);
		if ('all' === $this->match_condition) {
			return count($matched_conditions) === count($conditions_result);
		} else {
			return count($matched_conditions) > 0;
		}

		return false;
	}

	/** 
	 * Check global form template condition
	 * 
	 * @since 1.1.3
	 * @return boolean
	 */
	public function check_global_form_template_conditions($compare_data) {
		$conditions = array_filter($this->get_conditions(), fn($item) => !empty($item['type']));
		$conditions_result = array_map(function ($condition) use ($compare_data) {
			return ($compare_data['form_id'] == $condition['value']);
		}, $conditions);

		return $this->condition_matched($conditions_result);
	}

	/** 
	 * Send event
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function send_event($payload = array()) {
		if (false === $this->event_data || false === $this->plugin_event || !Google_Analytics::is_send_event_active()) {
			return;
		}

		$api_secret = Settings::get_instance()->get('google_analytics_api_secret');
		$measurement_id = Settings::get_instance()->get('google_analytics_measurement_id');
		if (empty($api_secret) || empty($measurement_id)) {
			return false;
		}

		$event_key = $this->get_event_key();
		$send_without_session = apply_filters('utm_event_tracker/google_analytics/send_event_without_session', $this->get_session()->is_exists(), $this);
		if (false === $send_without_session || empty($event_key)) {
			return false;
		}

		$event_params = array();

		$utm_data = array_filter($this->get_session()->get_utm_values());
		unset($utm_data['ip_address'], $utm_data['landing_page']);
		foreach ($utm_data as $utm_key => $utm_value) {
			$event_params[$utm_key] = $utm_value;
		}

		foreach ($this->custom_params as $custom_param_item) {
			$event_params[$custom_param_item['key']] = $custom_param_item['value'];
		}

		$event_params = apply_filters('utm_event_tracker/google_analytics/event_params', array_merge($event_params, $payload), $this);
		$event_params['session_id'] = Session_Handler::get_instance()->get_session_started_time();

		if ($this->is_debug_mode()) {
			$event_params['debug_mode'] = 1;
		}

		$events_data = array(
			'client_id' => Google_Analytics::get_client_id(),
			'events' => array(
				array('name' => $event_key, 'params' => $event_params)
			)
		);

		$request_url = 'https://www.google-analytics.com/mp/collect';
		if ($this->is_debug_mode()) {
			$request_url = 'https://www.google-analytics.com/debug/mp/collect';
		}

		$request_url = add_query_arg(array(
			'api_secret' => $api_secret,
			'measurement_id' => $measurement_id,
		), $request_url);

		wp_remote_post($request_url, array(
			'headers' => array(
				'content-type' => 'application/json'
			),
			'body' => wp_json_encode($events_data)
		));
	}
}
