<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly 
}
?>

<div class="utm-event-tracker-header">
	<h3><?php esc_html_e('UTM Medium', 'utm-event-tracker-and-analytics'); ?></h3>
</div>
<div id="utm-medium-analysis-dashboard" class="wrap wrap-utm-event-tracker">
	<hr class="wp-header-end">

	<div class="utm-report-filter-row">
		<div class="left-column">
			<input class="filter-keyword" type="text" placeholder="<?php esc_html_e('Search keywords...', 'utm-event-tracker-and-analytics'); ?>" v-model="keywords">
		</div>
		<input ref="datepicker" type="text" class="utm-event-tracker-date-picker-input">
		<span class="btn-reload dashicons dashicons-update" @click="reload()"></span>
	</div>

	<div class="utm-event-tracker-keyword-stats-container">
		<keyword-stats param="utm_medium" type="session" :dates="dates">
			<template #heading="{count}">
				<h4><?php esc_html_e('Top {{count}} UTM Mediums by Sessions', 'utm-event-tracker-and-analytics'); ?></h4>
			</template>
		</keyword-stats>

		<keyword-stats param="utm_medium" type="view" :dates="dates">
			<template #heading="{count}">
				<h4><?php esc_html_e('Top {{count}} UTM Mediums by Views', 'utm-event-tracker-and-analytics'); ?></h4>
			</template>
		</keyword-stats>

		<keyword-stats param="utm_medium" type="conversion" :dates="dates">
			<template #heading="{count}">
				<h4><?php esc_html_e('Top {{count}} UTM Mediums by Conversions', 'utm-event-tracker-and-analytics'); ?></h4>
			</template>
		</keyword-stats>
	</div>

	<session-list-param ref="keyword_list_table" column="utm_medium" :dates="dates" :keywords="keywords"></utm-event-tracker-session-list-param>
</div>