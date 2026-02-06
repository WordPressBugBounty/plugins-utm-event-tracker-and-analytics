<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Settings class plugin
 */
class Settings {

	/** 
	 * Hold prefix for custom event 
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const CUSTOM_EVENT_PREFIX = 'custom_event_';

	/**
	 * The single instance of the class.
	 *
	 * @var Settings
	 * @since 1.1.2
	 */
	protected static $_instance = null;

	/**
	 * Admin Instance.
	 *
	 * @since 1.1.2
	 * @return Settings - Main instance.
	 */
	public static function get_instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Sanitize custom event data
	 * 
	 * @since 1.1.2
	 * @return array
	 */
	public static function sanitize_custom_event($event_data) {
		$event = wp_parse_args($event_data, array(
			'title' => '',
			'selector' => '',
			'event_type' => '',
			'event_group' => 'custom_event',
		));


		$event['title'] = trim($event['title']);
		$event['event_type'] = Utils::sanitize_event_key($event['event_type']);

		return $event;
	}

	/**
	 * Hold settings data
	 * 
	 * @since 1.1.2
	 */
	private $data = array();

	/**
	 * Hold custom events
	 * 
	 * @since 1.1.3
	 */
	private $custom_events = array();

	/**
	 * Hold all added events of google analytics
	 * 
	 * @since 1.1.3
	 */
	private $google_analytics_events = array();

	/** 
	 * Constructor 
	 * 
	 * @since 1.1.2
	 */
	public function __construct() {
		$get_settings = get_option('utm_event_tracker_settings');
		if (!is_array($get_settings)) {
			$get_settings = json_decode(stripslashes($get_settings), true);
		}

		$default_settings = apply_filters('utm_event_tracker/settings_default_values', array(
			'webhook_url' => '',
			'ipinfo_token' => '',
			'cookie_duration' => 30,
			'disable_preview_mode' => false,
			'capture_custom_events' => true,
			'custom_events' => array(),
			'google_analytics_measurement_id' => '',
			'google_analytics_api_secret' => '',
			'send_google_analytics_event' => true,
			'google_analytics_events' => array()
		));

		$settings = wp_parse_args($get_settings, $default_settings);
		if (absint($settings['cookie_duration']) === 0) {
			$settings['cookie_duration'] = 30;
		}

		$settings['cookie_duration'] = absint($settings['cookie_duration']);
		$this->data = $settings;

		$this->set_custom_events();
		$this->set_google_analytics_events();
	}

	/**
	 * Save settings
	 * 
	 * @since 1.1.6
	 * @return void
	 */
	public function save() {
		update_option('utm_event_tracker_settings', wp_json_encode($this->data, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Get all settings
	 * 
	 * @since 1.1.2
	 * @return array
	 */
	public function get_all_data() {
		return $this->data;
	}

	/**
	 * Set magic method
	 * 
	 * @since 1.1.6
	 * @return void
	 */
	public function __set($key, $value) {
		$this->data[$key] = $value;
	}

	/**
	 * Get magic method
	 * 
	 * @since 1.1.2
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * Get value from settings key
	 * 
	 * @since 1.1.2
	 * @return mixed
	 */
	public function get($key, $default_value = null) {
		return isset($this->data[$key]) ? $this->data[$key] : $default_value;
	}

	/**
	 * Get cookie duraction
	 * 
	 * @since 1.1.3
	 * @return int
	 */
	public function get_cookie_duration() {
		return $this->cookie_duration;
	}

	/**
	 * Set custom events
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public function set_custom_events() {
		if (!isset($this->data['custom_events']) || !is_array($this->data['custom_events'])) {
			return;
		}

		$this->custom_events = array_map(fn($event) => self::sanitize_custom_event($event), $this->data['custom_events']);
	}

	/**
	 * Has custom events
	 * 
	 * @since 1.1.2
	 * @return boolean
	 */
	public function has_custom_events() {
		return count($this->custom_events) > 0;
	}

	/**
	 * Get custom events
	 * 
	 * @since 1.1.2
	 * @return array
	 */
	public function get_custom_events() {
		return array_filter($this->custom_events, fn($item) => !empty($item['event_type']));
	}

	/**
	 * Set all added google analytics events
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	private function set_google_analytics_events() {
		$added_events = $this->get('google_analytics_events');
		if (!is_array($added_events)) {
			return;
		}

		$this->google_analytics_events = array_map(function ($event_item) {
			$event_item = wp_parse_args($event_item, Google_Analytics::get_event_item_params());
			$event_item['event_name'] = sanitize_key($event_item['event_name']);

			return $event_item;
		}, $added_events);
	}

	/**
	 * Get google analytics active events
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public function get_google_analytics_events() {
		return array_filter($this->google_analytics_events, fn($event_item) => $event_item['disabled'] != true);
	}
}
