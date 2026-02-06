<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Event class
 * 
 * @since 1.0.0
 */
class Event {

	/**
	 * ID of event
	 * 
	 * @since 1.0.0
	 * @var integer
	 */
	public $id = 0;

	/**
	 * Session ID of event
	 * 
	 * @since 1.0.0
	 * @var integer
	 */
	public $session_id  = 0;

	/**
	 * Event type
	 * 
	 * @since 1.0.0
	 * @var null|string
	 */
	public $type = null;

	/**
	 * Event title
	 * 
	 * @since 1.1.2
	 * @var null|string
	 */
	public $title = null;

	/**
	 * Currency of amount
	 * 
	 * @since 1.0.0
	 * @var null|string
	 */
	public $currency = null;

	/**
	 * Hold amount of event
	 * 
	 * @since 1.0.0
	 * @var float
	 */
	public $amount = 0.00;

	/**
	 * Hold extra data of event
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	public $meta_data = [];

	/**
	 * Hold created datetime of event
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $created_on = '';

	/**
	 * Hold description of this event
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $description = '';

	/**
	 * Constructor of event
	 * 
	 * @since 1.0.0
	 */
	public function __construct($event_data = null) {
		$this->created_on = gmdate('Y-m-d H:i:s');
		if (is_object($event_data)) {
			$event_data = (array) $event_data;
		}

		if (!is_array($event_data)) {
			return;
		}

		$meta_data = null;
		if (isset($event_data['meta_data']) && !is_array($event_data['meta_data'])) {
			$meta_data = json_decode($event_data['meta_data'], true);
			unset($event_data['meta_data']);
		}

		$this->meta_data = (array) $meta_data;

		foreach ($event_data as $key => $value) {
			$key = sanitize_key($key);
			if (empty($key)) {
				continue;
			}

			$this->$key = $value;
		}

		$this->id = absint($this->id);
		$this->set_title();
		$this->set_description();
	}

	/**
	 * Add data into meta data
	 * 
	 * @since 1.0.0
	 */
	public function __set($key, $value) {
		$this->meta_data[$key] = $value;
	}

	/**
	 * Get value from meta_data
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->meta_data[$key]) ? $this->meta_data[$key] : null;
	}

	/**
	 * Check the key exists within meta data
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function __isset($key) {
		return isset($this->meta_data[$key]);
	}

	/**
	 * Save event
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function save() {
		$event_data = get_object_vars($this);
		unset($event_data['description']);

		$meta_data = isset($event_data['meta_data']) && is_array($event_data['meta_data']) ? $event_data['meta_data'] : null;
		if (is_array($meta_data)) {
			$event_data['meta_data'] = wp_json_encode($meta_data);
		}

		global $wpdb;
		$wpdb->replace($wpdb->utm_event_tracker_events_table, $event_data); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Set title of event
	 * 
	 * @since 1.1.2
	 * @return void
	 */
	public function set_title() {
		if (empty($this->title)) {
			$this->title = $this->this->type;
		}
	}

	/**
	 * Set description of this event
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function set_description() {
		$descriptions = array();

		if (!empty($this->title)) {
			$descriptions['title'] = sprintf(
				/* translators: %s for product cost */
				esc_html__('Title: %s', 'utm-event-tracker-and-analytics'),
				esc_html($this->title)
			);
		}

		if (!empty($this->form_id)) {
			$descriptions['form_id'] = sprintf(
				/* translators: %s of form ID */
				esc_html__('Form ID: %s', 'utm-event-tracker-and-analytics'),
				$this->form_id
			);
		}

		if (!empty($this->entry_id)) {
			$descriptions['entry_id'] = sprintf(
				/* translators: %s for entry ID */
				esc_html__('Entry ID: %s', 'utm-event-tracker-and-analytics'),
				$this->entry_id
			);
		}

		if ('woocommerce_add_to_cart' === $this->type) {
			$product_id = $this->product_id;
			$variation_id = 0;

			if (!empty($this->variation_id) && absint($this->variation_id) > 0) {
				$variation_id = $this->variation_id;
			}

			$descriptions['title'] = __('Added to cart:', 'utm-event-tracker-and-analytics');

			$descriptions['product_id'] = sprintf(
				/* translators: %d for product ID */
				__('Product ID: %d', 'utm-event-tracker-and-analytics'),
				$product_id
			);

			if ($variation_id > 0) {
				$descriptions['variation_id'] = sprintf(
					/* translators: %d for product variation ID */
					__('Variation ID: %d', 'utm-event-tracker-and-analytics'),
					$variation_id
				);
			}

			$descriptions['amount'] = sprintf(
				/* translators: %s for product cost */
				__('Amount: %s', 'utm-event-tracker-and-analytics'),
				number_format($this->amount, 2)
			);
		}

		if ('woocommerce_purchased' === $this->type) {
			$descriptions['title'] = __('Order Placed:', 'utm-event-tracker-and-analytics');
			$descriptions['amount'] = sprintf(
				/* translators: %s for product cost */
				__('Amount: %s', 'utm-event-tracker-and-analytics'),
				number_format($this->amount, 2)
			);

			if (absint($this->order_id) > 0) {
				$descriptions['order_id'] = sprintf(
					/* translators: %s order ID */
					__('Order ID: %d', 'utm-event-tracker-and-analytics'),
					$this->order_id
				);
			}
		}

		$descriptions = apply_filters('utm_event_tracker/event_descriptions', $descriptions, $this);
		$descriptions = apply_filters('utm_event_tracker/' . $this->type . '/event_descriptions', $descriptions, $this);

		$descriptions['date'] = sprintf(
			/* translators: %s for date of event */
			__('Date: %s', 'utm-event-tracker-and-analytics'),
			gmdate(get_option('date_format') . ' ' . get_option('time_format'), Utils::get_date($this->created_on, true))
		);

		$description = apply_filters('utm_event_tracker/' . $this->type . '/event_description', implode('<br>', $descriptions), $this);
		$this->description = apply_filters('utm_event_tracker/event_description', $description, $this->type, $this);
	}

	/**
	 * Get description of this event
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Get event type
	 * 
	 * @since 1.1.3
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}
}
