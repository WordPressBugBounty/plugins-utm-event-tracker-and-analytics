<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('EDD')) {
	return;
}

/**
 * Handle purchase event 
 * 
 * @since 1.0.6
 * @return void
 */
function edd_complete_purchase($order_id) {
	$order = edd_get_order($order_id);
	utm_event_tracker_add_event('edd_purcahse', array(
		'amount' => $order->total,
		'currency' => $order->currency,
		'title' => esc_html__('EDD Purchase', 'utm-event-tracker-and-analytics'),
		'meta_data' => array(
			'order_id' => $order_id,
			'customer_id' => $order->customer_id,
		)
	));
}
add_action('edd_complete_purchase', '\UTM_Event_Tracker\edd_complete_purchase', 100);

/**
 * Handle event after added to cart
 * 
 * @since 1.0.6
 * @return void
 */
function edd_add_to_cart($download_id) {
	$_product = edd_get_download($download_id);
	utm_event_tracker_add_event('edd_add_to_cart', array(
		'amount' => $_product->get_price(),
		'currency' => edd_get_currency(),
		'title' => esc_html__('EDD added to cart', 'utm-event-tracker-and-analytics'),
		'meta_data' => array(
			'download_id' => $download_id,
			'download_name' => $_product->get_name(),
		)
	));
}
add_action('edd_post_add_to_cart', '\UTM_Event_Tracker\edd_add_to_cart', 100);


/**
 * Add google analytics events
 * 
 * @since 1.1.3
 * @return array
 */
function edd_add_ga4_events($events) {
	$events['edd_purchase'] = array(
		'event_type' => 'purchase',
		'event_group' => 'easy_digital_downloads',
		'title' => esc_html__('Purchase', 'utm-event-tracker-and-analytics'),
		'disable_settings' => true,
	);

	$events['edd_add_to_cart'] = array(
		'has_ability' => array(),
		'event_type' => 'add_to_cart',
		'event_group' => 'easy_digital_downloads',
		'title' => esc_html__('Add to cart', 'utm-event-tracker-and-analytics'),
		'disable_settings' => true,
	);

	return $events;
}
//add_filter('utm_event_tracker/google_analytics/plugins_events', '\UTM_Event_Tracker\edd_add_ga4_events');

/**
 * Get cart item data for GA4 event
 * 
 * @since 1.1.3
 * @return array
 */
function edd_get_cart_item_data($download_id, $quantity = 1) {
	$product = edd_get_download($download_id);

	$cart_item_data = array(
		'quantity' => $quantity,
		'item_id' => $product->get_ID(),
		'item_name' => $product->get_name(),
		'price' => $product->get_price(),
		'affiliation' => get_bloginfo('name'),
	);

	$categories = wp_get_post_terms($download_id, 'download_category');

	$item_category_slug = array('item_category', 'item_category2', 'item_category3', 'item_category4', 'item_category5');
	foreach ($item_category_slug as $term_index => $category_key) {
		if (isset($categories[$term_index]) && is_a($categories[$term_index], 'WP_Term')) {
			$cart_item_data[$category_key] = $categories[$term_index]->name;
		}
	}

	return $cart_item_data;
}


/**
 * Send add to cart event to Google Analytics 4
 * 
 * @since 1.1.3
 * @return void
 */
function edd_send_add_to_cart_event($download_id) {
	if (!Google_Analytics::is_send_event_active()) {
		return;
	}

	$cart_items[] = edd_get_cart_item_data($download_id);

	$cart_item_value = array_sum(wp_list_pluck($cart_items, 'price'));

	$payload = array(
		'value' => $cart_item_value,
		'currency' => edd_get_currency(),
		'items' => $cart_items,
	);

	$events = Google_Analytics::get_instance()->get_events('edd_add_to_cart');
	foreach ($events as $event) {
		$event = new Google_Analytics_Event($event);
		$event->send_event($payload);
	}
}
add_action('edd_post_add_to_cart', '\UTM_Event_Tracker\edd_send_add_to_cart_event');


/**
 * Send purchase event to Google Analytics 4
 * 
 * @since 1.1.3
 * @return void
 */
function edd_send_purchase_event($order_id) {
	if (!Google_Analytics::is_send_event_active()) {
		return;
	}

	$order = edd_get_order($order_id);
	if (!$order) {
		return;
	}

	$cart_items = array_map(function ($order_item) {
		return edd_get_cart_item_data($order_item->product_id);
	}, $order->get_items());

	$payload = array(
		'tax' => $order->tax,
		'value' => $order->total,
		'currency' => $order->currency,
		'affiliation' => get_bloginfo('name'),
		'transaction_id' => $order->get_transaction_id(),
		'items' => $cart_items,
	);
}
add_action('edd_complete_purchase', '\UTM_Event_Tracker\edd_send_purchase_event');
