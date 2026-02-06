<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Session Handler class
 */
class Session_Handler {

	/**
	 * The single instance of the class.
	 *
	 * @var Session_Handler
	 * @since 1.1.3
	 */
	private static $_instance = null;

	/**
	 * Session Handler Instance.
	 *
	 * @since 1.1.3
	 * @return Session_Handler - Main instance.
	 */
	public static function get_instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Set sessoin value
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public static function set($key, $value) {
		self::get_instance()->$key = $value;
	}

	/**
	 * Get sessoin value
	 * 
	 * @since 1.1.3
	 * @return mixed
	 */
	public static function get($key) {
		return self::get_instance()->$key;
	}

	/**
	 * Session data
	 * 
	 * @var array
	 * @since 1.1.3
	 */
	private $data = array();

	/** 
	 * Constructor 
	 * 
	 * @since 1.1.3
	 */
	public function __construct() {
		$session_data = (array) get_transient(Utils::get_client_key('session_data'));
		foreach ($session_data as $key => $value) {
			if (!empty($key)) {
				$this->data[$key] = $value;
			}
		}
	}

	/**
	 * Check if session data available
	 * 
	 * @since 1.1.3
	 * @return boolean
	 */
	public function __isset($name) {
		return isset($this->data[$name]);
	}

	/**
	 * Set value of property
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function __set($name, $value) {
		$this->data[$name] = $value;
		set_transient(Utils::get_client_key('session_data'), $this->data, (HOUR_IN_SECONDS / 2));
	}

	/**
	 * Get value of property
	 * 
	 * @since 1.1.3
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/** 
	 * Get session start time
	 * 
	 * @since 1.1.3
	 * @return int
	 */
	public function get_session_started_time() {
		if (empty($this->session_started_time)) {
			$this->session_started_time = time();
		}

		return $this->session_started_time;
	}

	/**
	 * Destroy session
	 * 
	 * @since 1.1.9
	 * @return void
	 */
	public static function destroy() {
		delete_transient(Utils::get_client_key('session_data'));
	}
}
