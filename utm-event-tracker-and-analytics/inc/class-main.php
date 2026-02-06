<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main class plugin
 */
final class Main {

	/**
	 * The single instance of the class.
	 *
	 * @var Main
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
	 * Constructor 
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->add_tables();
		require_once UTM_EVENT_TRACKER_PATH . 'inc/utils.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/webhook.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-migrate.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-settings.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-cache.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-event.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-query.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-session.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-admin.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-session-handler.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-google-analytics.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/class-google-analytics-event.php';

		if (version_compare(PHP_VERSION, UTM_EVENT_TRACKER_MIN_PHP_VERSION, '<')) {
			return add_action('admin_notices', array($this, 'php_version_missing'));
		}

		$this->init();
	}

	/**
	 * Add tables variables at $wpdb 
	 * 
	 * @since 1.0.0
	 */
	public function add_tables() {
		global $wpdb;
		$wpdb->utm_event_tracker_stats_table = $wpdb->prefix . 'utm_event_tracker_stats';
		$wpdb->utm_event_tracker_views_table = $wpdb->prefix . 'utm_event_tracker_views';
		$wpdb->utm_event_tracker_events_table = $wpdb->prefix . 'utm_event_tracker_events';
		$wpdb->utm_event_tracker_sessions_table = $wpdb->prefix . 'utm_event_tracker_sessions';
	}

	/**
	 * Check PHP version
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function php_version_missing() {
		$notice = sprintf(
			/* translators: 1 for plugin name, 2 for PHP, 3 for PHP version */
			esc_html__('%1$s need %2$s version %3$s or greater.', 'utm-event-tracker-and-analytics'),
			'<strong>' . __('UTM Event Tracker and Analytics', 'utm-event-tracker-and-analytics') . '</strong>',
			'<strong>' . __('PHP', 'utm-event-tracker-and-analytics') . '</strong>',
			UTM_EVENT_TRACKER_MIN_PHP_VERSION
		);

		printf('<div class="notice notice-warning"><p>%1$s</p></div>', wp_kses_post($notice));
	}

	/**
	 * Init the UTM analytics plugin
	 * 
	 * @since 1.0.0
	 */
	public function init() {
		add_action('wp', array($this, 'generate_session'));
		add_action('wp_footer', array($this, 'add_preview_section'), 0);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
		add_action('template_redirect', array($this, 'handle_preview_actions'));
		add_action('template_redirect', array($this, 'add_session_view'), 1000);
		add_filter('plugin_action_links', array($this, 'add_plugin_links'), 10, 2);
		add_action('plugins_loaded', array($this, 'load_third_party_files'), 10000);
		add_action('wp_ajax_utm_event_tracker/capture_custom_event', array($this, 'capture_custom_event'));
		add_action('wp_ajax_nopriv_utm_event_tracker/capture_custom_event', array($this, 'capture_custom_event'));
	}

	/**
	 * Load all php file from third-party directory
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function load_third_party_files() {
		foreach (glob(UTM_EVENT_TRACKER_PATH . 'third-party/*.php') as $file) {
			include_once $file;
		}
	}

	/**
	 * Generate user session
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function generate_session() {
		if (is_admin() || wp_doing_ajax() || wp_is_json_request() || wp_doing_cron()) {
			return;
		}

		if (!(is_front_page() || is_singular() || is_archive() || is_404() || is_search())) {
			return;
		}

		if (Session::is_available()) {
			Session::get_current_session()->save();
		}
	}

	/**
	 * Add view of session
	 * 
	 * @since 1.0.0
	 */
	public function add_session_view() {
		if (!Session::is_available() || is_admin()) {
			return;
		}

		Session::get_current_session()->add_view();
	}

	/**
	 * Add links at the plugin action
	 * 
	 * @since 1.0.0
	 * @return array $actions
	 */
	public function add_plugin_links($actions, $plugin_file) {
		if (UTM_EVENT_TRACKER_BASENAME == $plugin_file) {
			$new_links = array(
				'overview' => sprintf('<a href="%s">%s</a>', menu_page_url('utm-event-tracker', false), __('Overview', 'utm-event-tracker-and-analytics')),
				'settings' => sprintf('<a href="%s">%s</a>', menu_page_url('utm-event-tracker-settings', false), __('Settings', 'utm-event-tracker-and-analytics')),
				'get-pro' => '<a target="_blank" href="https://codiepress.com/plugins/utm-event-tracker-and-analytics-pro/?utm_campaign=utm+event+tracker&utm_source=get+pro&utm_medium=plugins+page">' . __('Get Pro', 'utm-event-tracker-and-analytics') . '</a>'
			);

			$actions = array_merge($new_links, $actions);
		}

		return $actions;
	}

	/**
	 * Enqueue script on frontend
	 * 
	 * @since 1.0.1
	 * @return void
	 */
	public function enqueue_script() {
		wp_enqueue_style('utm-event-tracker', UTM_EVENT_TRACKER_URL . 'assets/frontend.css', [], UTM_EVENT_TRACKER_VERSION);
		wp_enqueue_script('utm-event-tracker', UTM_EVENT_TRACKER_URL . 'assets/frontend.min.js', ['jquery'], UTM_EVENT_TRACKER_VERSION, true);

		$settings = Settings::get_instance();
		wp_localize_script('utm-event-tracker', 'utm_event_tracker', array(
			'site_url' => home_url(),
			'ajax_url' => admin_url('admin-ajax.php'),
			'session_id' => Session::get_client_session_id(),
			'utm_parameters' => Utils::get_parameters_data(),
			'custom_events' => $settings->get_custom_events(),
			'parameter_items' => Utils::get_utm_parameters(),
			'append_utm_parameter' => $settings->append_utm_parameter,
			'capture_custom_events' => $settings->capture_custom_events,
			'nonce_capture_custom_event' => wp_create_nonce('_nonce_utm_event_tracker_capture_custom_event'),
		));
	}

	/**
	 * Capture custom event
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function capture_custom_event() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), '_nonce_utm_event_tracker_capture_custom_event')) {
			return;
		}

		$event_title = !empty($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : null;
		$event_type = !empty($_POST['event_type']) ? sanitize_text_field(wp_unslash($_POST['event_type'])) : null;

		$session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
		$session = Session::get_by_session_id($session_id);

		$capture_without_session = Settings::get_instance()->get('capture_custom_events_without_session', false);
		if ($capture_without_session) {
			$session->save();
		}

		if ($session->is_exists()) {
			$session->add_event(array('type' => $event_type, 'title' => $event_title));
		}

		Webhook::get_instance()->send(Utils::get_parameters_data());

		$event_name = Settings::CUSTOM_EVENT_PREFIX . Utils::sanitize_event_key($event_type);
		if (Google_Analytics::is_send_event_active()) {
			$events = Google_Analytics::get_instance()->get_events($event_name);
			foreach ($events as $event) {
				$event = new Google_Analytics_Event($event);
				$event->extra_data = $_POST;
				$event->set_session($session);
				$event->send_event();
			}
		}
	}

	/**
	 * Add preview section
	 * 
	 * @since 1.1.6
	 * @return void
	 */
	public function handle_preview_actions() {
		if (!isset($_GET['utm-event-tracker-action']) || !isset($_GET['nonce'])) {
			return;
		}

		$from_preview = false;

		$nonce = sanitize_text_field(wp_unslash($_GET['nonce']));
		$action = sanitize_text_field(wp_unslash($_GET['utm-event-tracker-action']));

		if ('clear-session' == $action && wp_verify_nonce($nonce, '_nonce_clear_utm_event_tracker_session')) {
			$from_preview = true;
			delete_transient(Session::get_transient_key());
			setcookie(Session::COOKIE_KEY, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
		}

		if ('disable-preview' == $action && wp_verify_nonce($nonce, '_nonce_utm_event_tracker_disable_preview')) {
			$from_preview = true;
			$settings = Settings::get_instance();
			$settings->disable_preview_mode = true;
			$settings->save();
		}

		if (false === $from_preview) {
			return;
		}

		$redirect_url = remove_query_arg(array('utm-event-tracker-action', 'nonce'));
		wp_safe_redirect($redirect_url); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Add preview section
	 * 
	 * @since 1.1.6
	 * @return void
	 */
	public function add_preview_section() {
		$settings = Settings::get_instance();
		if (!current_user_can('manage_options') || true === $settings->disable_preview_mode) {
			return;
		}

		$test_session_url = add_query_arg(array(
			'utm_campaign' => 'test+session',
			'utm_medium' => 'article',
			'utm_source' => 'google',
		));

		$settings_page = add_query_arg('page', 'utm-event-tracker-settings', admin_url('admin.php'));

		$url_without_utm_data = remove_query_arg(array_keys(Utils::get_utm_parameters()));

		$clear_session_url = add_query_arg(array(
			'utm-event-tracker-action' => 'clear-session',
			'nonce' => wp_create_nonce('_nonce_clear_utm_event_tracker_session'),
		), $url_without_utm_data);

		$disable_preview_url = add_query_arg(array(
			'utm-event-tracker-action' => 'disable-preview',
			'nonce' => wp_create_nonce('_nonce_utm_event_tracker_disable_preview'),
		), $url_without_utm_data);

		$session = Session::get_current_session(); ?>

		<div class="utm-event-tracker-preview">
			<h4>Preview mode for test (for admin only)</h4>

			<ol>
				<?php if (!$session->is_exists()) : ?>
					<li><strong>Create a New Session</strong>: A new session will be initialized with test data.</li>
				<?php endif; ?>

				<?php if ($session->is_exists()) : ?>
					<li><strong>Clear the Session</strong>: Your current session will be cleared, and you'll be able to create a new one. You can also clear your session from <a target="_blank" href="<?php echo esc_url($settings_page) ?>">the settings</a> page.</li>
				<?php endif; ?>

				<li><strong>Disable Preview</strong>: To hide this section, click the "Disable Preview" button. You can re-enable preview mode anytime from <a target="_blank" href="<?php echo esc_url($settings_page) ?>">the settings</a>.</li>
			</ol>

			<p class="red">Please note that the session is created based on visitor IP address.</p>

			<div class="preview-actions">
				<?php if (!$session->is_exists()) : ?>
					<a href="<?php echo esc_url($test_session_url) ?>">Create a New Session</a>
				<?php endif; ?>

				<?php if ($session->is_exists()) : ?>
					<a href="<?php echo esc_url($clear_session_url) ?>">Clear the Session</a>
				<?php endif; ?>

				<a href="<?php echo esc_url($disable_preview_url) ?>">Disable Preview</a>
			</div>
		</div>
<?php
	}
}
