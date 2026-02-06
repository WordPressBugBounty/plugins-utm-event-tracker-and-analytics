<?php

namespace UTM_Event_Tracker;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Migrate class plugin
 */
final class Migrate {

	/**
	 * Option key for hold db version
	 * 
	 * @since 1.2.0
	 * @var string
	 */
	const DB_VERSION_OPTION_KEY = 'utm_event_tracker_db_version';

	/** 
	 * Constructor 
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		register_activation_hook(UTM_EVENT_TRACKER_FILE, [$this, 'activate']);

		add_action('init', array($this, 'schedule_event'));
		add_action('init', array($this, 'modify_data_table'));
		add_action('init', array($this, 'import_data_from_old_data_table'));

		add_action('utm_event_tracker/migrate_event_data', array($this, 'migrate_event_data'));
		add_action('utm_event_tracker/update_session_location', array($this, 'update_session_location'));
	}

	/**
	 * Create stats table
	 * 
	 * @since 1.0
	 * @return void
	 */
	public static function create_stats_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		maybe_create_table($wpdb->utm_event_tracker_stats_table, "CREATE TABLE $wpdb->utm_event_tracker_stats_table (
			session_id INT PRIMARY KEY,
			views INT DEFAULT 0,
			events INT DEFAULT 0,
			KEY idx_views (views),
			KEY idx_events (events)
		) $charset_collate;");
	}

	/**
	 * Create data table
	 * 
	 * @since 1.0
	 * @return void
	 */
	public function activate() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		maybe_create_table($wpdb->utm_event_tracker_sessions_table, "CREATE TABLE $wpdb->utm_event_tracker_sessions_table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id CHAR(64) NOT NULL,
			utm_campaign VARCHAR(100) DEFAULT NULL,
			utm_medium VARCHAR(100) DEFAULT NULL,
			utm_source VARCHAR(100) DEFAULT NULL,
			utm_term VARCHAR(100) DEFAULT NULL,
			utm_content VARCHAR(255) DEFAULT NULL,
			fbclid VARCHAR(150) DEFAULT NULL,
			gclid VARCHAR(150) DEFAULT NULL,
			landing_page VARCHAR(250) NOT NULL DEFAULT '',
			referrer VARCHAR(500) NOT NULL DEFAULT '',
			ip_address VARBINARY(16) DEFAULT NULL,
			city VARCHAR(50) DEFAULT NULL,
			region VARCHAR(50) DEFAULT NULL,
			country CHAR(2) DEFAULT NULL,
			meta_data JSON DEFAULT NULL,
			last_online DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			created_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_session_id (session_id),
			KEY idx_utm_search (utm_campaign, utm_medium, utm_source, utm_term, utm_content),
			KEY idx_created_on (created_on),
			KEY idx_last_online (last_online)
		) $charset_collate;");

		maybe_create_table($wpdb->utm_event_tracker_views_table, "CREATE TABLE $wpdb->utm_event_tracker_views_table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id INT UNSIGNED NOT NULL,
			landing_page VARCHAR(250) DEFAULT NULL,
			created_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_session_id (session_id),
			KEY idx_created_on (created_on)
		) $charset_collate;");

		maybe_create_table($wpdb->utm_event_tracker_events_table, "CREATE TABLE $wpdb->utm_event_tracker_events_table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id INT UNSIGNED NOT NULL,
			type VARCHAR(100) DEFAULT NULL,
			title VARCHAR(255) DEFAULT NULL,
			currency CHAR(3) DEFAULT NULL,
			amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			meta_data JSON DEFAULT NULL,
			created_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_session_id (session_id),
			KEY idx_type_created (type, created_on),
    		KEY idx_created_on (created_on)
		) $charset_collate;");

		self::create_stats_table();

		update_option(self::DB_VERSION_OPTION_KEY, '1.1');
	}

	/**
	 * Modify data table
	 * 
	 * @since 1.2.0
	 * @return void
	 */
	public function modify_data_table() {
		$db_version = get_option(self::DB_VERSION_OPTION_KEY);
		if ($db_version < '1.1') {
			$this->modify_table_columns('view');
			$this->modify_table_columns('event');
			$this->modify_table_columns('session');
			update_option(self::DB_VERSION_OPTION_KEY, '1.1');
		}
	}

	/**
	 * Schedule events
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function schedule_event() {
		if (!wp_next_scheduled('utm_event_tracker/update_session_location')) {
			wp_schedule_event(time(), 'hourly', 'utm_event_tracker/update_session_location');
		}

		if (!wp_next_scheduled('utm_event_tracker/migrate_event_data')) {
			wp_schedule_event(time(), 'daily', 'utm_event_tracker/migrate_event_data');
		}
	}

	/**
	 * Get information of column from data table
	 * 
	 * @since 1.2.0
	 * @return object
	 */
	public function get_table_column_info($column_name, $columns_informations) {
		return array_find($columns_informations, fn($item) => $item->Field == $column_name);
	}

	/**
	 * Modify tables
	 * 
	 * @since 1.0.6
	 * @return void
	 */
	public function modify_table_columns($table_key, $suffix = '') {
		global $wpdb;

		$supported_tables = array(
			'view' => $wpdb->utm_event_tracker_views_table . $suffix,
			'event' => $wpdb->utm_event_tracker_events_table . $suffix,
			'session' => $wpdb->utm_event_tracker_sessions_table . $suffix,
		);

		if (!array_key_exists($table_key, $supported_tables)) {
			return;
		}

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$table_name = $supported_tables[$table_key];

		// phpcs:disable

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$columns_info = $wpdb->get_results($wpdb->prepare("DESCRIBE %i", $table_name));
		$columns = wp_list_pluck($columns_info, 'Field');

		if ('session' == $table_key) {
			if (!in_array('referrer', $columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD `referrer` VARCHAR(500) NOT NULL DEFAULT '' AFTER `landing_page`;", $table_name));
			}

			if (!in_array('meta_data', $columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD `meta_data` JSON DEFAULT NULL AFTER `country`;", $table_name));
			}
		}

		if ('event' == $table_key) {
			if (!in_array('title', $columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD `title` VARCHAR(255) DEFAULT NULL AFTER `type`;", $table_name));
			}
		}

		// Update column and other information of table
		$columns_info = $wpdb->get_results($wpdb->prepare("DESCRIBE %i", $table_name));
		if ('session' == $table_key) {
			$current_column_info = $this->get_table_column_info('session_id', $columns_info);
			if ($current_column_info && 'char(64)' !== $current_column_info->Type) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i MODIFY COLUMN `session_id` CHAR(64) NOT NULL;", $table_name));
			}
		}

		$indexes_columns = $wpdb->get_col($wpdb->prepare("SHOW INDEX FROM %i", $table_name), 2);
		if ('session' == $table_key) {
			if (!in_array('uniq_session_id', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD UNIQUE KEY uniq_session_id (session_id);", $table_name));
			}

			if (!in_array('idx_utm_search', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD KEY idx_utm_search (utm_campaign, utm_medium, utm_source, utm_term, utm_content);", $table_name));
			}

			if (!in_array('idx_created_on', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD KEY idx_created_on (created_on);", $table_name));
			}

			if (!in_array('idx_last_online', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD KEY idx_last_online (last_online);", $table_name));
			}
		}

		if ('view' == $table_key) {
			if (!in_array('idx_session_id', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD KEY idx_session_id (session_id);", $table_name));
			}

			if (!in_array('idx_created_on', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD KEY idx_created_on (created_on);", $table_name));
			}
		}

		if ('event' == $table_key) {
			if (!in_array('idx_session_id', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD KEY idx_session_id (session_id);", $table_name));
			}

			if (!in_array('idx_type_created', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD KEY idx_type_created (type, created_on);", $table_name));
			}

			if (!in_array('idx_created_on', $indexes_columns)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query($wpdb->prepare("ALTER TABLE %i ADD KEY idx_created_on (created_on);", $table_name));
			}
		}
	}

	/**
	 * Update session location
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function update_session_location() {
		$ipinfo_token = Settings::get_instance()->get('ipinfo_token');
		if (empty($ipinfo_token)) {
			return;
		}

		global $wpdb;

		$sessions = $wpdb->get_results("SELECT * FROM $wpdb->utm_event_tracker_sessions_table WHERE country IS null ORDER BY created_on DESC LIMIT 0, 100"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		array_walk($sessions, function ($item) {
			$session = new Session($item);
			$session->save();
		});
	}

	/**
	 * Migrate old data to updated system
	 * 
	 * @since 1.1.3
	 * @return void
	 */
	public function migrate_event_data() {
		global $wpdb;

		$events = $wpdb->get_results("SELECT * FROM $wpdb->utm_event_tracker_events_table WHERE type = 'woocommerce_checkout' OR title is null"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if (empty($events)) {
			return;
		}

		foreach ($events as $event_item) {
			$event = new Event($event_item);
			if ('woocommerce_checkout' === $event->type) {
				$event->type = 'woocommerce_purchased';
			}

			$event->save();
		}
	}

	/**
	 * Migrate old table
	 * 
	 * @since 1.2.0
	 * @return void 
	 */
	public function import_data_from_old_data_table() {
		if (!isset($_GET['utm_tracker_table_migration']) || !current_user_can('manage_options')) {
			return;
		}

		global $wpdb;

		$current_table = sanitize_text_field(wp_unslash($_GET['utm_tracker_table_migration']));
		$supported_tables = array(
			'view' => $wpdb->utm_event_tracker_views_table,
			'event' => $wpdb->utm_event_tracker_events_table,
			'session' => $wpdb->utm_event_tracker_sessions_table,
		);

		if (!array_key_exists($current_table, $supported_tables)) {
			return;
		}

		$limit = isset($_GET['limit']) && absint($_GET['limit']) > 0 ? absint($_GET['limit']) : 50000;

		$option_key = 'utm_event_tracker_data_chunks_' . $current_table . '_' . $limit;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$suffix = '_new';
		$old_table_name = $supported_tables[$current_table];
		$new_table_name = $old_table_name . '_new';

		$old_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_table_name . '_old'));
		if ($old_table_exists) {
			$suffix = '';
			$new_table_name = $supported_tables[$current_table];
			$old_table_name = $supported_tables[$current_table] . '_old';
		}

		if (isset($_GET['drop'])) {
			$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $new_table_name));
			delete_option($option_key);
			if (true == $_GET['drop']) {
				exit;
			}
		}

		$create_table_sql = $wpdb->prepare("CREATE TABLE %i LIKE %i;", $new_table_name, $old_table_name);
		maybe_create_table($new_table_name, $create_table_sql);
		$this->modify_table_columns($current_table, $suffix);

		$chunk_complted = get_option($option_key);
		if (!is_array($chunk_complted) || isset($_GET['drop'])) {
			$chunk_complted = array();
		}

		$total_rows = $wpdb->get_var($wpdb->prepare("SELECT count(*) as total FROM %i", $old_table_name));
		$loop_time = ceil($total_rows / $limit);

		for ($i = 0; $i < $loop_time; $i++) {
			if (in_array($i, $chunk_complted)) {
				continue;
			}

			$offset = $i * $limit;
			$line_result = $wpdb->query($wpdb->prepare(
				"INSERT IGNORE INTO %i SELECT * FROM %i LIMIT %d OFFSET %d;",
				$new_table_name,
				$old_table_name,
				$limit,
				$offset
			));

			if ($line_result) {
				$chunk_complted[] = $i;
			}
		}

		update_option($option_key, $chunk_complted);

		if (!$old_table_exists) {
			$wpdb->query($wpdb->prepare(
				"RENAME TABLE %i TO %i, %i TO %i;",
				$old_table_name,
				$old_table_name . '_old',
				$new_table_name,
				$old_table_name
			));
		}

		exit;
	}
}

new Migrate();
