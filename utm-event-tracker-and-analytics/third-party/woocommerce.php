<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WooCommerce')) {
	return;
}

/**
 * Event description of woocommerce plugin
 * 
 * @since 1.1.3
 * @return array
 */
function woocommerce_event_description($descriptions, $object) {
	if ('woocommerce_purchased' === $object->get_type()) {
		$order = wc_get_order($object->order_id);

		if ($order) {
			$order_permalink = add_query_arg(array(
				'page' => 'wc-orders',
				'action' => 'edit',
				'id' => $object->order_id,
			), admin_url('admin.php'));

			$descriptions['order_id'] = sprintf(
				/* translators: %s order ID */
				__('Order ID: %s', 'utm-event-tracker-and-analytics'),
				'<a target="_blank" href="' . esc_url($order_permalink) . '">' . $object->order_id . '</a>'
			);
		}
	}

	if ('woocommerce_add_to_cart' === $object->get_type()) {
		$product = wc_get_product($object->product_id);

		if (is_a($product, 'WC_Product')) {
			$descriptions['product_id'] = sprintf(
				/* translators: %s for product name with link */
				__('Product: %s', 'utm-event-tracker-and-analytics'),
				'<a target="_blank" href="' . $product->get_permalink() . '">' . $product->get_name() . '</a>'
			);
		} else {
			$descriptions['product_id'] = sprintf(
				/* translators: %d for product id */
				__('Product: %d', 'utm-event-tracker-and-analytics'),
				$object->product_id
			);
		}

		if ($object->variation_id > 0) {
			$descriptions['variation_id'] = sprintf(
				/* translators: %d variation id of product */
				__('Variation ID: %d', 'utm-event-tracker-and-analytics'),
				$object->variation_id
			);
		}

		$descriptions['amount'] = sprintf(
			/* translators: %s for product cost */
			__('Amount: %s', 'utm-event-tracker-and-analytics'),
			number_format($object->amount, 2)
		);
	}

	return $descriptions;
}
add_filter('utm_event_tracker/event_descriptions', '\UTM_Event_Tracker\woocommerce_event_description', 10, 2);

/**
 * Get cart item data
 * 
 * @since 1.1.3
 * @return array
 */
function woocommerce_get_cart_item_data($_product, $quantity) {
	$cart_item_data = array(
		'item_id' => $_product->get_id(),
		'item_name' => $_product->get_name(),
		'price' => $_product->get_price(),
		'quantity' => $quantity,
		'affiliation' => get_bloginfo('name'),
	);

	$brands = get_the_terms($_product->get_id(), 'product_brand');
	if (isset($brands[0]) && is_a($brands[0], 'WP_Term')) {
		$cart_item_data['item_brand'] = $brands[0]->name;
	}

	$terms = get_the_terms($_product->get_id(), 'product_cat');

	$item_category_slug = array('item_category', 'item_category2', 'item_category3', 'item_category4', 'item_category5');
	foreach ($item_category_slug as $term_index => $category_key) {
		if (isset($terms[$term_index]) && is_a($terms[$term_index], 'WP_Term')) {
			$cart_item_data[$category_key] = $terms[$term_index]->name;
		}
	}

	return $cart_item_data;
}


/**
 * Show UTM session data at order page
 * 
 * @since 1.0.0
 * @return void
 */
function woocommerce_order_metabox($order) {
	$session_id = get_post_meta($order->ID, 'utm_event_tracker_session', true);
	$session = Session::get_by_id($session_id);

	echo '<div class="order-attribution-metabox">';

	echo '<h4>' . esc_html__('UTM Campaign', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html($session->get('utm_campaign', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('UTM Source', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html($session->get('utm_source', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('UTM Medium', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html($session->get('utm_medium', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('UTM Content', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html($session->get('utm_content', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('UTM Term', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html($session->get('utm_term', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('Google Click ID', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html($session->get('gclid', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('Facebook Click ID', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html($session->get('fbclid', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('City', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span>' . esc_html($session->get('city', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('Province/Region', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html($session->get('region', 'N/A')) . '</span>';

	echo '<h4>' . esc_html__('Country', 'utm-event-tracker-and-analytics') . '</h4>';
	echo '<span class="utm-event-tracker-word-break">' . esc_html(Utils::get_country_name($session->get('country'))) . '</span>';

	echo '<h4>' . esc_html__('IP Address', 'utm-event-tracker-and-analytics') . '</h4>';

	if ($session->is_exists()) {
		echo '<span>' . esc_html($session->get('ip_address', 'N/A')) . '</span>';
	} else {
		echo '<span>N/A</span>';
	}

	echo '</div>';

	echo '<style>.utm-event-tracker-word-break {word-wrap: break-word}</style>';
}

/**
 * Register meta boxes for order
 * 
 * @since 1.0.0
 * @return void
 */
function woocommerce_order_meta_boxes() {
	$order_screen = function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop-order') : 'shop_order';
	add_meta_box('utm-event-tracker-order-metabox', __('UTM Event Tracker', 'utm-event-tracker-and-analytics'), '\UTM_Event_Tracker\woocommerce_order_metabox', $order_screen, 'side', 'high');
}
add_action('add_meta_boxes', '\UTM_Event_Tracker\woocommerce_order_meta_boxes');


/**
 * Add UTM vars at order
 * 
 * @since 1.0.0
 * @return void
 */
function woocommerce_after_placed_order($order_id) {
	$order = wc_get_order($order_id);
	if (!$order) {
		return;
	}

	$session = Session::get_current_session();
	if (!$session->is_exists()) {
		return;
	}

	$has_session = get_post_meta($order_id, 'utm_event_tracker_session', true);
	if ($has_session) {
		return;
	}

	update_post_meta($order_id, 'utm_event_tracker_session', $session->get_id());

	utm_event_tracker_add_event('woocommerce_purchased', array(
		'currency' => $order->get_currency(),
		'amount' => $order->get_total(),
		'title' => esc_html__('Purchased', 'utm-event-tracker-and-analytics'),
		'meta_data' => array(
			'order_id' => $order_id
		)
	));
}
add_action('woocommerce_thankyou', '\UTM_Event_Tracker\woocommerce_after_placed_order');

/**
 * Add event after adding product to cart
 * 
 * @since 1.0.0
 * @return void
 */
function woocommerce_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id) {
	$cart_items = WC()->cart->get_cart();
	if (!isset($cart_items[$cart_item_key])) {
		return;
	}

	$current_item = $cart_items[$cart_item_key];

	utm_event_tracker_add_event('woocommerce_add_to_cart', array(
		'currency' => get_woocommerce_currency(),
		'amount' => $current_item['data']->get_price(),
		'title' => esc_html__('Added to Cart', 'utm-event-tracker-and-analytics'),
		'meta_data' => array(
			'product_id' => $product_id,
			'variation_id' => $variation_id
		)
	));
}

add_action('woocommerce_add_to_cart', '\UTM_Event_Tracker\woocommerce_add_to_cart', 10, 4);

/**
 * Add google analytics events
 * 
 * @since 1.1.3
 * @return array
 */
function woocommerce_add_ga4_events($events) {
	$events['woocommerce_purchase'] = array(
		'disable_settings' => true,
		'event_type' => 'purchase',
		'event_group' => 'woocommerce',
		'title' => esc_html__('Purchase', 'utm-event-tracker-and-analytics'),
	);

	$events['woocommerce_view_cart'] = array(
		'disable_settings' => true,
		'event_type' => 'view_cart',
		'event_group' => 'woocommerce',
		'title' => esc_html__('View cart', 'utm-event-tracker-and-analytics'),
	);

	$events['woocommerce_view_item'] = array(
		'disable_settings' => true,
		'event_type' => 'view_item',
		'event_group' => 'woocommerce',
		'title' => esc_html__('View product', 'utm-event-tracker-and-analytics'),
	);

	$events['woocommerce_add_to_cart'] = array(
		'disable_settings' => true,
		'event_type' => 'add_to_cart',
		'event_group' => 'woocommerce',
		'title' => esc_html__('Add to cart', 'utm-event-tracker-and-analytics'),
	);

	$events['woocommerce_begin_checkout'] = array(
		'disable_settings' => true,
		'event_group' => 'woocommerce',
		'event_type' => 'begin_checkout',
		'title' => esc_html__('Begin checkout', 'utm-event-tracker-and-analytics'),
	);

	$events['woocommerce_remove_from_cart'] = array(
		'disable_settings' => true,
		'event_group' => 'woocommerce',
		'event_type' => 'remove_from_cart',
		'title' => esc_html__('Remove from cart', 'utm-event-tracker-and-analytics'),
	);

	return $events;
}
//add_filter('utm_event_tracker/google_analytics/plugins_events', '\UTM_Event_Tracker\woocommerce_add_ga4_events');

/**
 * Reset begin checkout if customer update cart data
 * 
 * @since 1.1.3
 * @return void
 */
function woocommerce_reset_begin_checkout_flag() {
	if (!is_a(WC()->session, '\WC_Session_Handler')) {
		return;
	}

	WC()->session->set('utm_event_tracker_cart_viewed', false);
	WC()->session->set('utm_event_tracker_begin_checkout_sent', false);
}
add_action('woocommerce_add_to_cart', '\UTM_Event_Tracker\woocommerce_reset_begin_checkout_flag');
add_action('woocommerce_cart_item_removed', '\UTM_Event_Tracker\woocommerce_reset_begin_checkout_flag');
add_action('woocommerce_cart_item_restored', '\UTM_Event_Tracker\woocommerce_reset_begin_checkout_flag');
add_action('woocommerce_after_cart_item_quantity_update', '\UTM_Event_Tracker\woocommerce_reset_begin_checkout_flag');

/**
 * Send GA4 Event after new order created
 * 
 * @since 1.1.3
 * @return void
 */
function woocommerce_send_session_start_event() {
	return;
	if (!Google_Analytics::is_send_event_active() || is_admin() || wp_doing_ajax()) {
		return;
	}

	//update_option('my_cache_free_data', $data, false);



	$session_started = get_transient('utm_event_tracker_woocommerce_session_started');
	if (true === $session_started) {
		return;
	}

	set_transient('utm_event_tracker_woocommerce_session_started', true, (HOUR_IN_SECONDS / 2));

	$private_event = array('event_type' => 'session_start');
	$event = new Google_Analytics_Event(array('event_name' => 'woocommerce_session_start'), $private_event);
	$event->send_event();
}
add_action('init', '\UTM_Event_Tracker\woocommerce_send_session_start_event');

/**
 * Send GA4 Event after new order created
 * 
 * @since 1.1.3
 * @return void
 */
function woocommerce_send_purchase_event($order_id, $order) {
	if (!Google_Analytics::is_send_event_active()) {
		return;
	}

	$cart_items = array();
	foreach ($order->get_items() as $item) {
		$product = $item->get_product();
		$cart_items[] = array_merge(woocommerce_get_cart_item_data($product, $item->get_quantity()), array(
			'price' => $item->get_total()
		));
	}

	$payload = array(
		'transaction_id' => $order_id,
		'value' => $order->get_total(),
		'currency' => $order->get_currency(),
		'shipping' => $order->get_shipping_total(),
		'tax' => $order->get_total_tax(),
		'affiliation' => get_bloginfo('name'),
		'items' => $cart_items,
	);

	$coupons = $order->get_coupon_codes();
	if (!empty($coupons)) {
		$payload['coupon'] = implode(', ', $coupons);
	}

	$events = Google_Analytics::get_instance()->get_events('woocommerce_purchase');
	foreach ($events as $event) {
		$event = new Google_Analytics_Event($event);
		$event->send_event($payload);
	}
}
//add_action('woocommerce_new_order', '\UTM_Event_Tracker\woocommerce_send_purchase_event', 20, 2);

/**
 * Send event after adding a product to cart
 * 
 * @since 1.1.3
 * @return void
 */
function woocommerce_send_add_to_cart_event($cart_item_key) {
	if (!WC()->cart || !Google_Analytics::is_send_event_active()) {
		return;
	}

	$cart_item = WC()->cart->get_cart_item($cart_item_key);
	if (!$cart_item) {
		return;
	};

	$cart_items[] = woocommerce_get_cart_item_data($cart_item['data'], $cart_item['quantity']);

	$cart_item_value = $cart_item['data']->get_price() * $cart_item['quantity'];

	$payload = array(
		'value' => $cart_item_value,
		'currency' => get_woocommerce_currency(),
		'items' => $cart_items,
	);

	$events = Google_Analytics::get_instance()->get_events('woocommerce_add_to_cart');
	foreach ($events as $event) {
		$event = new Google_Analytics_Event($event);
		$event->send_event($payload);
	}
}
//add_action('woocommerce_add_to_cart', '\UTM_Event_Tracker\woocommerce_send_add_to_cart_event', 20);


/**
 * Send event after adding a product to cart
 * 
 * @since 1.1.3
 * @return void
 */
function woocommerce_send_event_remove_from_cart($cart_item_key, $cart) {
	if (!Google_Analytics::is_send_event_active()) {
		return;
	}

	$cart_item = $cart->get_cart_item($cart_item_key);
	if (!$cart_item) {
		return;
	};

	$cart_items[] = woocommerce_get_cart_item_data($cart_item['data'], $cart_item['quantity']);

	$cart_item_value = $cart_item['data']->get_price() * $cart_item['quantity'];

	$payload = array(
		'value' => $cart_item_value,
		'currency' => get_woocommerce_currency(),
		'items' => $cart_items,
	);

	$events = Google_Analytics::get_instance()->get_events('woocommerce_remove_from_cart');
	foreach ($events as $event) {
		$event = new Google_Analytics_Event($event);
		$event->send_event($payload);
	}
}
//add_action('woocommerce_remove_cart_item', '\UTM_Event_Tracker\woocommerce_send_event_remove_from_cart', 10, 2);

/**
 * Send event if customer start to checkout
 * 
 * @since 1.1.3
 * @return void
 */
function woocommerce_send_event_begin_checkout() {
	if (!WC()->cart || WC()->session->get('utm_event_tracker_begin_checkout_sent')) {
		return;
	}

	$cart_items = [];
	$cart_total = 0;

	foreach (WC()->cart->get_cart() as $cart_item) {
		$cart_total += ($cart_item['data']->get_price() * $cart_item['quantity']);
		$cart_items[] = woocommerce_get_cart_item_data($cart_item['data'], $cart_item['quantity']);
	}

	$payload = array(
		'value' => $cart_total,
		'currency' => get_woocommerce_currency(),
		'items' => $cart_items,
	);

	$events = Google_Analytics::get_instance()->get_events('woocommerce_begin_checkout');
	foreach ($events as $event) {
		$event = new Google_Analytics_Event($event);
		$event->send_event($payload);
	}

	WC()->session->set('utm_event_tracker_begin_checkout_sent', true);
}
//add_action('woocommerce_before_checkout_form', '\UTM_Event_Tracker\woocommerce_send_event_begin_checkout');

/**
 * Send event if customer view cart page
 * 
 * @since 1.1.3
 * @return void
 */
function woocommerce_send_view_cart_event() {
	if (!WC()->cart || !is_cart() || WC()->session->get('utm_event_tracker_cart_viewed')) {
		return;
	}

	$cart_items = [];
	$cart_total = 0;

	foreach (WC()->cart->get_cart() as $cart_item) {
		$cart_total += ($cart_item['data']->get_price() * $cart_item['quantity']);
		$cart_items[] = woocommerce_get_cart_item_data($cart_item['data'], $cart_item['quantity']);
	}

	if (count($cart_items) === 0) {
		return;
	}

	$payload = array(
		'value' => $cart_total,
		'currency' => get_woocommerce_currency(),
		'items' => $cart_items,
	);

	$events = Google_Analytics::get_instance()->get_events('woocommerce_view_cart');
	foreach ($events as $event) {
		$event = new Google_Analytics_Event($event);
		$event->send_event($payload);
	}

	WC()->session->set('utm_event_tracker_cart_viewed', true);
}
//add_action('template_redirect', '\UTM_Event_Tracker\woocommerce_send_view_cart_event');

/**
 * Send event if customer view a product item
 * 
 * @since 1.1.3
 * @return void
 */
function woocommerce_send_view_item_event() {
	if (!is_product()) {
		return;
	}

	$product = wc_get_product();
	if (!is_a($product, 'WC_Product')) {
		return;
	}

	$last_viewed_product = WC()->session->get('utm_event_tracker_last_viewed_product');
	if ($last_viewed_product == $product->get_id()) {
		return;
	}

	$cart_items[] = woocommerce_get_cart_item_data($product, 1);

	$payload = array(
		'value' => $product->get_price(),
		'currency' => get_woocommerce_currency(),
		'items' => $cart_items,
	);

	$events = Google_Analytics::get_instance()->get_events('woocommerce_view_item');
	foreach ($events as $event) {
		$event = new Google_Analytics_Event($event);
		$event->send_event($payload);
	}

	WC()->session->set('utm_event_tracker_last_viewed_product', $product->get_id());
}
//add_action('template_redirect', '\UTM_Event_Tracker\woocommerce_send_view_item_event');
