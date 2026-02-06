<?php

namespace UTM_Event_Tracker\Admin;

use UTM_Event_Tracker\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Session List class
 */
final class UTM_Sessions {

	/** 
	 * Constructor 
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action('utm_event_tracker/admin_menu', [$this, 'admin_menu'], 5);
	}

	/**
	 * Register submneu page for UTM campaign
	 * 
	 * @since 1.0.0
	 */
	public function admin_menu() {
		add_submenu_page(
			'utm-event-tracker',
			__('UTM Event Tracker Sessions', 'utm-event-tracker-and-analytics'),
			__('Sessions', 'utm-event-tracker-and-analytics'),
			'manage_categories',
			'utm-event-tracker-sessions',
			array($this, 'screen'),
			10
		);
	}

	/**
	 * UTM campaign page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function screen() {
		echo '<div class="utm-event-tracker-header">';
		echo '<h3>' . esc_html__('Sessions', 'utm-event-tracker-and-analytics') . '</h3>';
		echo '</div>';

		echo '<div id="session-list-report" class="wrap wrap-utm-event-tracker">';
		echo '<hr class="wp-header-end">';

		echo '<div class="utm-report-filter-row">';
		echo '<div class="left-column">';
		echo '<input class="filter-keyword" type="text" placeholder="' . esc_html__('Search keywords...', 'utm-event-tracker-and-analytics') . '" v-model="keywords">';
		echo '</div>';

		echo '<input ref="datepicker" type="text" class="utm-event-tracker-date-picker-input">';
		echo '<span class="btn-reload dashicons dashicons-update" @click="reload()"></span>';
		echo '</div>';

		echo '<session-list-all ref="keyword_list_table" :dates="dates" :keywords="keywords"></utm-event-tracker-session-list-all>';
		echo '</div>';
	}
}
