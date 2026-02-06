<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly 
} ?>

<div id="session-details-container">

	<a @click.prevent="close_summary" href="#" class="btn-close-summary dashicons dashicons-no-alt"></a>

	<dl class="session-summary">
		<dt><?php esc_html_e('UTM Campaign', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>{{session.utm_campaign}}</dd>

		<dt><?php esc_html_e('UTM Source', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>{{session.utm_source}}</dd>

		<dt><?php esc_html_e('UTM Medium', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>{{session.utm_medium}}</dd>

		<dt><?php esc_html_e('UTM Term', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>{{session.utm_term}}</dd>

		<dt><?php esc_html_e('UTM Content', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>{{session.utm_content}}</dd>

		<dt><?php esc_html_e('Google Ad Click ID', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>{{session.gclid}}</dd>

		<dt><?php esc_html_e('Facebook Click ID', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>{{session.fbclid}}</dd>

		<?php do_action('utm_event_tracker/session_summary_item_html') ?>

		<dt><?php esc_html_e('Landing Page', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd><a target="_blank" :href="session.landing_page_url">{{session.landing_page}}</a></dd>

		<dt><?php esc_html_e('Referrer', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd><a target="_blank" :href="session.referrer">{{session.referrer}}</a></dd>

		<dt><?php esc_html_e('Last Online', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>{{session.last_online_readable_time}} <br> {{session.last_online_date}}</dd>

		<dt><?php esc_html_e('Journey', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>
			<ul class="session-summary-views">
				<li v-for="view in session.journey"><a target="_blank" :href="view.landing_page_url">{{view.landing_page}}</a></li>
			</ul>
		</dd>

		<dt><?php esc_html_e('Events', 'utm-event-tracker-and-analytics'); ?></dt>
		<dd>
			<ul class="session-summary-events">
				<li v-for="event in session.events" :data-id="event.id" v-html="event.description"></li>
				<li class="pro-lock-event-item" v-if="lock_event_count > 0">
					<?php esc_html_e('Get pro version for seeing more {{lock_event_count}} event(s).', 'utm-event-tracker-and-analytics'); ?>
					<br>
					<a class="btn-utm-event-tracker-get-pro" target="_blank" href="https://codiepress.com/plugins/utm-event-tracker-and-analytics-pro/?utm_campaign=utm+event+tracker&utm_source=plugin&utm_medium=event+list"><?php esc_html_e('Get Pro', 'utm-event-tracker-and-analytics'); ?></a>
				</li>
			</ul>
		</dd>
	</dl>

</div>