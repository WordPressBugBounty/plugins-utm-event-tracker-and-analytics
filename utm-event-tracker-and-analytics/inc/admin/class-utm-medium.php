<?php

namespace UTM_Event_Tracker\Admin;

use UTM_Event_Tracker\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * UTM Campaign class
 */
final class UTM_Medium {

	/** 
	 * Constructor 
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action('utm_event_tracker/admin_menu', [$this, 'admin_menu'], 5);
		add_filter('utm_event_tracker/dashboard_widgets', [$this, 'dashboard_widget'], 5);		
	}

	/**
	 * Register submneu page for UTM campaign
	 * 
	 * @since 1.0.0
	 */
	public function admin_menu() {
		add_submenu_page(
			'utm-event-tracker',
			__('UTM Mediums', 'utm-event-tracker-and-analytics'),
			__('Mediums', 'utm-event-tracker-and-analytics'),
			'manage_categories',
			'utm-event-tracker-mediums',
			array($this, 'admin_page'),
			10
		);
	}

	/**
	 * UTM campaign page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_page() {
		include_once UTM_EVENT_TRACKER_PATH . '/template/utm-medium.php';
	}

	/**
	 * Dashboard widget for short report
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function dashboard_widget($widgets) {
		$widgets['utm_medium'] = array(
			'priority' => 20,
			'placement' => 'left',
			'callback' => array($this, 'widget'),
			'title' => __('UTM Mediums', 'utm-event-tracker-and-analytics'),
		);

		return $widgets;
	}

	/**
	 * Widget template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function widget() {
		echo '<overview-widget param="utm_medium" v-if="!widget_is_visible(\'utm_medium\')">';
		echo '<template v-slot:header_left>';
		echo '<h3>' . esc_html__('UTM Mediums', 'utm-event-tracker-and-analytics') . '</h3>';
		echo '</template>';
		echo '</overview-widget>';
	}
}
