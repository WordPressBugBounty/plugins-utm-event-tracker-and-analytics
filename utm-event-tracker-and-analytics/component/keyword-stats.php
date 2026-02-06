<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly 
}

?>

<div :class="{'utm-event-tracker-keyword-stats': true, loading: loading, 'utm-event-tracker-keyword-stats-empty': keywords.length == 0}">
	<input ref="nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('_nonce_utm_keywords_stats')); ?>">

	<template v-if="keywords.length == 0">
		<div><?php esc_html_e('No keyword is available.', 'utm-event-tracker-and-analytics'); ?></div>
	</template>

	<template v-else>
		<slot name="heading" :count="keywords.length"></slot>

		<ul class="slider" v-if="get_keywords_stats.length > 0">
			<li v-for="(item, i) in get_keywords_stats" :style="{'background-color': get_color(i), width: item.percentage + '%'}"></li>
		</ul>

		<ul class="utm-top-five-keywords-list">
			<li v-for="(item, i) in get_keywords_stats"><span class="circle" :style="{'background-color': get_color(i)}"></span> {{item.keyword}} <span class="percentage">{{item.percentage}}%</span></li>
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