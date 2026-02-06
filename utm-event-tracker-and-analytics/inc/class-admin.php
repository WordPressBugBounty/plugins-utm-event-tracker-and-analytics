<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main class plugin
 */
final class Admin {

	/**
	 * The single instance of the class.
	 *
	 * @var Admin
	 * @since 1.1.2
	 */
	protected static $_instance = null;

	/**
	 * Admin Instance.
	 *
	 * @since 1.1.2
	 * @return Admin - Main instance.
	 */
	public static function get_instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Hold the instance of Report Widget
	 * 
	 * @var Admin\Report_Widgets
	 */
	public $report_widgets = null;

	/** 
	 * Constructor 
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load();
		$this->init();

		add_action('admin_menu', [$this, 'admin_menu'], 0);
		add_action('admin_footer', [$this, 'include_components']);
		add_action('init', array($this, 'handle_settings_form'));
		add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
		add_action('wp_ajax_utm_event_tracker/handle_preview_mode', array($this, 'handle_preview_mode'));
		add_action('wp_ajax_utm_event_tracker/dismiss_cache_notice', array($this, 'dismiss_cache_notice'));
		add_action('wp_ajax_utm_event_tracker/clear_current_session', array($this, 'clear_current_session'));

		add_action('utm_event_tracker/admin_settings', array($this, 'add_cookie_setting_field'), 2);
		add_action('utm_event_tracker/admin_settings', array($this, 'add_debugging_option'), 2.1);

		add_action('utm_event_tracker/admin_settings', array($this, 'add_append_parameters_field'));
		add_action('utm_event_tracker/admin_settings', array($this, 'create_session_without_utm_params'), 12);
		add_action('utm_event_tracker/admin_settings', array($this, 'add_ipinfo_token_field'), 15);
		add_action('utm_event_tracker/admin_settings', array($this, 'add_webhook_url_field'), 20);
		add_action('utm_event_tracker/admin_settings', array($this, 'add_custom_event_field'), 25);
		add_action('utm_event_tracker/after_custom_events', array($this, 'add_custom_events_fields'));
		add_action('utm_event_tracker/admin_settings', array($this, 'add_custom_parameters_option'), 4);
	}

	/**
	 * Load files
	 * 
	 * @since 1.0.0
	 */
	public function load() {
		require_once UTM_EVENT_TRACKER_PATH . 'inc/admin/class-utm-sessions.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/admin/class-utm-campaign.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/admin/class-utm-medium.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/admin/class-utm-source.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/admin/class-utm-content.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/admin/class-utm-term.php';
		require_once UTM_EVENT_TRACKER_PATH . 'inc/admin/class-event.php';
	}

	/**
	 * Initialize classes
	 * 
	 * @since 1.0.0
	 */
	public function init() {
		new Admin\UTM_Sessions();
		new Admin\UTM_Campaign();
		new Admin\UTM_Medium();
		new Admin\UTM_Source();
		new Admin\UTM_Content();
		new Admin\UTM_Term();
		new Admin\Sessoin_Event();
	}

	/**
	 * Handle submitted settings form
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_settings_form() {
		if (!isset($_POST['_wpnonce']) || !isset($_POST['utm_event_tracker_settings']) || !isset($_POST['_wp_http_referer'])) {
			return;
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), '_nonce_utm_event_tracker_settings')) {
			return;
		}

		update_option('utm_event_tracker_settings', sanitize_text_field(wp_unslash($_POST['utm_event_tracker_settings'])));
		wp_safe_redirect(sanitize_text_field(wp_unslash($_POST['_wp_http_referer']))); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Handle dismiss cache notice request
	 * 
	 * @since 1.0.9
	 * @return void
	 */
	public function dismiss_cache_notice() {
		if (!isset($_POST['_wpnonce'])) {
			wp_send_json_error();
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'utm_event_tracker/dismiss_cache_notice_nonce')) {
			wp_send_json_error();
		}

		update_option('utm_event_tracker_dismiss_cache_notice', 'yes');
		wp_send_json_success();
	}

	/**
	 * Clear current session for debugging
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function clear_current_session() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'utm_event_tracker/clear_session')) {
			wp_send_json_error();
		}

		delete_transient(Session::get_transient_key());
		setcookie(Session::COOKIE_KEY, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
		wp_send_json_success();
	}

	/**
	 * Handle preview mode
	 * 
	 * @since 1.1.6
	 * @return void
	 */
	public function handle_preview_mode() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'utm_event_tracker/preview_mode_nonce')) {
			wp_send_json_error();
		}

		$preview_mode = isset($_POST['preview_mode']) ? filter_var(wp_unslash($_POST['preview_mode']), FILTER_VALIDATE_BOOLEAN) : false;

		$settings = Settings::get_instance();
		$settings->disable_preview_mode =  $preview_mode;
		$settings->save();
		wp_send_json_success();
	}

	/**
	 * Register admin page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_menu() {
		add_menu_page(__('UTM Analytics', 'utm-event-tracker-and-analytics'), __('UTM Analytics', 'utm-event-tracker-and-analytics'), 'manage_categories', 'utm-event-tracker', array($this, 'screen_overview'), 'dashicons-chart-bar', 25);
		add_submenu_page('utm-event-tracker', __('UTM Analytics', 'utm-event-tracker-and-analytics'), __('Overview', 'utm-event-tracker-and-analytics'), 'manage_categories', 'utm-event-tracker', [$this, 'screen_overview'], 0);
		do_action('utm_event_tracker/admin_menu');
		add_submenu_page('utm-event-tracker', __('UTM Analytics Settings', 'utm-event-tracker-and-analytics'), __('Settings', 'utm-event-tracker-and-analytics'), 'manage_options', 'utm-event-tracker-settings', array($this, 'screen_settings'));
	}

	/**
	 * Enqueue scripts
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		preg_match('/(utm-event-tracker)/', $screen->id, $matches);
		if (empty($matches)) {
			return;
		}

		if (defined('UTM_EVENT_TRACKER_DEV_MODE')) {
			wp_register_script('utm-event-tracker-vue', UTM_EVENT_TRACKER_URL . 'assets/vue.js', [], '3.5.13', true);
		} else {
			wp_register_script('utm-event-tracker-vue', UTM_EVENT_TRACKER_URL . 'assets/vue.min.js', [], '3.5.13', true);
		}

		wp_register_style('utm-event-tracker-icons', UTM_EVENT_TRACKER_URL . 'assets/utm-event-tracker-icons/iconly.min.css', [], UTM_EVENT_TRACKER_VERSION);
		wp_register_style('daterangepicker', UTM_EVENT_TRACKER_URL . 'assets/daterangepicker.css');
		wp_enqueue_style('utm-event-tracker-admin', UTM_EVENT_TRACKER_URL . 'assets/admin.css', ['daterangepicker', 'utm-event-tracker-icons'], UTM_EVENT_TRACKER_VERSION);

		wp_register_script('daterangepicker', UTM_EVENT_TRACKER_URL . 'assets/daterangepicker.min.js', ['moment'], 3.1, true);
		do_action('utm_event_tracker/admin_enqueue_scripts');
		wp_enqueue_script('utm-event-tracker', UTM_EVENT_TRACKER_URL . 'assets/admin.min.js', ['utm-event-tracker-vue', 'wp-hooks', 'wp-i18n', 'daterangepicker'], UTM_EVENT_TRACKER_VERSION, true);
		wp_localize_script('utm-event-tracker', 'utm_event_tracker', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'setting_models' => Settings::get_instance()->get_all_data(),
			'event_item_params' => Google_Analytics::get_event_item_params(),
			'google_analytics_plugins_events' => Google_Analytics::get_plugins_events(false),
			'i10n' => array(
				'confirm_delete_ga4_event' => esc_html__('Are you sure you want to delete this event?', 'utm-event-tracker-and-analytics'),
				'confirm_delete_custom_event' => esc_html__('Are you sure you want to delete this custom event?', 'utm-event-tracker-and-analytics'),
				'confirm_delete_ga4_condition' => esc_html__('Are you sure you want to delete this condition?', 'utm-event-tracker-and-analytics'),
				'confirm_delete_ga4_custom_param' => esc_html__('Are you sure you want to delete this custom param?', 'utm-event-tracker-and-analytics'),
			)
		));
	}

	/**
	 * Implement overview page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function screen_overview() {
		include_once UTM_EVENT_TRACKER_PATH . '/template/overview.php';
	}

	/**
	 * Add component templates for vuejs
	 * 
	 * @since 1.0.0
	 */
	public function include_components() {
		echo '<template id="utm-event-tracker-pagination">';
		include_once UTM_EVENT_TRACKER_PATH . '/component/pagination.php';
		echo '</template>';

		echo '<template id="utm-event-tracker-keyword-stats">';
		include_once UTM_EVENT_TRACKER_PATH . '/component/keyword-stats.php';
		echo '</template>';

		echo '<template id="utm-event-tracker-session-list-all">';
		include_once UTM_EVENT_TRACKER_PATH . '/component/session-list-all.php';
		echo '</template>';

		echo '<template id="utm-event-tracker-session-list-param">';
		include_once UTM_EVENT_TRACKER_PATH . '/component/session-list-param.php';
		echo '</template>';

		echo '<template id="session-summary">';
		include_once UTM_EVENT_TRACKER_PATH . '/component/session-summary.php';
		echo '</template>';

		echo '<template id="utm-event-tracker-overview-widget">';
		include_once UTM_EVENT_TRACKER_PATH . '/component/overview-widget.php';
		echo '</template>';
	}

	/**
	 * Implement settings page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function screen_settings() { ?>
		<div class="wrap wrap-utm-event-tracker">
			<h1 class="wp-heading-inline"><?php esc_html_e('UTM Event Tracker Settings', 'utm-event-tracker-and-analytics'); ?></h1>
			<hr class="wp-header-end">

			<form id="utm-event-tracker-settings" method="post">
				<?php wp_nonce_field('_nonce_utm_event_tracker_settings'); ?>
				<input type="hidden" name="utm_event_tracker_settings" :value="get_settings_data">
				<?php include_once UTM_EVENT_TRACKER_PATH . '/inc/admin/settings-template.php'; ?>
			</form>
		</div>
	<?php
	}

	/**
	 * Add cookie setting field
	 * 
	 * @since 1.1.2
	 */
	public function add_cookie_setting_field() { ?>
		<tr>
			<th>
				<label for="cookie-duration"><?php esc_html_e('Cookie Duration', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Specify the days of cookie duration. Default is 30 days.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td>
				<input style="width: 60px;padding-right: 0" type="number" id="cookie-duration" v-model="cookie_duration">
				<?php esc_html_e('days', 'utm-event-tracker-and-analytics'); ?>
			</td>
		</tr>
	<?php
	}

	/**
	 * Add debugging setting field
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function add_debugging_option() { ?>
		<tr>
			<th>
				<label for="clear-session"><?php esc_html_e('Clear Session', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Clear the session data to create a new one for debugging.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td>
				<div style="display: flex; gap: 5px">
					<button class="button" href="#" @click.prevent="clear_session()" :disabled="session_clearing" ref="clear_session" data-nonce="<?php echo esc_attr(wp_create_nonce('utm_event_tracker/clear_session')) ?>"><?php esc_html_e('Clear your session', 'utm-event-tracker-and-analytics'); ?></button>
					<span v-if="session_clearing" class="utm-event-tracker-loading"></span>
				</div>
				<p class="field-note"><?php esc_html_e('Click the button to clear your session data. This will remove the current session from both cookies and the database.', 'utm-event-tracker-and-analytics'); ?></p>
			</td>
		</tr>

		<tr>
			<th>
				<label for="clear-session"><?php esc_html_e('Preview Mode', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Enable/Disable preview mode to test data on frontend.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td>
				<div style="display: flex; gap: 5px" ref="preview_mode_nonce" data-nonce="<?php echo esc_attr(wp_create_nonce('utm_event_tracker/preview_mode_nonce')) ?>">
					<button class="button" v-if="!disable_preview_mode" :disabled="preview_mode_updating" href="#" @click.prevent="handle_preview_mode(true)"><?php esc_html_e('Disable preview mode', 'utm-event-tracker-and-analytics'); ?></button>
					<button class="button" v-if="disable_preview_mode" :disabled="preview_mode_updating" href="#" @click.prevent="handle_preview_mode(false)"><?php esc_html_e('Enable preview mode', 'utm-event-tracker-and-analytics'); ?></button>
					<span v-if="preview_mode_updating" class="utm-event-tracker-loading"></span>
				</div>
				<p class="field-note" v-if="disable_preview_mode"><?php esc_html_e('Enable preview mode to verify that everything is functioning correctly.', 'utm-event-tracker-and-analytics'); ?></p>
				<p class="field-note" v-if="!disable_preview_mode"><?php esc_html_e('Disable preview mode to hide it after confirming that everything is working correctly.', 'utm-event-tracker-and-analytics'); ?></p>
			</td>
		</tr>
	<?php
	}

	/**
	 * Add append parameter setting field
	 * 
	 * @since 1.1.2
	 */
	public function add_append_parameters_field() { ?>
		<tr>
			<th>
				<label for="append-utm-parameters"><?php esc_html_e('Append UTM Parameters', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Append UTM parameters to the URL.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td>
				<div class="switch-input-field" @click="show_modal = 'append_utm_parameter'">
					<label>
						<input type="radio" disabled>
						<?php esc_html_e('Yes', 'utm-event-tracker-and-analytics'); ?>
					</label>

					<label>
						<input type="radio" disabled checked>
						<?php esc_html_e('No', 'utm-event-tracker-and-analytics'); ?>
					</label>
				</div>

				<p class="field-note"><?php esc_html_e('Append UTM parameters to webpage URLs to track campaign performance, including source, medium, campaign, term, content, fbclid and gclid.', 'utm-event-tracker-and-analytics'); ?></p>
			</td>
		</tr>
	<?php
	}

	/**
	 * Add ipinfo token setting field
	 * 
	 * @since 1.1.2
	 */
	public function add_ipinfo_token_field() { ?>
		<tr>
			<th>
				<label for="ipinfo-token"><?php esc_html_e('IP Info Token', 'utm-event-tracker-and-analytics'); ?></label>

				<?php
				$note_text = sprintf(
					/* translators: 1 for ipinfo link */
					__('Get token from %s. 50k requests free per month.', 'utm-event-tracker-and-analytics'),
					'<a target="_blank" href="https://ipinfo.io/pricing">IP Info</a>'
				);
				?>

				<p class="field-note"><?php echo wp_kses($note_text, array('a' => array('href' => true, 'target' => true))); ?></p>
			</th>
			<td>
				<input v-model="ipinfo_token" type="password" id="ipinfo-token" placeholder="<?php esc_html_e('Enter your IP Info Token', 'utm-event-tracker-and-analytics'); ?>">
			</td>
		</tr>
	<?php
	}

	/**
	 * Add webhook url setting field
	 * 
	 * @since 1.1.2
	 */
	public function add_webhook_url_field() { ?>
		<tr>
			<th>
				<label for="webhook-url"><?php esc_html_e('Webhook URL', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Enter the webhook URL to receive UTM tracking data in real time. Compatible with Zapier for automation.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td>
				<input v-model="webhook_url" type="url" id="webhook-url" placeholder="<?php esc_html_e('Enter your webhook URL', 'utm-event-tracker-and-analytics'); ?>">
			</td>
		</tr>
	<?php
	}

	/**
	 * Add custom event field
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function add_custom_event_field() { ?>
		<tr>
			<th>
				<label><?php esc_html_e('Custom Events', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Add custom events to track button or link clicks. Use CSS selectors to target the elements you want to track.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td style="vertical-align:top">

				<label>
					<input type="checkbox" v-model="capture_custom_events">
					<?php esc_html_e('Capture custom events', 'utm-event-tracker-and-analytics'); ?>
				</label>

				<div style="margin-bottom: 8px;"></div>

				<template v-if="capture_custom_events">
					<table class="utm-event-tracker-custom-events" v-for="(event_item, event_item_key) in custom_events" :key="event_item_key">
						<tr>
							<th>
								<?php esc_html_e('Event Title', 'utm-event-tracker-and-analytics'); ?>
								<div class="utm-event-tracker-tooltip">
									<div><?php esc_html_e('Specify the event title for the element you want to track.', 'utm-event-tracker-and-analytics'); ?></div>
								</div>
							</th>
							<td>
								<input type="text" v-model="event_item.title" required placeholder="<?php esc_html_e('Enter event title', 'utm-event-tracker-and-analytics') ?>">
							</td>

							<td class="custom-event-action" rowspan="3">
								<div class="event-action-container">
									<a class="dashicons dashicons-admin-page" href="#" @click.prevent="duplicate_custom_event(event_item_key)"></a>
									<a class="dashicons dashicons-trash" href="#" @click.prevent="delete_custom_event(event_item_key)"></a>
								</div>
							</td>
						</tr>

						<tr>
							<th>
								<?php esc_html_e('Event Selector', 'utm-event-tracker-and-analytics'); ?>
								<div class="utm-event-tracker-tooltip">
									<div><?php esc_html_e('Use CSS selector for this event like .container .button-phone, #btn-email.', 'utm-event-tracker-and-analytics'); ?></div>
								</div>
							</th>
							<td>
								<?php $placeholder = esc_html__('Use commas for multiple selectors.', 'utm-event-tracker-and-analytics'); ?>
								<textarea type="text" v-model="event_item.selector" required title="<?php echo esc_attr($placeholder) ?>" placeholder="<?php echo esc_attr($placeholder) ?>"></textarea>
							</td>
						</tr>

						<tr>
							<th>
								<?php esc_html_e('Event Type', 'utm-event-tracker-and-analytics'); ?>

								<div class="utm-event-tracker-tooltip">
									<div><?php esc_html_e('Enter value like phone_click or button_click.', 'utm-event-tracker-and-analytics'); ?></div>
								</div>
							</th>
							<td>
								<?php $placeholder = esc_html__('Enter the event type', 'utm-event-tracker-and-analytics'); ?>
								<input type="text" v-model="event_item.event_type" required title="<?php echo esc_attr($placeholder) ?>" placeholder="<?php echo esc_attr($placeholder) ?>">
							</td>
						</tr>
					</table>

					<?php do_action('utm_event_tracker/after_custom_events') ?>
				</template>
			</td>
		</tr>
	<?php
	}

	/**
	 * Add custom events fields
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function add_custom_events_fields() { ?>
		<template v-if="custom_events.length > 0">
			<label>
				<input type="checkbox" disabled>
				<?php esc_html_e('Track custom events without session', 'utm-event-tracker-and-analytics'); ?>
			</label>

			<?php Utils::get_field_note(esc_html__('Track custom events even if the visitor does not arrive with UTM values in the URL\'s query string.', 'utm-event-tracker-and-analytics'), '', 'custom+events', 'track+custom+events') ?>
		</template>

		<div style="margin-top: 10px;"></div>
		<button class="button button-primary button-add-custom-event" @click.prevent="add_custom_event()">
			<span class="dashicons dashicons-lock" v-if="custom_events.length >= 1"></span>
			<?php esc_html_e('Add a Custom Event', 'utm-event-tracker-and-analytics'); ?>
		</button>
	<?php
	}

	/**
	 * Add append parameter setting field
	 * 
	 * @since 1.1.8
	 * @return void
	 */
	public function create_session_without_utm_params() { ?>
		<tr>
			<th>
				<label><?php esc_html_e('Create Session Without UTM Parameters', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Start tracking sessions even when no UTM parameters are found in the URL.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td>
				<div class="switch-input-field" @click="show_modal = 'create_session_without_utm'">
					<label>
						<input type="radio" disabled>
						<?php esc_html_e('Yes', 'utm-event-tracker-and-analytics'); ?>
					</label>

					<label>
						<input type="radio" checked disabled>
						<?php esc_html_e('No', 'utm-event-tracker-and-analytics'); ?>
					</label>
				</div>

				<p class="field-note"><?php esc_html_e('Helps you track more visitors by creating sessions even when UTM parameters are missing. This reduces lost attribution and improves overall analytics accuracy.', 'utm-event-tracker-and-analytics'); ?></p>
			</td>
		</tr>
	<?php
	}

	/**
	 * Add custom parameter setting field
	 * 
	 * @since 1.1.9
	 * @return void
	 */
	public function add_custom_parameters_option() { ?>
		<tr>
			<th>
				<label><?php esc_html_e('Custom Parameters', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Allow tracking sessions using your own custom URL parameters in addition to UTM parameters.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td>

				<table class="table-utm-event-tracker-repeater" style="margin-bottom: 10px;">
					<thead>
						<tr>
							<th><?php esc_html_e('Key', 'utm-event-tracker-and-analytics'); ?></th>
							<th><?php esc_html_e('Title', 'utm-event-tracker-and-analytics'); ?></th>
							<th class="column-min"></th>
						</tr>
					</thead>

					<tbody>

						<tr>
							<td><input type="text" placeholder="fb_campaign_id" disabled></td>
							<td><input type="text" placeholder="<?php esc_html_e('FB Campaign ID', 'utm-event-tracker-and-analytics'); ?>" disabled></td>
							<td><a class="dashicons dashicons-trash" href="javascript:void(0)"></a></td>
						</tr>
					</tbody>
				</table>

				<a class="button" href="#" @click.prevent="show_modal = 'custom_parameters_modal'">
					<span class="dashicons dashicons-lock"></span>
					<?php esc_html_e('Add custom parameter', 'utm-event-tracker-and-analytics'); ?>
				</a>

				<p class="field-note"><?php esc_html_e('Track visitors more accurately by using your own custom URL parameters alongside UTM values.', 'utm-event-tracker-and-analytics'); ?></p>
			</td>
		</tr>
<?php
	}
}

Admin::get_instance();
