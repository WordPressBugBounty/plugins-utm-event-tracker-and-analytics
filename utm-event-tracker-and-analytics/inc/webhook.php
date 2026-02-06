<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class webhook
 * 
 * @since 1.0.0
 */
class Webhook {

	/**
	 * The single instance of the class.
	 *
	 * @var Webhook
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of Main is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @return Main - Main instance.
	 */
	public static function get_instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Webhook URL
	 * 
	 * @since 1.0.0
	 * @var string|boolean
	 */
	public $webhook_url = false;

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->webhook_url = $this->get_webhook_url();
	}

	/**
	 * Get webhook URL
	 * 
	 * @since 1.0.0
	 * @var string|boolean
	 */
	public function get_webhook_url() {
		$url = Settings::get_instance()->get('webhook_url');
		if (filter_var($url, FILTER_VALIDATE_URL)) {
			return esc_url_raw($url);
		}

		return false;
	}

	/**
	 * Send Request
	 * 
	 * @since 1.0.0
	 */
	public function send($data) {
		if (false === $this->webhook_url) {
			return;
		}

		wp_remote_post($this->webhook_url, array('body' => apply_filters('utm_event_tracker/webhook_data', $data)));
	}
}
