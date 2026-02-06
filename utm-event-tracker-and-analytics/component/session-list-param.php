<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly 
}

use UTM_Event_Tracker\Settings;

$utm_event_tracker_ipinfo_token = Settings::get_instance()->get('ipinfo_token');

$utm_event_tracker_api_notice = sprintf(
	/* translators: 1 for settings page URL */
	__('Please enter your IP info token on the %s.', 'utm-event-tracker-and-analytics'),
	'<a target="_blank" href="' . menu_page_url('utm-event-tracker-settings', false) . '">settings page</a>'
);

?>

<input ref="nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('_nonce_session_list_keywords')); ?>">

<table :class="['table-utm-event-tracker-report', {loading: loading}]">
	<thead>
		<tr>
			<th><?php esc_html_e('Keywords', 'utm-event-tracker-and-analytics'); ?></th>
			<th :class="['column-date', 'sortable-column', get_sort_column_class('last_online')]" @click="sort_report('last_online')"><?php esc_html_e('Last Online', 'utm-event-tracker-and-analytics'); ?></th>
			<th :class="['column-date', 'sortable-column', get_sort_column_class('created_on')]" @click="sort_report('created_on')"><?php esc_html_e('Date', 'utm-event-tracker-and-analytics'); ?></th>
			<th :class="['column-city', 'sortable-column', get_sort_column_class('city')]" @click="sort_report('city')"><?php esc_html_e('City', 'utm-event-tracker-and-analytics'); ?></th>
			<th :class="['column-region', 'sortable-column', get_sort_column_class('region')]" @click="sort_report('region')"><?php esc_html_e('Region', 'utm-event-tracker-and-analytics'); ?></th>
			<th :class="['column-country', 'sortable-column', get_sort_column_class('country')]" @click="sort_report('country')"><?php esc_html_e('Country', 'utm-event-tracker-and-analytics'); ?></th>
			<th :class="['column-100', 'sortable-column', get_sort_column_class('total_views')]" @click="sort_report('total_views')"><?php esc_html_e('Views', 'utm-event-tracker-and-analytics'); ?></th>
			<th :class="['column-100', 'sortable-column', get_sort_column_class('total_events')]" @click="sort_report('total_events')"><?php esc_html_e('Events', 'utm-event-tracker-and-analytics'); ?></th>
			<th style="width: 1%;"></th>
		</tr>
	</thead>

	<tbody>
		<tr class="no-record" v-if="is_empty && !loading">
			<td colspan="10"><?php esc_html_e('No data available for display.', 'utm-event-tracker-and-analytics'); ?></td>
		</tr>

		<tr v-for="item in get_data">
			<td class="column-keyword">{{ item.keyword }}</td>

			<td>
				<span v-if="item.show_last_online_readable_time" class="readable-time">{{item.last_online_readable_time}}</span>
				{{ item.last_online_date }}
			</td>

			<td class="session-date">
				<span v-if="item.show_readable_time" class="readable-time">{{item.readable_time}}</span>
				{{ item.session_date }}
			</td>

			<?php if (empty($utm_event_tracker_ipinfo_token)) : ?>
				<td colspan="3"><?php echo wp_kses($utm_event_tracker_api_notice, array('a' => array('href' => true, 'target' => true))); ?></td>
			<?php else : ?>
				<td>{{ item.city }}</td>
				<td>{{ item.region }}</td>
				<td>{{ item.country }}</td>
			<?php endif; ?>

			<td>{{ item.total_views }}</td>
			<td>{{ item.total_events }}</td>
			<td><a @click.prevent.stop="set_session(item.id)" href="#" class="btn-view-session-details utm-event-tracker-icon-eye"></a></td>
		</tr>
	</tbody>
</table>

<session-summary @close="close_summary" v-if="view_session != null" :session="view_session"></session-summary>

<div class="utm-gap-20"></div>

<utm-event-tracker-pagination @change-page="updatePagination" :total-items="total_items" :current-page="page" :per-page="per_page"></utm-event-tracker-pagination>