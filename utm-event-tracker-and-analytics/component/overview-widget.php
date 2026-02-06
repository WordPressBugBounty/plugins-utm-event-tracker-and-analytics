<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly 
}
?>
<div class="utm-event-tracker-widget-item">
	<div class="header">
		<slot name="header_left"></slot>

		<div class="actions">
			<select v-model="type">
				<option value="keywords"><?php esc_html_e('By Keywords', 'utm-event-tracker-and-analytics'); ?></option>
				<option value="date"><?php esc_html_e('By Date', 'utm-event-tracker-and-analytics'); ?></option>
			</select>

			<div class="utm-event-tracker-date-picker" ref="datepicker">
				<i class="dashicons dashicons-calendar-alt"></i> {{get_date_text()}}
			</div>

			<span class="btn-reload utm-event-tracker-icon-rotate" @click="fetch_data()"></span>

			<input type="hidden" ref="nonce" value="<?php echo esc_attr(wp_create_nonce('_nonce_utm_overview_widget')); ?>">
		</div>
	</div>

	<div class="content-body">
		<table :class="{'table-utm-event-tracker-report no-margin-top': true, loading: loading}">
			<thead>
				<tr v-if="type == 'keywords'">
					<th class="column-large"><?php esc_html_e('Keywords', 'utm-event-tracker-and-analytics'); ?></th>
					<th :class="['column-100', 'sortable-column', get_sort_column_class('sessions')]" @click="sort_report('sessions')"><?php esc_html_e('Sessions', 'utm-event-tracker-and-analytics'); ?></th>
					<th :class="['column-100', 'sortable-column', get_sort_column_class('views')]" @click="sort_report('views')"><?php esc_html_e('Views', 'utm-event-tracker-and-analytics'); ?></th>
					<th :class="['column-100', 'sortable-column', get_sort_column_class('events')]" @click="sort_report('events')"><?php esc_html_e('Events', 'utm-event-tracker-and-analytics'); ?></th>
				</tr>

				<tr v-if="type == 'date'">
					<th :class="['sortable-column', get_sort_column_class('date')]" @click="sort_report('date')"><?php esc_html_e('Date', 'utm-event-tracker-and-analytics'); ?></th>
					<th :class="['column-100', 'sortable-column', get_sort_column_class('sessions')]" @click="sort_report('sessions')"><?php esc_html_e('Sessions', 'utm-event-tracker-and-analytics'); ?></th>
					<th :class="['column-100', 'sortable-column', get_sort_column_class('views')]" @click="sort_report('views')"><?php esc_html_e('Views', 'utm-event-tracker-and-analytics'); ?></th>
					<th :class="['column-100', 'sortable-column', get_sort_column_class('events')]" @click="sort_report('events')"><?php esc_html_e('Events', 'utm-event-tracker-and-analytics'); ?></th>
				</tr>
			</thead>

			<tbody>

				<tr class="no-record" v-if="is_empty && !loading">
					<td colspan="10"><?php esc_html_e('No data available for display.', 'utm-event-tracker-and-analytics'); ?></td>
				</tr>

				<template v-if="type == 'keywords'">
					<tr v-for="item in get_report">
						<td>{{ item.keyword }}</td>
						<td>{{ item.sessions }}</td>
						<td>{{ item.views }}</td>
						<td>{{ item.events }}</td>
					</tr>
				</template>

				<template v-if="type == 'date'">
					<tr v-for="item in get_report">
						<td>{{ item.date }}</td>
						<td>{{ item.sessions }}</td>
						<td>{{ item.views }}</td>
						<td>{{ item.events }}</td>
					</tr>
				</template>

			</tbody>
		</table>
	</div>
</div>