<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Session class
 * 
 * @since 1.0.0
 */
final class Session {

	/**
	 * Cookie key for sesion
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	const COOKIE_KEY = 'wordpress_utm_event_tracker_session';

	/**
	 * Hold current session instance
	 * 
	 * @since 1.1.3
	 * @var Session
	 */
	private static $current_session = null;

	/**
	 * Get transient key
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public static function get_transient_key() {
		return Utils::get_client_key('session_id');
	}

	/**
	 * Get client session ID
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public static function get_client_session_id() {
		if (!empty($_COOKIE[self::COOKIE_KEY])) {
			return sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_KEY]));
		}

		return get_transient(self::get_transient_key());
	}

	/**
	 * Check if sessoin available
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_available() {
		$parameters = array_keys(Utils::get_utm_parameters());

		$has_utm_parameter = false;
		while ($key = current($parameters)) {
			if (isset($_GET[$key])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$has_utm_parameter = true;
			}

			next($parameters);
		}

		return apply_filters('utm_event_tracker/is_session_available', ($has_utm_parameter || !empty(self::get_client_session_id())));
	}

	/**
	 * Get session by id
	 * 
	 * @since 1.0.0
	 * @return Session
	 */
	public static function get_by_id($id) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->utm_event_tracker_sessions_table WHERE id = %d", $id));
		return new self($session);
	}

	/**
	 * Get session by session_id
	 * 
	 * @since 1.1.2
	 * @return Session
	 */
	public static function get_by_session_id($session_id) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->utm_event_tracker_sessions_table WHERE session_id = %s", $session_id));
		return new self($session);
	}

	/**
	 * Get current session of the visitor
	 * 
	 * @since 1.0.0
	 * @return Session
	 */
	public static function get_current_session() {
		if (is_null(self::$current_session)) {
			self::$current_session = self::get_by_session_id(self::get_client_session_id());
		}

		return self::$current_session;
	}

	/**
	 * ID of session
	 * 
	 * @var integer
	 */
	private $id = 0;

	/**
	 * Hold the session_id of the session
	 * 
	 * @var string
	 */
	private $session_id = '';

	/**
	 * Hold the UTM campaign value of the session
	 * 
	 * @var string|null
	 */
	public $utm_campaign = null;

	/**
	 * Hold the UTM medium value of the session
	 * 
	 * @var string|null
	 */
	public $utm_medium = null;

	/**
	 * Hold the UTM source value of the session
	 * 
	 * @var string|null
	 */
	public $utm_source = null;

	/**
	 * Hold the UTM term value of the session
	 * 
	 * @var string|null
	 */
	public $utm_term = null;

	/**
	 * Hold the UTM content value of the session
	 * 
	 * @var string|null
	 */
	public $utm_content = null;

	/**
	 * Hold the facebook click id of the session
	 * 
	 * @var string|null
	 */
	public $fbclid = null;

	/**
	 * Hold the google click id of the session
	 * 
	 * @var string|null
	 */
	public $gclid = null;

	/**
	 * Hold the google click id of the session
	 * 
	 * @var string
	 */
	public $landing_page = '';

	/**
	 * Hold referrer URL
	 * 
	 * @var string
	 */
	public $referrer = '';

	/**
	 * Hold the user IP address
	 * 
	 * @var string|null
	 */
	public $ip_address = null;

	/**
	 * Hold the city of current session
	 * 
	 * @var string|null
	 */
	public $city = null;

	/**
	 * Hold the region of current session
	 * 
	 * @var string|null
	 */
	public $region = null;

	/**
	 * Hold the country of current session
	 * 
	 * @var string|null
	 */
	public $country = null;

	/**
	 * Hold the date of latest update
	 * 
	 * @var string
	 */
	public $last_online = '';

	/**
	 * Hold the date time of the sessoin
	 * 
	 * @var string
	 */
	public $created_on = '';

	/**
	 * Hold the hash of current session
	 * 
	 * @var string
	 */
	private $hash = '';

	/**
	 * Hold the extra data of the sessoin
	 * 
	 * @var array
	 */
	public $meta_data = array();

	/**
	 * Constructor of session
	 * 
	 * @since 1.0.0
	 */
	public function __construct($session_data = array()) {
		$this->landing_page = $this->get_landing_page();
		$this->created_on = gmdate('Y-m-d H:i:s');

		$session_data = (array) $session_data;
		foreach ($session_data as $key => $value) {
			$key = sanitize_key($key);
			if (empty($key)) {
				continue;
			}

			if ('meta_data' == $key) {
				$this->meta_data = Utils::json_string_to_array($value);
			} else {
				$this->$key = $value;
			}
		}

		$this->id = absint($this->id);
		$this->hash = $this->get_hash();
		if (empty($this->session_id)) {
			$this->session_id = wp_generate_uuid4();
		}

		$this->set_utm_data();
		if (empty($this->ip_address)) {
			$this->ip_address = Utils::get_client_ip();
		}

		if (empty($this->referrer) && !empty($_SERVER['HTTP_REFERER'])) {
			$this->referrer = esc_url_raw(strtok(sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'])), '?'));
		}

		$this->last_online = gmdate('Y-m-d H:i:s');
		if (!is_array($this->meta_data)) {
			$this->meta_data = array();
		}
	}

	/**
	 * Set extra data to dirty data var
	 * 
	 * @since 1.0.0
	 */
	public function __set($key, $value) {
		$this->meta_data[$key] = $value;
	}

	/**
	 * Get extra data from dirty data var
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function __get($key) {
		if ('tracking_time' === $key) {
			return current_time('timestamp');
		}

		return isset($this->meta_data[$key]) ? $this->meta_data[$key] : null;
	}

	/**
	 * Check the key exists within dirty data
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function __isset($key) {
		return isset($this->meta_data[$key]);
	}

	/**
	 * Get ID column of session
	 * 
	 * @since 1.0.0
	 * @return int
	 */
	public function get_id() {
		if (isset($this->new_id) && absint($this->new_id) > 0) {
			return absint($this->new_id);
		}

		return $this->id;
	}

	/**
	 * Check if this is new sesion
	 * 
	 * @since 1.0.0
	 * @return bolean
	 */
	public function is_new() {
		return 0 == $this->id;
	}

	/**
	 * Check session id already exists
	 * 
	 * @since 1.0.0
	 * @return bolean
	 */
	public function is_exists() {
		return $this->get_id() > 0;
	}

	/**
	 * Get the current session id
	 * 
	 * @return string
	 */
	public function get_session_id() {
		return $this->session_id;
	}

	/**
	 * Generate session data from parameter
	 * 
	 * @since 1.0.0
	 */
	public function set_utm_data() {
		if (empty($_GET)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$parameters = array_keys(Utils::get_utm_parameters());
		foreach ($parameters as $param_key) {
			if (!empty($_GET[$param_key])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->{$param_key} = sanitize_text_field(wp_unslash($_GET[$param_key])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
	}

	/**
	 * Get landing page path
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_landing_page() {
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
		$host = !empty($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
		$path = !empty($_SERVER['REQUEST_URI']) ? strtok(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])), '?') : '';
		return str_replace(home_url(), '', "{$protocol}://{$host}{$path}");
	}

	/**
	 * Get hash of current session
	 * 
	 * @since 1.0.0
	 */
	public function get_hash() {
		$session_data = get_object_vars($this);
		unset($session_data['hash'], $session_data['meta_data']);
		return md5(wp_json_encode($session_data));
	}

	/**
	 * Save the session
	 * 
	 * @since 1.0.0
	 * @return false|int
	 */
	public function save() {
		global $wpdb;
		$this->set_location();

		if ($this->is_new()) {
			$attempt = 0;
			$max_attempts = 5;

			while ($attempt < $max_attempts) {
				$session_id_quantity = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE session_id = %s", $wpdb->utm_event_tracker_sessions_table, $this->session_id));
				if (0 == $session_id_quantity) {
					break; // Success
				}

				$attempt++;
				$this->session_id = wp_generate_uuid4();
			}
		}


		$session_data = get_object_vars($this);
		unset($session_data['hash'], $session_data['meta_data']);
		if ($this->hash == $this->get_hash()) {
			return true;
		}

		$session_data['meta_data'] = wp_json_encode($this->meta_data, JSON_UNESCAPED_UNICODE);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->replace($wpdb->utm_event_tracker_sessions_table, $session_data);
		if ($result) {
			$this->new_id = $wpdb->insert_id;

			if ($this->is_new()) {
				Session_Handler::destroy();
				$cookie_duration = Settings::get_instance()->get_cookie_duration();
				set_transient(self::get_transient_key(), $this->get_session_id(), $cookie_duration * DAY_IN_SECONDS);
				setcookie(self::COOKIE_KEY, $this->get_session_id(), strtotime(sprintf('+%d days', $cookie_duration)), COOKIEPATH, COOKIE_DOMAIN);
				$_COOKIE[self::COOKIE_KEY] = $this->get_session_id();
			}

			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Add view
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_view() {
		if (!$this->is_exists() || wp_doing_ajax() || is_admin() || wp_doing_cron()) {
			return;
		}

		if (!(is_singular() || is_archive() || is_post_type_archive())) {
			return;
		}

		$landing_page = $this->get_landing_page();
		$last_viewed_page = Session_Handler::get('last_viewed_page');
		if ($last_viewed_page === $landing_page) {
			return;
		}

		Session_Handler::set('last_viewed_page', $landing_page);

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert($wpdb->utm_event_tracker_views_table, array(
			'session_id' => $this->get_id(),
			'landing_page' => $this->get_landing_page()
		));
	}

	/**
	 * Save event data
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_event($event_data) {
		if (!$this->is_exists()) {
			return;
		}

		$event_data['session_id'] = $this->get_id();
		$event = new Event($event_data);
		$event->save();
	}

	/**
	 * Get value from property
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get($key, $default = null) {
		return $this->$key ? $this->$key : $default;
	}

	/**
	 * Set session location
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function set_location() {
		if (empty($this->ip_address)) {
			return;
		}

		$ipinfo_token = Settings::get_instance()->get('ipinfo_token');
		if (empty($ipinfo_token)) {
			return;
		}

		$response = wp_remote_get(sprintf('https://ipinfo.io/%s?token=%s', sanitize_text_field($this->ip_address), sanitize_text_field($ipinfo_token)));
		if ((is_wp_error($response)) || (200 !== wp_remote_retrieve_response_code($response))) {
			return;
		}

		$result = json_decode(wp_remote_retrieve_body($response), true);
		if (!is_array($result)) {
			return;
		}

		if (!empty($result['city'])) {
			$this->city = $result['city'];
		}

		if (!empty($result['region'])) {
			$this->region = $result['region'];
		}

		if (!empty($result['country'])) {
			$this->country = $result['country'];
		}
	}

	/**
	 * Get full landing page URL of the session
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_landing_page_url() {
		return home_url($this->landing_page);
	}

	/**
	 * Get UTM parameters with values
	 * 
	 * @since 1.1.3
	 * @return array
	 */
	public function get_utm_values() {
		$params = array_keys(Utils::get_all_parameters());

		$values = array();
		while ($key = current($params)) {
			$values[$key] = $this->get($key);
			next($params);
		}

		if (!empty($values['landing_page'])) {
			$values['landing_page'] = home_url($values['landing_page']);
		}

		return $values;
	}
}
