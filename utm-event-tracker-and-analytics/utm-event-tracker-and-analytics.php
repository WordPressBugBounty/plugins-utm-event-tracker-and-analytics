<?php

/**
 * Plugin Name: UTM Event Tracker and Analytics
 * Plugin URI: https://wordpress.org/plugins/utm-event-tracker-and-analytics/
 * Description: Unlocking the Power of UTM Event Tracker and Analytics for Enhanced Marketing Insights
 * Version: 1.2.0
 * Author: Codiepress
 * Author URI: https://codiepress.com/plugins/utm-event-tracker-and-analytics-pro/
 * Text Domain: utm-event-tracker-and-analytics
 * 
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
	exit;
}

define('UTM_EVENT_TRACKER_FILE', __FILE__);
define('UTM_EVENT_TRACKER_VERSION', '1.2.0');
define('UTM_EVENT_TRACKER_BASENAME', plugin_basename(__FILE__));
define('UTM_EVENT_TRACKER_URL', trailingslashit(plugins_url('/', __FILE__)));
define('UTM_EVENT_TRACKER_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('UTM_EVENT_TRACKER_MIN_PHP_VERSION', '7.4.3');


/**
 * Add event function for adding UTM event
 * 
 * @since 1.0.2
 * @param string $type
 * @param array $event_data
 */
function utm_event_tracker_add_event($type, $event_data = array()) {
	$event_data['type'] = $type;
	$session = \UTM_Event_Tracker\Session::get_current_session();
	$session->add_event($event_data);
}

require_once UTM_EVENT_TRACKER_PATH . 'inc/class-main.php';

UTM_Event_Tracker\Main::get_instance();