<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cache class
 */
final class Cache {

	/** 
	 * Constructor 
	 * 
	 * @since 1.0.4
	 */
	public function __construct() {
		add_filter('wpo_cache_defaults', array($this, 'exclude_wp_optimized_cookie'));
	}

	/**
	 * Exclude cookie for wp optimized cache plugin
	 * 
	 * @since 1.0.4
	 */
	public function exclude_wp_optimized_cookie($cookies) {
		$cookies['cache_exception_cookies'][] = 'utm_event_tracker_session';
		return $cookies;
	}
}


new Cache();
