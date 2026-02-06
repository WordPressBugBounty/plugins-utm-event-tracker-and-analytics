<?php

namespace UTM_Event_Tracker\Admin;

use UTM_Event_Tracker\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * UTM Campaign class
 */
final class UTM_Source {

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
			__('UTM Sources', 'utm-event-tracker-and-analytics'),
			__('Sources', 'utm-event-tracker-and-analytics'),
			'manage_categories',
			'utm-event-tracker-sources',
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
		include_once UTM_EVENT_TRACKER_PATH . '/template/utm-source.php';
	}

	/**
	 * Dashboard widget for short report
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function dashboard_widget($widgets) {
		$widgets['utm_source'] = array(
			'priority' => 15,
			'placement' => 'left',
			'callback' => array($this, 'widget'),
			'title' => __('UTM Sources', 'utm-event-tracker-and-analytics'),
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
		echo '<overview-widget param="utm_source" v-if="!widget_is_visible(\'utm_source\')">';
		echo '<template v-slot:header_left>';
		echo '<h3>' . esc_html__('UTM Sources', 'utm-event-tracker-and-analytics') . '</h3>';
		echo '</template>';
		echo '</overview-widget>';
	}
}
