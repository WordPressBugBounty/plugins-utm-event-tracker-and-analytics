<?php

namespace UTM_Event_Tracker\Admin;

use UTM_Event_Tracker\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Event class
 */
final class Sessoin_Event {

	/** 
	 * Constructor 
	 * 
	 * @since 1.1.2
	 */
	public function __construct() {
		add_action('admin_footer', [$this, 'events_components']);
		add_action('utm_event_tracker/admin_menu', [$this, 'admin_menu'], 5);
		add_filter('utm_event_tracker/dashboard_widgets', [$this, 'dashboard_widget'], 5);

		add_action('wp_ajax_utm_event_tracker/get_events_list', array($this, 'get_events_list'));
		add_action('wp_ajax_utm_event_tracker/get_events_stats', array($this, 'get_events_stats'));
		add_action('wp_ajax_utm_event_tracker/get_event_overview_query', array($this, 'get_event_overview'));
		add_action('wp_ajax_utm_event_tracker/get_event_date_overview_data', array($this, 'get_event_date_overview_data'));
	}

	/**
	 * Add component templates for vuejs
	 * 
	 * @since 1.1.2
	 */
	public function events_components() { ?>
		<template id="utm-event-tracker-session-list-events">
			<?php include_once UTM_EVENT_TRACKER_PATH . '/component/session-list-events.php'; ?>
		</template>

		<template id="utm-event-tracker-widget-event">
			<div class="utm-event-tracker-widget-item">
				<div class="header">
					<slot name="header_left"></slot>

					<div class="actions">
						<select v-model="type">
							<option value="events"><?php esc_html_e('By Events', 'utm-event-tracker-and-analytics'); ?></option>
							<option value="date"><?php esc_html_e('By Date', 'utm-event-tracker-and-analytics'); ?></option>
						</select>

						<div class="utm-event-tracker-date-picker" ref="datepicker">
							<i class="dashicons dashicons-calendar-alt"></i> {{get_date_text()}}
						</div>

						<span class="btn-reload utm-event-tracker-icon-rotate" @click="fetch_data()"></span>

						<input type="hidden" ref="nonce" value="<?php echo esc_attr(wp_create_nonce('_nonce_event_overview')); ?>">
					</div>
				</div>

				<div class="content-body">
					<table :class="{'table-utm-event-tracker-report no-margin-top': true, loading: loading}">
						<thead>
							<tr v-if="type == 'events'">
								<th class="column-large"><?php esc_html_e('Event Title', 'utm-event-tracker-and-analytics'); ?></th>
								<th :class="['column-100', 'sortable-column', get_sort_column_class('events')]" @click="sort_report('events')"><?php esc_html_e('Total', 'utm-event-tracker-and-analytics'); ?></th>
							</tr>

							<tr v-if="type == 'date'">
								<th :class="['column-large', 'sortable-column', get_sort_column_class('date')]" @click="sort_report('date')"><?php esc_html_e('Date', 'utm-event-tracker-and-analytics'); ?></th>
								<th :class="['sortable-column', get_sort_column_class('events')]" @click="sort_report('events')"><?php esc_html_e('Events', 'utm-event-tracker-and-analytics'); ?></th>
							</tr>
						</thead>

						<tbody>

							<tr class="no-record" v-if="is_empty && !loading">
								<td colspan="10"><?php esc_html_e('No data available for display.', 'utm-event-tracker-and-analytics'); ?></td>
							</tr>

							<template v-if="type == 'events'">
								<tr v-for="item in get_report">
									<td>{{ item.title }} - ({{ item.type }})</td>
									<td>{{ item.events }}</td>
								</tr>
							</template>

							<template v-if="type == 'date'">
								<tr v-for="item in get_report">
									<td>{{ item.date }}</td>
									<td>{{ item.events }}</td>
								</tr>
							</template>
						</tbody>
					</table>
				</div>
			</div>
		</template>

		<template id="utm-event-tracker-events-stats">
			<div :class="{'utm-event-tracker-keyword-stats': true, loading: loading, 'utm-event-tracker-keyword-stats-empty': keywords.length == 0}">
				<input ref="nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('_nonce_events_stats')); ?>">

				<template v-if="keywords.length == 0">
					<div><?php esc_html_e('No keyword is available.', 'utm-event-tracker-and-analytics'); ?></div>
				</template>

				<template v-else>
					<slot name="heading" :count="keywords.length"></slot>

					<ul class="slider" v-if="get_keywords_stats.length > 0">
						<li v-for="(item, i) in get_keywords_stats" :style="{'background-color': get_color(i), width: item.percentage + '%'}"></li>
					</ul>

					<ul class="utm-top-five-keywords-list">
						<li v-for="(item, i) in get_keywords_stats"><span class="circle" :style="{'background-color': get_color(i)}"></span> {{item.keyword}} <span class="percentage">{{item.quantity}}</span></li>
						<li class="keyword-lock" v-if="hided_keywords_count > 0">
							<?php esc_html_e('Get the pro version for unlocking more {{hided_keywords_count}} keywords.', 'utm-event-tracker-and-analytics'); ?>
							<br>
							<a class="btn-utm-event-tracker-get-pro" target="_blank" href="https://codiepress.com/plugins/utm-event-tracker-and-analytics-pro/?utm_campaign=utm+event+tracker&utm_source=plugin&utm_medium=stats+widget"><?php esc_html_e('Get Pro', 'utm-event-tracker-and-analytics'); ?></a>
						</li>
					</ul>

					<p class="utm-error" v-if="error !== null">{{error}}</p>

					<div v-if="showReportDate" class="date-time">
						<?php esc_html_e('Dates', 'utm-event-tracker-and-analytics'); ?>: <strong>{{get_date}}</strong>
					</div>
				</template>
			</div>
		</template>
	<?php
	}

	/**
	 * Register submneu page for UTM campaign
	 * 
	 * @since 1.1.2
	 */
	public function admin_menu() {
		add_submenu_page(
			'utm-event-tracker',
			__('UTM Event Tracker - Events', 'utm-event-tracker-and-analytics'),
			__('Events', 'utm-event-tracker-and-analytics'),
			'manage_categories',
			'utm-event-tracker-events',
			array($this, 'screen'),
			10
		);
	}

	/**
	 * Events report
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function screen() { ?>
		<div class="utm-event-tracker-header">
			<h3><?php esc_html_e('Events', 'utm-event-tracker-and-analytics'); ?></h3>
		</div>

		<div id="events-analysis-dashboard" class="wrap wrap-utm-event-tracker">
			<hr class="wp-header-end">

			<div class="utm-report-filter-row">
				<div class="left-column">
					<input class="filter-keyword" type="text" placeholder="<?php esc_html_e('Search events...', 'utm-event-tracker-and-analytics'); ?>" v-model="keywords">
				</div>
				<input ref="datepicker" type="text" class="utm-event-tracker-date-picker-input">
				<span class="btn-reload dashicons dashicons-update" @click="reload()"></span>
			</div>

			<div class="utm-event-tracker-keyword-stats-container">
				<events-stats :dates="dates">
					<template #heading="{count}">
						<h4><?php esc_html_e('Top {{count}} Events', 'utm-event-tracker-and-analytics'); ?></h4>
					</template>
				</events-stats>
			</div>

			<session-list-events ref="keyword_list_table" :dates="dates" :keywords="keywords"></session-list-events>
		</div>
<?php
	}

	/**
	 * Dashboard widget for short report
	 * 
	 * @since 1.1.2
	 * @return array
	 */
	public function dashboard_widget($widgets) {
		$widgets['session_event'] = array(
			'priority' => 25,
			'placement' => 'right',
			'callback' => array($this, 'widget'),
			'title' => __('Events', 'utm-event-tracker-and-analytics'),
		);

		return $widgets;
	}

	/**
	 * Widget template
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function widget() {
		echo '<overview-widget-event param="events" v-if="!widget_is_visible(\'events\')">';
		echo '<template v-slot:header_left>';
		echo '<h3>' . esc_html__('Events', 'utm-event-tracker-and-analytics') . '</h3>';
		echo '</template>';
		echo '</overview-widget-event>';
	}

	/**
	 * Get overview result for event
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function get_events_list() {
		check_ajax_referer('_nonce_session_list_events', '_nonce');

		if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
			wp_send_json_error(array(
				'error' => esc_html__('Date parameter missing', 'utm-event-tracker-and-analytics')
			));
		}

		$start_date = gmdate('Y-m-d 00:00:00', strtotime(sanitize_text_field(wp_unslash($_POST['start_date']))));
		$end_date = gmdate('Y-m-d 23:59:59', strtotime(sanitize_text_field(wp_unslash($_POST['end_date']))));
		$per_page = isset($_POST['per_page']) ? absint(wp_unslash($_POST['per_page'])) : 20;
		$page_no = isset($_POST['page']) ? absint(wp_unslash($_POST['page'])) : 1;

		global $wpdb;

		$offset = ($page_no - 1) * $per_page;

		$keywords = !empty($_POST['keywords']) ? sanitize_text_field(wp_unslash($_POST['keywords'])) : '';

		$sort_type = !empty($_POST['sort_type']) ? sanitize_text_field(wp_unslash($_POST['sort_type'])) : 'DESC';
		if (!in_array($sort_type, array('ASC', 'DESC'))) {
			$sort_type = 'DESC';
		}

		$sort_column = !empty($_POST['sort_column']) ? sanitize_text_field(wp_unslash($_POST['sort_column'])) : 'created_on';
		if (!in_array($sort_column, array('title', 'utm_campaign', 'utm_medium', 'utm_source', 'utm_term', 'utm_content', 'city', 'region', 'country', 'total_views', 'total_events'))) {
			$sort_column = 'created_on';
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$items = $wpdb->get_results($wpdb->prepare(
			"SELECT events.*, sessions.id as id,
			utm_campaign, utm_medium, utm_source, utm_term, utm_content, fbclid, city, gclid, landing_page, referrer, region, country,
			IFNULL(views.views, 0) AS total_views, IFNULL(total_events.events, 0) AS total_events
			FROM $wpdb->utm_event_tracker_events_table as events
			LEFT JOIN $wpdb->utm_event_tracker_sessions_table as sessions ON events.session_id = sessions.id

			LEFT JOIN (
				SELECT session_id, count(*) as views FROM $wpdb->utm_event_tracker_views_table GROUP BY session_id
			) as views ON events.session_id = views.session_id

			LEFT JOIN (
				SELECT session_id, count(*) as events FROM $wpdb->utm_event_tracker_events_table GROUP BY session_id
			) as total_events ON events.session_id = total_events.session_id

			WHERE events.title LIKE %s AND events.created_on BETWEEN %s AND %s ORDER BY %i %5s LIMIT %d, %d", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
			'%' . $wpdb->esc_like($keywords) . '%',
			$start_date,
			$end_date,
			$sort_column,
			$sort_type,
			$offset,
			$per_page
		));


		$one_week_ago = strtotime('-1 week');

		$parameters = array_keys(Utils::get_utm_parameters());

		array_walk($items, function (&$item) use ($one_week_ago, $wpdb, $parameters) {
			$na_text = esc_html__('N/A', 'utm-event-tracker-and-analytics');
			foreach ($parameters as $param) {
				if (empty($item->{$param})) {
					$item->{$param} = $na_text;
				}

				$item->{$param} = html_entity_decode($item->{$param});
			}

			$item->country = Utils::get_country_name($item->country);

			$item->timestamp = Utils::get_date($item->created_on, true);
			$item->readable_time = human_time_diff($item->timestamp, current_time('timestamp')) . ' ' . __('ago', 'utm-event-tracker-and-analytics');
			$item->session_date = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $item->timestamp);
			$item->show_readable_time = ($item->timestamp > $one_week_ago);
			$item->landing_page_url = home_url($item->landing_page);

			$item->journey = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->utm_event_tracker_views_table WHERE session_id = %d", $item->id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			array_walk($item->journey, function (&$item) {
				$item->landing_page_url = home_url($item->landing_page);
			});

			$item->events = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->utm_event_tracker_events_table WHERE session_id = %d ORDER BY created_on DESC LIMIT 0, 2", $item->id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			array_walk($item->events, function (&$event_data) {
				$event = new \UTM_Event_Tracker\Event($event_data);
				$event_data->description = $event->get_description();
			});

			$item = apply_filters('utm_event_tracker/get_sessions/session_item', $item);
		});

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_items = $wpdb->get_var($wpdb->prepare(
			"SELECT count(*) as total_items
			FROM $wpdb->utm_event_tracker_events_table as events
			WHERE events.title LIKE %s AND created_on BETWEEN %s AND %s
			ORDER BY created_on DESC",
			'%' . $wpdb->esc_like($keywords) . '%',
			$start_date,
			$end_date
		));

		wp_send_json_success(array(
			'items' => $items,
			'total' => absint($total_items)
		));
	}

	/**
	 * Get event stats
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function get_events_stats() {
		check_ajax_referer('_nonce_events_stats');

		if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
			wp_send_json_error(array(
				'error' => __('Missing dates information.', 'utm-event-tracker-and-analytics')
			));
		}

		$start_date = gmdate('Y-m-d 00:00:00', strtotime(sanitize_text_field(wp_unslash($_POST['start_date']))));
		$end_date = gmdate('Y-m-d 23:59:59', strtotime(sanitize_text_field(wp_unslash($_POST['end_date']))));

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT type, title AS keyword, count(*) AS quantity FROM $wpdb->utm_event_tracker_events_table as events
			WHERE created_on BETWEEN %s AND %s GROUP BY type, keyword 
			HAVING quantity > 0 ORDER BY quantity DESC LIMIT 5",
			$start_date,
			$end_date
		));

		if (!is_array($results)) {
			$results = [];
		}

		$total_quantity = array_sum(wp_list_pluck($results, 'quantity'));
		if ($total_quantity <= 0) {
			$total_quantity = 1;
		}

		array_walk($results, function (&$item) use ($total_quantity) {
			$item->keyword = html_entity_decode($item->keyword);
			$item->percentage = round(($item->quantity * 100) / $total_quantity, 2);
		});

		wp_send_json_success($results);
	}

	/**
	 * Get overview result for event
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function get_event_overview() {
		check_ajax_referer('_nonce_event_overview', '_nonce');

		if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
			wp_send_json_error(array(
				'error' => __('Missing dates information.', 'utm-event-tracker-and-analytics')
			));
		}

		$start_date = gmdate('Y-m-d 00:00:00', strtotime(sanitize_text_field(wp_unslash($_POST['start_date']))));
		$end_date = gmdate('Y-m-d 23:59:59', strtotime(sanitize_text_field(wp_unslash($_POST['end_date']))));

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT title, type, count(*) as events FROM $wpdb->utm_event_tracker_events_table as events
			WHERE created_on BETWEEN %s AND %s
			GROUP BY type, title LIMIT 5",
			$start_date,
			$end_date
		));

		$results = array_map(function ($item) {
			if (empty($item->title)) {
				$item->title = $item->type;
			}

			return $item;
		}, $results);

		wp_send_json_success($results);
	}

	/**
	 * Get event overview result by date
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function get_event_date_overview_data() {
		check_ajax_referer('_nonce_event_overview', '_nonce');

		if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
			wp_send_json_error(array(
				'error' => __('Missing dates information.', 'utm-event-tracker-and-analytics')
			));
		}

		$start_date = gmdate('Y-m-d 00:00:00', strtotime(sanitize_text_field(wp_unslash($_POST['start_date']))));
		$end_date = gmdate('Y-m-d 23:59:59', strtotime(sanitize_text_field(wp_unslash($_POST['end_date']))));

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT DATE(created_on) date, count(*) as events FROM $wpdb->utm_event_tracker_events_table as events
			WHERE created_on BETWEEN %s AND %s
			GROUP BY date LIMIT 5",
			$start_date,
			$end_date
		));

		wp_send_json_success($results);
	}
}
