<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

$utm_event_tracker_license_activate_url = menu_page_url('utm-event-tracker-license', false);
$utm_event_tracker_activate_url = wp_nonce_url('plugins.php?action=activate&plugin=utm-event-tracker-and-analytics-pro/utm-event-tracker-and-analytics-pro.php&plugin_status=all&paged=1', 'activate-plugin_utm-event-tracker-and-analytics-pro/utm-event-tracker-and-analytics-pro.php'); ?>

<div class="utm-event-tracker-box">
	<div class="utm-event-tracker-heading">
		<h2><?php esc_html_e('Settings', 'utm-event-tracker-and-analytics'); ?></h2>
	</div>

	<table class="form-table">
		<?php do_action('utm_event_tracker/admin_settings'); ?>
	</table>
</div>

<div class="utm-event-tracker-box">
	<div class="utm-event-tracker-heading">
		<h2><?php esc_html_e('Google Analytics Events', 'utm-event-tracker-and-analytics'); ?></h2>
	</div>

	<table class="form-table form-table-utm-event-tracker-repeater">
		<tr>
			<th>
				<label for="send_google_analytics_event"><?php esc_html_e('Enable Send Event', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note"><?php esc_html_e('Track and send events to Google Analytics.', 'utm-event-tracker-and-analytics') ?></p>
			</th>
			<td>
				<label>
					<input id="send_google_analytics_event" type="checkbox" v-model="send_google_analytics_event">
					<?php esc_html_e('Send events to Google Analytics.', 'utm-event-tracker-and-analytics'); ?>
				</label>
			</td>
		</tr>

		<tr v-if="send_google_analytics_event">
			<th>
				<label for="measurement-id"><?php esc_html_e('Measurement ID', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note">
					<?php
					printf(
						/* translators: %1$s: Link open, %2$s: Link close */
						esc_html__('Enter your Google Analytics Measurement ID. %1$sClick here%2$s for setup instructions.', 'utm-event-tracker-and-analytics'),
						'<a href="https://support.google.com/analytics/answer/12270356" target="_blank">',
						'</a>',
					); ?>
				</p>
			</th>
			<td>
				<input type="text" id="measurement-id" v-model="google_analytics_measurement_id">
			</td>
		</tr>

		<tr v-if="send_google_analytics_event">
			<th>
				<label for="ga4_api_secret"><?php esc_html_e('API Secret', 'utm-event-tracker-and-analytics'); ?></label>
				<p class="field-note">
					<?php
					printf(
						/* translators: %1$s: Link open, %2$s: Link close */
						esc_html__('Enter your Google Analytics API Secret. %1$sClick here%2$s to view the instructions.', 'utm-event-tracker-and-analytics'),
						'<a href="https://www.youtube.com/watch?v=phhF68w9bLA" target="_blank">',
						'</a>',
					); ?>
				</p>
			</th>
			<td>
				<input type="text" id="ga4_api_secret" v-model="google_analytics_api_secret">
			</td>
		</tr>

		<?php do_action('utm_event_tracker/google_analytics/events_settings_rows') ?>

		<tr v-if="send_google_analytics_event">
			<th>
				<?php esc_html_e('Send Events', 'utm-event-tracker-and-analytics'); ?>
				<p class="field-note"><?php esc_html_e('Select the specific events you want to track and send to Google Analytics.', 'utm-event-tracker-and-analytics'); ?></p>
			</th>
			<td>
				<google-analytics-events :events="google_analytics_events"></google-analytics-events>
			</td>
		</tr>
	</table>
</div>

<div class="form-footer">
	<button class="button button-primary" name="submit" value="save"><?php esc_html_e('Save Changes', 'utm-event-tracker-and-analytics'); ?></button>
</div>

<?php if (!Utils::license_activated()) : ?>
	<template v-if="show_modal">
		<div id="utm-event-tracker-custom-parameters-modal" class="utm-event-tracker-modal">
			<div class="utm-modal-container">
				<a @click.prevent="close_modal()" class="btn-close-modal dashicons dashicons-no-alt" href="#"></a>

				<div class="utm-modal-body">
					<img class="modal-logo" src="<?php echo esc_url(UTM_EVENT_TRACKER_URL) ?>assets/analytics-logo.png" alt="UTM Event Tracker Logo">

					<template v-if="show_modal == 'custom_parameters_modal'">
						<h4>Unlock Custom Parameter Support</h4>
						<p>Track visitors using your own custom URL parameters, helping you capture more complete and accurate attribution across all marketing channels.</p>
					</template>

					<template v-if="show_modal == 'custom_event_modal'">
						<h4>Unlock Custom Event Tracking</h4>
						<p>Track more user interactions by adding unlimited custom click events. The free version supports up to 1 event — upgrade to create as many custom events as you need for deeper insights.</p>
					</template>

					<template v-if="show_modal == 'append_utm_parameter'">
						<h4>Unlock UTM Parameter Appending</h4>
						<p>Automatically append UTM parameters to internal links to maintain consistent tracking across user sessions and improve attribution accuracy.</p>
					</template>

					<template v-if="show_modal == 'create_session_without_utm'">
						<h4>Unlock Session Tracking Without UTM Parameters</h4>
						<p>Automatically create sessions even when no UTM parameters are present. This helps capture more visitors and maintain accurate tracking across all traffic sources.</p>
					</template>

					<template v-if="show_modal == 'ga4_locked_modal'">
						<h4>Unlock Unlimited GA4 Event Tracking</h4>
						<p>Send multiple events—including custom events—to Google Analytics for deeper insights and better reporting. The free version allows 1 event; upgrade to send unlimited events to GA4.</p>
					</template>

					<?php if (!Utils::is_pro_installed()) : ?>
						<p style="font-weight: 500;">This feature is available in the Pro version. Install the Pro plugin to unlock full access and enjoy advanced tracking capabilities.</p>
					<?php endif; ?>

					<?php if (Utils::is_pro_installed() && !Utils::is_pro_activated()) : ?>
						<p style="font-weight: 500;">The Pro plugin is installed but not active. Activate it to unlock this feature and access all advanced tracking options.</p>
					<?php endif; ?>

					<?php if (Utils::is_pro_activated() && !Utils::license_activated()) : ?>
						<p style="font-weight: 500;">Your Pro plugin is active, but the license is not yet verified. Activate your license to unlock this feature and access all premium functionality.</p>
					<?php endif; ?>
				</div>

				<div class="utm-modal-footer">
					<a @click.prevent="close_modal()" class="button" href="#"><?php esc_html_e('Back', 'utm-event-tracker-and-analytics') ?></a>

					<?php if (!Utils::is_pro_installed()) : ?>
						<a target="_blank" class="button button-primary" :href="get_pro_link"><?php esc_html_e('Get Pro', 'utm-event-tracker-and-analytics') ?></a>
					<?php endif; ?>

					<?php if (Utils::is_pro_installed() && !Utils::is_pro_activated()) : ?>
						<a class="button button-primary" href="<?php echo esc_url($utm_event_tracker_activate_url) ?>"><?php esc_html_e('Activate Now', 'utm-event-tracker-and-analytics') ?></a>
					<?php endif; ?>

					<?php if (Utils::is_pro_activated() && !Utils::license_activated()) : ?>
						<a target="_blank" class="button button-primary" href="<?php echo esc_url($utm_event_tracker_license_activate_url) ?>"><?php esc_html_e('Activate License', 'utm-event-tracker-and-analytics') ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</template>
<?php endif; ?>