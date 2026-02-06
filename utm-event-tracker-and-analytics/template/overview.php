<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

$utm_event_tracker_report_widgets = apply_filters('utm_event_tracker/dashboard_widgets', array());

$utm_event_tracker_report_widgets = array_filter($utm_event_tracker_report_widgets, function ($widget) {
	if (!isset($widget['callback'])) {
		return false;
	}

	return is_callable($widget['callback']);
});

$utm_event_tracker_widgets = array_map(function ($widget, $key) {
	if (empty($widget['title'])) {
		$widget['title'] = $key;
	}

	return wp_parse_args($widget, array('id' =>  $key, 'priority' => 10, 'placement' => 'top'));
}, $utm_event_tracker_report_widgets, array_keys($utm_event_tracker_report_widgets));

usort($utm_event_tracker_widgets, fn($a, $b) => $a['priority'] - $b['priority']);

$utm_event_tracker_overview_settings = get_option('utm_event_tracker_overview_settings', '');

$utm_event_tracker_dismiss_cache_notice = get_option('utm_event_tracker_dismiss_cache_notice') == 'yes';

$utm_event_tracker_available_parameters = implode(', ', array_map(fn($parameter) => '<code>' . $parameter . '</code>', array_keys(Utils::get_all_parameters()))) ?>

<div class="utm-event-tracker-header">
	<h3><?php esc_html_e('Overview', 'utm-event-tracker-and-analytics'); ?></h3>
</div>

<div id="utm-overview-container" class="wrap wrap-utm-event-tracker" data-settings='<?php echo wp_json_encode($utm_event_tracker_overview_settings); ?>'>
	<hr class="wp-header-end">

	<?php if (!$utm_event_tracker_dismiss_cache_notice) : ?>
		<div class="cache-notice">
			<h3><?php esc_html_e('Warning', 'utm-event-tracker-and-analytics') ?></h3>
			<ul class="cache-notice-list">
				<li>If you are using a caching plugin, ensure that the <code>wordpress_utm_event_tracker_session</code> cookie is excluded. For sites hosted on <strong>WP Engine</strong>, request their support team to allow the <code>wordpress_utm_event_tracker_session</code> cookie.</li>
				<?php if (defined('NITROPACK_BASENAME')) : ?>
					<li>If you are using the <strong>NitroPack</strong> caching plugin, exclude the <code>wordpress_utm_event_tracker_session</code> cookie in the app settings. <a target="_blank" href="https://ps.w.org/utm-event-tracker-and-analytics/assets/guideline-nitropack.png">see guideline</a></li>
				<?php endif; ?>

				<li>You can use <code>{utm_event_tracker:utm_source}</code> as the default value to retrieve the <strong>UTM Source</strong> for any kind of forms. To capture a different UTM parameter, simply replace <strong>utm_source</strong> with the desired parameter name. <br>Available parameters: <?php echo wp_kses_post($utm_event_tracker_available_parameters) ?>.</li>

				<li>If the issue persists, please submit a support ticket <a target="_blank" href="https://wordpress.org/support/plugin/utm-event-tracker-and-analytics/">here</a>.</li>
			</ul>

			<a class="button button-primary button-dismiss-notice" href="#" data-nonce="<?php echo esc_attr(wp_create_nonce('utm_event_tracker/dismiss_cache_notice_nonce')) ?>"><?php esc_html_e('Hide this notice', 'utm-event-tracker-and-analytics') ?></a>
		</div>
	<?php endif; ?>

	<input ref="nonce" type="hidden" value="<?php echo esc_attr(wp_create_nonce('_nonce_utm_event_tracker_overview_settings')); ?>">

	<div class="utm-event-tracker-dashboard-widgets-grid">
		<?php
		foreach ($utm_event_tracker_widgets as $widget) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			call_user_func($widget['callback']);
		} ?>
	</div>

	<a @click.prevent="show_overview_setting = !show_overview_setting" href="#" class="btn-overview-settings utm-event-tracker-icon-settings"></a>

	<div id="overview-settings" :class="{'overview-settings-show': show_overview_setting}">
		<div class="popup-content">
			<a @click.prevent="show_overview_setting = false" href="#" class="btn-close utm-event-tracker-icon-close"></a>
			<h4><?php esc_html_e('Widgets Settings', 'utm-event-tracker-and-analytics'); ?></h4>
			<ul class="utm-event-tracker-overview-widget-list">
				<?php foreach ($utm_event_tracker_widgets as $widget_item) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound 
				?>
					<li><?php echo esc_html($widget_item['title']); ?> <span @click="update_widget_visibility('<?php echo esc_attr($widget_item['id']); ?>')" :class="['btn-visibility-widget', get_visibility_class('<?php echo esc_attr($widget_item['id']); ?>')]"></span></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>