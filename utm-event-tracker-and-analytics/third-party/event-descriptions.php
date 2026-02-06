<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Add description for event
 * 
 * @since 1.0.6
 * @return string
 */
function edd_event_descriptions($descriptions, $event) {
	if ('edd_purcahse' === $event->get_type()) {
		$user = get_user_by('id', $event->customer_id);
		if (is_a($user, '\WP_User')) {
			$descriptions[100] = sprintf(
				/* translators: %s for customer name */
				esc_html__('Customer: %s.', 'utm-event-tracker-and-analytics'), 
				$user->display_name
			);
		}

		$descriptions[] = sprintf(
			/* translators: %s for order amount */
			esc_html__('Amount: %s', 'utm-event-tracker-and-analytics'), $event->amount
		);

		$descriptions[] = sprintf(
			/* translators: %s for current */
			esc_html__('Currency: %s', 'utm-event-tracker-and-analytics'), $event->currency
		);

		$descriptions[] = sprintf(
			/* translators: %s for order id */
			esc_html__('Order ID: %s', 'utm-event-tracker-and-analytics'), $event->order_id
		);
	}

	if ('edd_add_to_cart' === $event->get_type()) {
		$descriptions[] = sprintf(
			/* translators: %s for  item id */
			esc_html__('ID: %d', 'utm-event-tracker-and-analytics'), $event->download_id
		);

		$descriptions[] = sprintf(
			/* translators: %s for download name */
			esc_html__('Name: %s', 'utm-event-tracker-and-analytics'), $event->download_name
		);

		$descriptions[] = sprintf(
			/* translators: %s for item amount */
			esc_html__('Amount: %s', 'utm-event-tracker-and-analytics'), $event->amount
		);

		$descriptions[] = sprintf(
			/* translators: %s for current */
			esc_html__('Currency: %s', 'utm-event-tracker-and-analytics'), $event->currency
		);
	}

	return $descriptions;
}
add_filter('utm_event_tracker/event_descriptions', '\UTM_Event_Tracker\edd_event_descriptions', 10, 2);

/**
 * Add form description
 * 
 * @since 1.1.2
 * @return array
 */
function elementor_event_description($descriptions, $event) {
	if (!empty($event->form_name)) {
		$descriptions[] = sprintf(
			/* translators: %s order ID */
			esc_html__('Form Name: %s', 'utm-event-tracker-and-analytics'),
			esc_html($event->form_name)
		);
	}

	return $descriptions;
}
add_filter('utm_event_tracker/elementor_form_submit/event_descriptions', '\UTM_Event_Tracker\elementor_event_description', 10, 2);