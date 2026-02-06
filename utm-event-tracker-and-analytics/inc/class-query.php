<?php

namespace UTM_Event_Tracker;

use UTM_Event_Tracker\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Query class
 */
final class Query {

	/** 
	 * Constructor 
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action('wp_ajax_utm_event_tracker/get_sessions', [$this, 'get_sessions']);
		add_action('wp_ajax_utm_event_tracker/get_keywords_report', array($this, 'get_keywords_report'));
		add_action('wp_ajax_utm_event_tracker/get_date_report', array($this, 'get_date_report'));
		add_action('wp_ajax_utm_event_tracker/get_keywords_stats', array($this, 'get_keywords_stats'));
		add_action('wp_ajax_utm_event_tracker/update_overview_settings', array($this, 'update_overview_settings'));
	}

	/**
	 * Update data of stats table
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function update_stats_table($stats_type) {
		global $wpdb;

		Migrate::create_stats_table();
		if (!in_array($stats_type, array('total_views', 'total_events'))) {
			return;
		}

		$last_stats_updated_key = 'utm_event_tracker_view_stats_updated';
		if ('total_events' == $stats_type) {
			$last_stats_updated_key = 'utm_event_tracker_event_stats_updated';
		}

		$last_stats_updated_date = get_option($last_stats_updated_key);
		if (!strtotime($last_stats_updated_date)) {
			$last_stats_updated_date = gmdate('Y-m-d H:i:s', strtotime('-5 years'));
		}

		$last_stats_updated_on = gmdate('Y-m-d H:i:s');

		$prepared_sql = $wpdb->prepare(
			"INSERT INTO %i (session_id, views)
			 SELECT session_id, COUNT(*) FROM %i v
			 WHERE v.created_on >= %s AND v.created_on < %s
			 GROUP BY session_id
			 ON DUPLICATE KEY UPDATE views = COALESCE(views, 0) + VALUES(views)",
			$wpdb->utm_event_tracker_stats_table,
			$wpdb->utm_event_tracker_views_table,
			$last_stats_updated_date,
			$last_stats_updated_on
		);

		if ('total_events' == $stats_type) {
			$prepared_sql = $wpdb->prepare(
				"INSERT INTO %i (session_id, events)
				 SELECT session_id, COUNT(*) FROM %i e
				 WHERE e.created_on >= %s AND e.created_on < %s GROUP BY session_id
				 ON DUPLICATE KEY UPDATE events = COALESCE(events, 0) + VALUES(events);",
				$wpdb->utm_event_tracker_stats_table,
				$wpdb->utm_event_tracker_events_table,
				$last_stats_updated_date,
				$last_stats_updated_on
			);
		}

		$is_updating = get_transient('utm_event_tracker_stats_updating') === true;
		if ($is_updating) {
			return;
		}

		set_transient('utm_event_tracker_stats_updating', true, 500);
		$result = $wpdb->query($prepared_sql);
		if ($result) {
			update_option($last_stats_updated_key, $last_stats_updated_on);
		}

		delete_transient('utm_event_tracker_stats_updating');
	}

	/**
	 * Get session data of keywords
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_sessions() {
		$result = check_ajax_referer('_nonce_session_list_keywords', false, false);
		if (false === $result) {
			wp_send_json_error(array(
				'error' => __('Security failed.', 'utm-event-tracker-and-analytics')
			));
		}

		if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
			wp_send_json_error(array(
				'error' => __('Missing dates information.', 'utm-event-tracker-and-analytics')
			));
		}

		$start_date = gmdate('Y-m-d 00:00:00', strtotime(sanitize_text_field(wp_unslash($_POST['start_date']))));
		$end_date = gmdate('Y-m-d 23:59:59', strtotime(sanitize_text_field(wp_unslash($_POST['end_date']))));
		$per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
		$page_no = isset($_POST['page']) ? absint($_POST['page']) : 1;

		global $wpdb;

		$utm_event_tracker_column = empty($_POST['column']) ? 'utm_campaign' : sanitize_text_field(wp_unslash($_POST['column']));
		if (!in_array($utm_event_tracker_column, array('utm_campaign', 'utm_source', 'utm_term', 'utm_medium', 'utm_content'))) {
			$utm_event_tracker_column = 'utm_campaign';
		}

		$offset = ($page_no - 1) * $per_page;

		$sort_column = !empty($_POST['sort_column']) ? sanitize_text_field(wp_unslash($_POST['sort_column'])) : 'created_on';
		if (!in_array($sort_column, array('utm_campaign', 'utm_medium', 'utm_source', 'utm_term', 'utm_content', 'city', 'region', 'country', 'total_views', 'total_events'))) {
			$sort_column = 'created_on';
		}

		$keywords = !empty($_POST['keywords']) ? sanitize_text_field(wp_unslash($_POST['keywords'])) : '';

		$sort_type = !empty($_POST['sort_type']) ? sanitize_text_field(wp_unslash($_POST['sort_type'])) : 'DESC';
		if (!in_array($sort_type, array('ASC', 'DESC'))) {
			$sort_type = 'DESC';
		}

		$select_sql = $wpdb->prepare("SELECT id FROM %i sessions_table", $wpdb->utm_event_tracker_sessions_table);
		$where_sql = $wpdb->prepare("WHERE sessions_table.created_on BETWEEN %s AND %s", $start_date, $end_date);
		$order_sql = $wpdb->prepare("ORDER BY sessions_table.%i DESC ", $sort_column);
		$limit_sql = $wpdb->prepare("LIMIT %d, %d", $offset, $per_page);

		if (!empty($keywords)) {
			$where_sql .= $wpdb->prepare(" AND sessions_table.%i LIKE %s", $utm_event_tracker_column, '%' . $wpdb->esc_like($keywords) . '%');
		}

		if ('ASC' == $sort_type) {
			$order_sql = $wpdb->prepare(" ORDER BY sessions_table.%i ASC", $sort_column);
		}

		$this->update_stats_table($sort_column);
		$select_sql .= $wpdb->prepare(" LEFT JOIN %i stats ON sessions_table.id = stats.session_id", $wpdb->utm_event_tracker_stats_table);

		if ('total_views' == $sort_column) {
			$order_sql = "ORDER BY stats.views DESC";
			if ('ASC' == $sort_type) {
				$order_sql = "ORDER BY stats.views ASC";
			}
		}

		if ('total_events' == $sort_column) {
			$order_sql = "ORDER BY stats.events DESC";
			if ('ASC' == $sort_type) {
				$order_sql = "ORDER BY stats.views ASC";
			}
		}

		$concated_sql = $select_sql . " " . $where_sql . " " . $order_sql . " " . $limit_sql;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$session_ids = $wpdb->get_col($concated_sql);
		if (count($session_ids) == 0) {
			$session_ids = array(0);
		}

		$session_ids_in_sql = esc_sql(implode(',', $session_ids));
		$ids_string = esc_sql(implode(',', array_map('intval', $session_ids)));

		$prepared_sql = $wpdb->prepare(
			"SELECT sessions.*, 
                %i AS keyword,
                COALESCE(views.views, 0) AS total_views, views.view_items,
                COALESCE(events.events, 0) AS total_events, events.event_items
			FROM %i as sessions
			LEFT JOIN (
				SELECT session_id, COUNT(*) as views, 
				JSON_ARRAYAGG(JSON_OBJECT('session_id', session_id, 'landing_page', landing_page)) as view_items
				FROM %i 
				WHERE session_id IN ($session_ids_in_sql)
				GROUP BY session_id
			) as views ON sessions.id = views.session_id
			LEFT JOIN (
				SELECT session_id, COUNT(*) as events,
				JSON_ARRAYAGG(
					JSON_OBJECT(
						'type', type, 
						'title', title, 
						'currency', currency, 
						'amount', amount, 
						'meta_data', meta_data, 
						'created_on', created_on
					)
				) as event_items
				FROM %i 
				WHERE session_id IN ($session_ids_in_sql)
				GROUP BY session_id
			) as events ON sessions.id = events.session_id
			WHERE sessions.id IN ($session_ids_in_sql)
			ORDER BY FIELD(sessions.id, $ids_string)",
			$utm_event_tracker_column,
			$wpdb->utm_event_tracker_sessions_table,
			$wpdb->utm_event_tracker_views_table,
			$wpdb->utm_event_tracker_events_table,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results($prepared_sql);

		$one_week_ago = strtotime('-1 week');

		$parameters = array_keys(Utils::get_utm_parameters());

		array_walk($items, function (&$item) use ($one_week_ago, $wpdb, $parameters) {
			$session_object = new Session((array) $item);

			$na_text = esc_html__('N/A', 'utm-event-tracker-and-analytics');
			foreach ($parameters as $param) {
				if (empty($item->{$param})) {
					$item->{$param} = $na_text;
				}
			}

			if ($item->keyword) {
				$item->keyword = html_entity_decode($item->keyword);
			}

			if (empty($item->keyword)) {
				$item->keyword = $na_text;
			}

			$item->country = Utils::get_country_name($item->country);

			$item->timestamp = Utils::get_date($item->created_on, true);
			$item->readable_time = human_time_diff($item->timestamp, current_time('timestamp')) . ' ' . __('ago', 'utm-event-tracker-and-analytics');
			$item->session_date = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $item->timestamp);
			$item->show_readable_time = ($item->timestamp > $one_week_ago);

			$last_online_timestamp = Utils::get_date($item->last_online, true);
			$item->last_online_date = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $last_online_timestamp);
			$item->last_online_readable_time = human_time_diff($last_online_timestamp, current_time('timestamp')) . ' ' . __('ago', 'utm-event-tracker-and-analytics');
			$item->show_last_online_readable_time = ($last_online_timestamp > $one_week_ago);

			$item->landing_page_url = home_url($item->landing_page);

			$view_items = Utils::json_string_to_array($item->view_items);
			unset($item->view_items);
			$item->journey = array_map(function ($item) {
				$item['landing_page_url'] = home_url($item['landing_page']);
				if ('/' === $item['landing_page']) {
					$item['landing_page'] = site_url();
				}

				return $item;
			}, $view_items);

			$event_items = Utils::json_string_to_array($item->event_items);
			if (!function_exists('utm_event_tracker_fs') || !utm_event_tracker_fs()->can_use_premium_code()) {
				$event_items = array_splice($event_items, 0, 2);
			}

			unset($item->event_items);
			$item->events = array_map(function ($event_data) {
				$event = new Event($event_data);
				$event_data['description'] = $event->get_description();
				return $event_data;
			}, $event_items);

			$item = apply_filters('utm_event_tracker/get_sessions/session_item', $item, $session_object);
		});

		$prepared_sql = $wpdb->prepare("SELECT count(*) as total FROM %i as sessions_table", $wpdb->utm_event_tracker_sessions_table);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$total_items = $wpdb->get_var($prepared_sql . " " . $where_sql);
		wp_send_json_success(array('items' => $items, 'total' => absint($total_items)));
	}

	/**
	 * Get keywords based report
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_keywords_report() {
		global $wpdb;

		$result = check_ajax_referer('_nonce_utm_overview_widget', false, false);
		if (false === $result) {
			wp_send_json_error(array(
				'error' => __('Security failed.', 'utm-event-tracker-and-analytics')
			));
		}

		$supported_columns = array('utm_campaign', 'utm_medium', 'utm_term', 'utm_source', 'utm_content', 'fbclid', 'gclid');

		$utm_event_tracker_column = !empty($_POST['param']) ? sanitize_text_field(wp_unslash($_POST['param'])) : null;
		if (!in_array($utm_event_tracker_column, $supported_columns)) {
			wp_send_json_error(array(
				'error' => __('No supported parameter found.', 'utm-event-tracker-and-analytics')
			));
		}

		if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
			wp_send_json_error(array(
				'error' => __('Missing dates information.', 'utm-event-tracker-and-analytics')
			));
		}

		$start_date = gmdate('Y-m-d 00:00:00', strtotime(sanitize_text_field(wp_unslash($_POST['start_date']))));
		$end_date = gmdate('Y-m-d 23:59:59', strtotime(sanitize_text_field(wp_unslash($_POST['end_date']))));

		$session_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT id FROM $wpdb->utm_event_tracker_sessions_table sessions
			WHERE %i != '' AND created_on BETWEEN %s AND %s",
			$utm_event_tracker_column,
			$start_date,
			$end_date
		));

		if (count($session_ids) == 0) {
			$session_ids = array(0);
		}

		$session_ids_sql = esc_sql(implode(',', $session_ids));

		$view_table = $wpdb->prepare(
			"SELECT v.session_id, count(*) as views FROM $wpdb->utm_event_tracker_views_table v
			 INNER JOIN $wpdb->utm_event_tracker_sessions_table vs ON v.session_id = vs.id
			 WHERE vs.%i != '' AND v.session_id IN ($session_ids_sql) AND vs.created_on BETWEEN %s AND %s
			 GROUP BY v.session_id",
			$utm_event_tracker_column,
			$start_date,
			$end_date,
		);

		$event_table = $wpdb->prepare(
			"SELECT e.session_id, count(*) as events FROM $wpdb->utm_event_tracker_events_table e
			 INNER JOIN $wpdb->utm_event_tracker_sessions_table es ON e.session_id = es.id
			 WHERE es.%i != '' AND e.session_id IN ($session_ids_sql) AND es.created_on BETWEEN %s AND %s
			 GROUP BY e.session_id",
			$utm_event_tracker_column,
			$start_date,
			$end_date,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT %i AS keyword, count(*) AS sessions, COALESCE(sum(views.views), 0) AS views, COALESCE(sum(events.events), 0) AS events
			FROM $wpdb->utm_event_tracker_sessions_table as sessions
			LEFT JOIN ($view_table) as views ON sessions.id = views.session_id
			LEFT JOIN ($event_table) as events ON sessions.id = events.session_id
			WHERE sessions.id IN ($session_ids_sql)
			GROUP BY %i ORDER BY sessions DESC",
			$utm_event_tracker_column,
			$utm_event_tracker_column,
			$utm_event_tracker_column
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results($sql);
		array_walk($results, fn(&$item) => $item->keyword = html_entity_decode($item->keyword));
		wp_send_json_success($results);
	}

	/**
	 * Get date wise report for each keyword
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_date_report() {
		$result = check_ajax_referer('_nonce_utm_overview_widget', false, false);
		if (false === $result) {
			wp_send_json_error(array(
				'error' => __('Security failed.', 'utm-event-tracker-and-analytics')
			));
		}

		if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
			wp_send_json_error(array(
				'error' => __('Missing dates information.', 'utm-event-tracker-and-analytics')
			));
		}

		if (empty($_POST['param'])) {
			wp_send_json_error(array(
				'error' => __('Missing parameter', 'utm-event-tracker-and-analytics')
			));
		}

		$start_date = gmdate('Y-m-d 00:00:00', strtotime(sanitize_text_field(wp_unslash($_POST['start_date']))));
		$end_date = gmdate('Y-m-d 23:59:59', strtotime(sanitize_text_field(wp_unslash($_POST['end_date']))));

		global $wpdb;
		$utm_event_tracker_column = sanitize_text_field(wp_unslash($_POST['param']));

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT DATE(created_on) date, count(*) AS sessions, IFNULL(sum(views.views), 0) AS views, IFNULL(sum(events.events), 0) AS events
			FROM $wpdb->utm_event_tracker_sessions_table as sessions
			LEFT JOIN (
				SELECT session_id, count(*) as views FROM $wpdb->utm_event_tracker_views_table GROUP BY session_id
			) as views ON sessions.id = views.session_id
			LEFT JOIN (
				SELECT session_id, count(*) as events FROM $wpdb->utm_event_tracker_events_table GROUP BY session_id
			) as events ON sessions.id = events.session_id
			WHERE %i != '' AND created_on BETWEEN %s AND %s
			GROUP BY date ORDER BY date DESC",
			$utm_event_tracker_column,
			$start_date,
			$end_date
		));

		wp_send_json_success($results);
	}

	/**
	 * Get keywords stats
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_keywords_stats() {
		global $wpdb;

		$result = check_ajax_referer('_nonce_utm_keywords_stats', false, false);
		if (false === $result) {
			wp_send_json_error(array(
				'error' => __('Security failed.', 'utm-event-tracker-and-analytics')
			));
		}

		$stats_type = !empty($_POST['stats_type']) ? sanitize_text_field(wp_unslash($_POST['stats_type'])) : null;
		if (!in_array($stats_type, array('session', 'view', 'conversion'))) {
			wp_send_json_error(array(
				'error' => __('Keywords stats type is missing.', 'utm-event-tracker-and-analytics')
			));
		}

		$utm_event_tracker_column = !empty($_POST['parameter']) ? sanitize_text_field(wp_unslash($_POST['parameter'])) : null;
		$supported_columns = array('utm_campaign', 'utm_medium', 'utm_term', 'utm_source', 'utm_content');

		if (!in_array($utm_event_tracker_column, $supported_columns)) {
			wp_send_json_error(array(
				'error' => __('No supported parameter found.', 'utm-event-tracker-and-analytics')
			));
		}

		if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
			wp_send_json_error(array(
				'error' => __('Missing dates information.', 'utm-event-tracker-and-analytics')
			));
		}

		$start_date = gmdate('Y-m-d 00:00:00', strtotime(sanitize_text_field(wp_unslash($_POST['start_date']))));
		$end_date = gmdate('Y-m-d 23:59:59', strtotime(sanitize_text_field(wp_unslash($_POST['end_date']))));

		global $wpdb;

		if ('session' === $stats_type) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$keywords = $wpdb->get_results($wpdb->prepare(
				"SELECT %i AS keyword, count(*) AS quantity FROM $wpdb->utm_event_tracker_sessions_table as sessions
				WHERE %i != '' AND created_on BETWEEN %s AND %s
				GROUP BY keyword HAVING quantity > 0 ORDER BY quantity DESC LIMIT 5",
				$utm_event_tracker_column,
				$utm_event_tracker_column,
				$start_date,
				$end_date
			));
		}

		if ('view' === $stats_type) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$keywords = $wpdb->get_results($wpdb->prepare(
				"SELECT %i AS keyword, COALESCE(views.views, 0) AS quantity FROM $wpdb->utm_event_tracker_sessions_table as sessions
				INNER JOIN (
					SELECT session_id, count(*) as views FROM $wpdb->utm_event_tracker_views_table GROUP BY session_id
				) as views ON sessions.id = views.session_id
				WHERE %i != '' AND created_on BETWEEN %s AND %s
				GROUP BY keyword HAVING quantity > 0 ORDER BY quantity DESC LIMIT 5",
				$utm_event_tracker_column,
				$utm_event_tracker_column,
				$start_date,
				$end_date
			));
		}

		if ('conversion' === $stats_type) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$keywords = $wpdb->get_results($wpdb->prepare(
				"SELECT %i AS keyword, COALESCE(events.events, 0) AS quantity FROM $wpdb->utm_event_tracker_sessions_table as sessions
				INNER JOIN (
					SELECT session_id, count(*) as events FROM $wpdb->utm_event_tracker_events_table GROUP BY session_id
				) as events ON sessions.id = events.session_id
				WHERE %i != '' AND created_on BETWEEN %s AND %s
				GROUP BY keyword HAVING quantity > 0 ORDER BY quantity DESC LIMIT 5",
				$utm_event_tracker_column,
				$utm_event_tracker_column,
				$start_date,
				$end_date
			));
		}


		if (!is_array($keywords)) {
			$keywords = [];
		}

		$total_quantity = array_sum(wp_list_pluck($keywords, 'quantity'));
		if ($total_quantity <= 0) {
			$total_quantity = 1;
		}

		array_walk($keywords, function (&$keyword) use ($total_quantity) {
			$keyword->keyword = html_entity_decode($keyword->keyword);
			$keyword->percentage = round(($keyword->quantity * 100) / $total_quantity, 2);
		});

		wp_send_json_success($keywords);
	}

	/**
	 * Save overview settings
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function update_overview_settings() {
		check_ajax_referer('_nonce_utm_event_tracker_overview_settings');

		$hide_widgets = isset($_POST['hide_widgets']) && is_array($_POST['hide_widgets']) ? array_map('sanitize_text_field', wp_unslash($_POST['hide_widgets'])) : [];
		update_option('utm_event_tracker_overview_settings', array(
			'hide_widgets' => $hide_widgets
		));
		wp_send_json_success();
	}
}

new Query();
