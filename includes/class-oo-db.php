<?php
// /includes/class-oo-db.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_DB { // Renamed class

    private static $employees_table;
    private static $phases_table;
    private static $job_logs_table;
    private static $stream_types_table; // New table
    // Potentially: private static $jobs_table, $job_streams_table in the future

    public static function init() {
        global $wpdb;
        // Define table names with new prefix
        self::$employees_table = $wpdb->prefix . 'oo_employees';
        self::$phases_table = $wpdb->prefix . 'oo_phases';
        self::$job_logs_table = $wpdb->prefix . 'oo_job_logs';
        self::$stream_types_table = $wpdb->prefix . 'oo_stream_types';
    }

    /**
     * Create/update custom database tables.
     */
    public static function create_tables() {
        oo_log('Attempting to create/update database tables...', __METHOD__); // Use new log function
        self::init();
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // SQL for oo_employees table (formerly ejpt_employees)
        $sql_employees = "CREATE TABLE " . self::$employees_table . " (
            employee_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_number VARCHAR(50) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (employee_id),
            UNIQUE KEY uq_employee_number (employee_number),
            INDEX idx_is_active (is_active)
        ) $charset_collate;";

        // SQL for oo_stream_types table (NEW)
        $sql_stream_types = "CREATE TABLE " . self::$stream_types_table . " (
            stream_type_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            stream_type_slug VARCHAR(50) NOT NULL,
            stream_type_name VARCHAR(100) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            kpi_fields_config TEXT NULLABLE, /* JSON for KPI fields relevant to this stream type */
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (stream_type_id),
            UNIQUE KEY uq_stream_type_slug (stream_type_slug)
        ) $charset_collate;";

        // SQL for oo_phases table (formerly ejpt_phases, now with stream_type_id)
        $sql_phases = "CREATE TABLE " . self::$phases_table . " (
            phase_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            stream_type_id INT UNSIGNED NOT NULL, /* FK to oo_stream_types */
            phase_slug VARCHAR(100) NOT NULL, /* Unique within a stream_type */
            phase_name VARCHAR(100) NOT NULL,
            phase_description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (phase_id),
            UNIQUE KEY uq_stream_phase_slug (stream_type_id, phase_slug),
            INDEX idx_stream_type_id (stream_type_id),
            INDEX idx_is_active (is_active)
            /* FOREIGN KEY (stream_type_id) REFERENCES " . self::$stream_types_table . "(stream_type_id) ON DELETE CASCADE ON UPDATE CASCADE */
            /* Add FK constraints after all tables are defined to avoid order issues with dbDelta */
        ) $charset_collate;";

        // SQL for oo_job_logs table (formerly ejpt_job_logs)
        // Will need kpi_data JSON field, and potentially job_stream_id instead of job_number later
        $sql_job_logs = "CREATE TABLE " . self::$job_logs_table . " (
            log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id BIGINT UNSIGNED NOT NULL, /* FK to oo_employees */
            job_number VARCHAR(50) NOT NULL, /* For now, keep job_number; will link to a future jobs table */
            phase_id INT UNSIGNED NOT NULL, /* FK to oo_phases */
            start_time DATETIME NOT NULL,
            end_time DATETIME NULL,
            boxes_completed INT UNSIGNED NULL, /* Will become part of kpi_data or be conditional */
            items_completed INT UNSIGNED NULL, /* Will become part of kpi_data or be conditional */
            kpi_data JSON NULL, /* For flexible KPIs */
            status VARCHAR(20) NOT NULL DEFAULT 'started',
            notes TEXT NULL,
            PRIMARY KEY (log_id),
            INDEX idx_employee_id (employee_id),
            INDEX idx_job_number (job_number),
            INDEX idx_phase_id (phase_id),
            INDEX idx_start_time (start_time),
            INDEX idx_end_time (end_time),
            INDEX idx_status (status)
            /* FOREIGN KEY (employee_id) REFERENCES " . self::$employees_table . "(employee_id) ON DELETE RESTRICT ON UPDATE CASCADE, */
            /* FOREIGN KEY (phase_id) REFERENCES " . self::$phases_table . "(phase_id) ON DELETE RESTRICT ON UPDATE CASCADE */
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        oo_log('Running dbDelta for employees table (' . self::$employees_table . ').', __METHOD__);
        dbDelta( $sql_employees );
        oo_log('Running dbDelta for stream types table (' . self::$stream_types_table . ').', __METHOD__);
        dbDelta( $sql_stream_types );
        oo_log('Running dbDelta for phases table (' . self::$phases_table . ').', __METHOD__);
        dbDelta( $sql_phases );
        oo_log('Running dbDelta for job logs table (' . self::$job_logs_table . ').', __METHOD__);
        dbDelta( $sql_job_logs );
        
        // Add foreign key constraints separately after tables are created/updated by dbDelta
        // This avoids issues if tables are created in a different order by dbDelta
        // Note: dbDelta does not add foreign keys itself. This needs to be run manually or via separate $wpdb->query calls.
        // For now, these are commented out, as direct $wpdb->query for ALTER TABLE is better after dbDelta.
        /*
        $wpdb->query("ALTER TABLE " . self::$phases_table . " ADD CONSTRAINT fk_phases_stream_type FOREIGN KEY (stream_type_id) REFERENCES " . self::$stream_types_table . "(stream_type_id) ON DELETE CASCADE ON UPDATE CASCADE;");
        $wpdb->query("ALTER TABLE " . self::$job_logs_table . " ADD CONSTRAINT fk_logs_employee FOREIGN KEY (employee_id) REFERENCES " . self::$employees_table . "(employee_id) ON DELETE RESTRICT ON UPDATE CASCADE;");
        $wpdb->query("ALTER TABLE " . self::$job_logs_table . " ADD CONSTRAINT fk_logs_phase FOREIGN KEY (phase_id) REFERENCES " . self::$phases_table . "(phase_id) ON DELETE RESTRICT ON UPDATE CASCADE;");
        */
        oo_log('Finished creating/updating database tables. Manual checks for foreign keys might be needed if dbDelta limitations apply.', __METHOD__);
    }

    // ... TODO: Rename all EJPT_DB methods to OO_DB, update table name references, and adapt logic ...
    // For example, add_employee, get_employees will use self::$employees_table (which is now oo_employees)
    // Methods for phases will need to accept/filter by stream_type_id
    // Methods for job_logs will need to handle new kpi_data field

// Placeholder for existing methods that need renaming and refactoring:
// ... (copy existing EJPT_DB methods here and then rename/refactor them one by one) ...

    // --- Employee CRUD Methods ---
    public static function add_employee( $employee_number, $first_name, $last_name ) {
        oo_log('Attempting to add employee.', __METHOD__);
        oo_log(array('employee_number' => $employee_number, 'first_name' => $first_name, 'last_name' => $last_name), __METHOD__);
        self::init(); // Ensures self::$employees_table is set with new prefix
        global $wpdb;
        if (empty($employee_number) || empty($first_name) || empty($last_name)) {
            $error = new WP_Error('missing_fields', 'All fields (Employee Number, First Name, Last Name) are required.');
            oo_log('Error adding employee: Missing fields.', $error);
            return $error;
        }
        $exists = $wpdb->get_var( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE employee_number = %s", $employee_number) );
        if ($exists) {
            $error = new WP_Error('employee_exists', 'Employee number already exists.');
            oo_log('Error adding employee: Employee number exists.', $error);
            return $error;
        }
        $result = $wpdb->insert(self::$employees_table, array('employee_number' => sanitize_text_field($employee_number), 'first_name' => sanitize_text_field($first_name), 'last_name' => sanitize_text_field($last_name), 'is_active' => 1, 'created_at' => current_time('mysql', 1)), array('%s', '%s', '%s', '%d', '%s'));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not add employee. Error: ' . $wpdb->last_error);
            oo_log('Error adding employee: DB insert failed.', $error);
            oo_log('WPDB Last Error: ' . $wpdb->last_error, __METHOD__);
            return $error;
        }
        oo_log('Employee added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function get_employee( $employee_id ) {
        oo_log('Attempting to get employee by ID: ' . $employee_id, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$employees_table . " WHERE employee_id = %d", $employee_id ) );
        oo_log('Result for get_employee: ', $result);
        return $result;
    }
    
    public static function get_employee_by_number( $employee_number ) {
        oo_log('Attempting to get employee by number: ' . $employee_number, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$employees_table . " WHERE employee_number = %s", $employee_number ) );
        oo_log('Result for get_employee_by_number: ', $result);
        return $result;
    }

    public static function update_employee( $employee_id, $employee_number, $first_name, $last_name, $is_active = null ) {
        oo_log('Attempting to update employee ID: ' . $employee_id, __METHOD__);
        oo_log(compact('employee_id', 'employee_number', 'first_name', 'last_name', 'is_active'), __METHOD__);
        self::init();
        global $wpdb;
        if (empty($employee_number) || empty($first_name) || empty($last_name)) {
            $error = new WP_Error('missing_fields', 'All fields (Employee Number, First Name, Last Name) are required.');
            oo_log('Error updating employee: Missing fields.', $error);
            return $error;
        }
        $existing_employee = $wpdb->get_row( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE employee_number = %s AND employee_id != %d", $employee_number, $employee_id) );
        if ($existing_employee) {
            $error = new WP_Error('employee_number_exists', 'This employee number is already assigned to another employee.');
            oo_log('Error updating employee: Employee number exists for another ID.', $error);
            return $error;
        }
        $data = array('employee_number' => sanitize_text_field($employee_number), 'first_name' => sanitize_text_field($first_name), 'last_name' => sanitize_text_field($last_name));
        $formats = array('%s', '%s', '%s');
        if ( !is_null($is_active) ) { $data['is_active'] = intval($is_active); $formats[] = '%d'; }
        $result = $wpdb->update(self::$employees_table, $data, array( 'employee_id' => $employee_id ), $formats, array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update employee. Error: ' . $wpdb->last_error);
            oo_log('Error updating employee: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Employee updated successfully. ID: ' . $employee_id, __METHOD__);
        return true;
    }

    public static function toggle_employee_status( $employee_id, $is_active ) {
        oo_log('Toggling employee status for ID: ' . $employee_id . ' to ' . $is_active, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->update(self::$employees_table, array( 'is_active' => intval($is_active) ), array( 'employee_id' => $employee_id ), array( '%d' ), array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update employee status. Error: ' . $wpdb->last_error);
            oo_log('Error toggling employee status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Employee status toggled successfully for ID: ' . $employee_id, __METHOD__);
        return true;
    }

    public static function get_employees( $args = array() ) {
        oo_log('Attempting to get employees with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('is_active' => null, 'orderby' => 'last_name', 'order' => 'ASC', 'search' => '', 'number' => -1, 'offset' => 0);
        $args = wp_parse_args($args, $defaults);
        $sql_base = "SELECT * FROM " . self::$employees_table;
        $where_clauses = array(); $query_params = array();
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = $args['is_active']; }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(employee_number LIKE %s OR first_name LIKE %s OR last_name LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $orderby_clause = ""; if (!empty($args['orderby'])) { $orderby_val = sanitize_sql_orderby($args['orderby']); if ($orderby_val) { $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'; $orderby_clause = " ORDER BY $orderby_val $order_val"; }}
        $sql .= $orderby_clause;
        $limit_clause = ""; if ( isset($args['number']) && $args['number'] > 0 ) { $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));}
        $sql .= $limit_clause;
        $results = $wpdb->get_results( $sql );
        oo_log('Get employees query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }
    
    public static function get_employees_count( $args = array() ) {
        oo_log('Attempting to get employees count with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('is_active' => null, 'search' => ''); $args = wp_parse_args($args, $defaults);
        $sql = "SELECT COUNT(*) FROM " . self::$employees_table;
        $where_clauses = array(); $query_params = array();
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = $args['is_active']; }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(employee_number LIKE %s OR first_name LIKE %s OR last_name LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        if ( !empty($where_clauses) ) { $sql .= " WHERE " . implode(" AND ", $where_clauses);}
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $count = $wpdb->get_var( $sql );
        oo_log('Employees count result: ' . $count . ' SQL: ' . $sql, __METHOD__);
        return $count;
    }

    // ... (Placeholders for Stream Type, Phase, and Job Log methods to be refactored/added next)
    // ... existing code ...

    // --- Stream Type CRUD Methods ---
    public static function add_stream_type($slug, $name, $kpi_config = null, $is_active = 1) {
        oo_log('Attempting to add stream type.', __METHOD__);
        oo_log(compact('slug', 'name', 'kpi_config', 'is_active'), __METHOD__);
        self::init(); global $wpdb;
        if (empty($slug) || empty($name)) {
            $error = new WP_Error('missing_fields', 'Stream Type Slug and Name are required.');
            oo_log('Error adding stream type: Missing fields.', $error);
            return $error;
        }
        $exists = $wpdb->get_var( $wpdb->prepare("SELECT stream_type_id FROM " . self::$stream_types_table . " WHERE stream_type_slug = %s", $slug) );
        if ($exists) {
            $error = new WP_Error('stream_type_exists', 'Stream Type slug already exists.');
            oo_log('Error adding stream type: Slug exists.', $error);
            return $error;
        }
        $data = array(
            'stream_type_slug' => sanitize_key($slug),
            'stream_type_name' => sanitize_text_field($name),
            'is_active' => intval($is_active),
            'created_at' => current_time('mysql', 1)
        );
        $formats = array('%s', '%s', '%d', '%s');
        if (!is_null($kpi_config)) {
            // Ensure kpi_config is a JSON string; could be an array/object from code, or already JSON string from form.
            $kpi_json = is_string($kpi_config) ? $kpi_config : wp_json_encode($kpi_config);
            if ($kpi_json === false) { // Check for JSON encoding error
                 oo_log('Warning: KPI config JSON encoding failed for stream type: ' . $slug . '. Storing NULL.', __METHOD__);
                 $data['kpi_fields_config'] = null;
            } else {
                 $data['kpi_fields_config'] = $kpi_json;
            }
            $formats[] = '%s';
        } else {
            $data['kpi_fields_config'] = null;
            $formats[] = '%s';
        }
        $result = $wpdb->insert(self::$stream_types_table, $data, $formats);
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not add stream type. Error: ' . $wpdb->last_error);
            oo_log('Error adding stream type: DB insert failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Stream type added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function get_stream_type($stream_type_id) {
        oo_log('Attempting to get stream type by ID: ' . $stream_type_id, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::$stream_types_table . " WHERE stream_type_id = %d", $stream_type_id));
        oo_log('Result for get_stream_type: ', $result);
        return $result;
    }
    
    public static function get_stream_type_by_slug($slug) {
        oo_log('Attempting to get stream type by slug: ' . $slug, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::$stream_types_table . " WHERE stream_type_slug = %s", $slug));
        oo_log('Result for get_stream_type_by_slug: ', $result);
        return $result;
    }

    public static function update_stream_type($stream_type_id, $slug, $name, $kpi_config = null, $is_active = null) {
        oo_log('Attempting to update stream type ID: ' . $stream_type_id, __METHOD__);
        oo_log(compact('stream_type_id', 'slug', 'name', 'kpi_config', 'is_active'), __METHOD__);
        self::init(); global $wpdb;

        if (empty($slug) || empty($name)) {
            $error = new WP_Error('missing_fields', 'Stream Type Slug and Name are required.');
            oo_log('Error updating stream type: Missing fields.', $error);
            return $error;
        }

        $existing = $wpdb->get_row( $wpdb->prepare("SELECT stream_type_id FROM " . self::$stream_types_table . " WHERE stream_type_slug = %s AND stream_type_id != %d", $slug, $stream_type_id) );
        if ($existing) {
            $error = new WP_Error('stream_type_slug_exists', 'Stream Type slug already exists for another type.');
            oo_log('Error updating stream type: Slug exists for another ID.', $error);
            return $error;
        }

        $data = array(
            'stream_type_slug' => sanitize_key($slug),
            'stream_type_name' => sanitize_text_field($name),
        );
        $formats = array('%s', '%s');

        if (array_key_exists('kpi_fields_config', compact('kpi_config'))) { // Check if kpi_config was passed as an argument
            $kpi_json = is_string($kpi_config) ? $kpi_config : wp_json_encode($kpi_config);
            if ($kpi_json === false) {
                oo_log('Warning: KPI config JSON encoding failed for stream type update: ' . $slug . '. Storing NULL.', __METHOD__);
                $data['kpi_fields_config'] = null;
            } else {
                $data['kpi_fields_config'] = $kpi_json;
            }
            $formats[] = '%s';
        }

        if (!is_null($is_active)) {
            $data['is_active'] = intval($is_active);
            $formats[] = '%d';
        }

        if (empty($data)) {
            oo_log('No data to update for stream type ID: ' . $stream_type_id, __METHOD__);
            return true; // Or WP_Error if this should be an error
        }

        $result = $wpdb->update(self::$stream_types_table, $data, array('stream_type_id' => $stream_type_id), $formats, array('%d'));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update stream type. Error: ' . $wpdb->last_error);
            oo_log('Error updating stream type: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Stream type updated successfully. ID: ' . $stream_type_id, __METHOD__);
        return true;
    }

    public static function toggle_stream_type_status( $stream_type_id, $is_active ) {
        oo_log('Toggling stream type status for ID: ' . $stream_type_id . ' to ' . $is_active, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->update(self::$stream_types_table, array( 'is_active' => intval($is_active) ), array( 'stream_type_id' => $stream_type_id ), array( '%d' ), array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update stream type status. Error: ' . $wpdb->last_error);
            oo_log('Error toggling stream type status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Stream type status toggled successfully for ID: ' . $stream_type_id, __METHOD__);
        return true;
    }

    public static function get_stream_types( $args = array() ) {
        oo_log('Attempting to get stream types with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('is_active' => null, 'orderby' => 'stream_type_name', 'order' => 'ASC', 'number' => -1, 'offset' => 0);
        $args = wp_parse_args($args, $defaults);
        $sql_base = "SELECT * FROM " . self::$stream_types_table;
        $where_clauses = array(); $query_params = array();
        if (!is_null($args['is_active'])) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $orderby_clause = ""; if (!empty($args['orderby'])) { $orderby_val = sanitize_sql_orderby($args['orderby']); if ($orderby_val) { $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'; $orderby_clause = " ORDER BY $orderby_val $order_val"; }}
        $sql .= $orderby_clause;
        $limit_clause = ""; if ( isset($args['number']) && $args['number'] > 0 ) { $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));}
        $sql .= $limit_clause;
        $results = $wpdb->get_results($sql);
        oo_log('Get stream types query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }

    public static function get_stream_types_count( $args = array() ) {
        oo_log('Attempting to get stream types count with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('is_active' => null);
        $args = wp_parse_args($args, $defaults);
        $sql = "SELECT COUNT(*) FROM " . self::$stream_types_table;
        $where_clauses = array(); $query_params = array();
        if (!is_null($args['is_active'])) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }
        if ( !empty($where_clauses) ) { $sql .= " WHERE " . implode(" AND ", $where_clauses);}
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $count = $wpdb->get_var($sql);
        oo_log('Stream types count result: ' . $count . ' SQL: ' . $sql, __METHOD__);
        return $count;
    }

    // --- Phase CRUD Methods (adapted for stream_type_id) ---
    public static function add_phase( $stream_type_id, $phase_slug, $phase_name, $phase_description = '', $sort_order = 0, $is_active = 1 ) {
        oo_log('Attempting to add phase.', __METHOD__);
        oo_log(compact('stream_type_id', 'phase_slug', 'phase_name', 'phase_description', 'sort_order', 'is_active'), __METHOD__);
        self::init(); global $wpdb;
        if (empty($stream_type_id) || empty($phase_slug) || empty($phase_name)) {
            $error = new WP_Error('missing_fields', 'Stream Type ID, Phase Slug, and Phase Name are required.');
            oo_log('Error adding phase: Missing fields.', $error);
            return $error;
        }
        $exists = $wpdb->get_var( $wpdb->prepare("SELECT phase_id FROM " . self::$phases_table . " WHERE stream_type_id = %d AND phase_slug = %s", $stream_type_id, $phase_slug) );
        if ($exists) {
            $error = new WP_Error('phase_exists', 'Phase slug already exists for this stream type.');
            oo_log('Error adding phase: Slug exists for stream type.', $error);
            return $error;
        }
        $result = $wpdb->insert(self::$phases_table, 
            array(
                'stream_type_id'    => intval($stream_type_id),
                'phase_slug'        => sanitize_key($phase_slug),
                'phase_name'        => sanitize_text_field($phase_name), 
                'phase_description' => sanitize_textarea_field($phase_description),
                'sort_order'        => intval($sort_order),
                'is_active'         => intval($is_active), 
                'created_at'        => current_time('mysql', 1)
            ), 
            array('%d', '%s', '%s', '%s', '%d', '%d', '%s')
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not add phase. Error: ' . $wpdb->last_error);
            oo_log('Error adding phase: DB insert failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Phase added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function get_phase( $phase_id ) {
        oo_log('Attempting to get phase by ID: ' . $phase_id, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$phases_table . " WHERE phase_id = %d", $phase_id ) );
        oo_log('Result for get_phase: ', $result);
        return $result;
    }

    public static function update_phase( $phase_id, $stream_type_id, $phase_slug, $phase_name, $phase_description = '', $sort_order = null, $is_active = null ) {
        oo_log('Attempting to update phase ID: ' . $phase_id, __METHOD__);
        oo_log(compact('phase_id', 'stream_type_id', 'phase_slug', 'phase_name', 'phase_description', 'sort_order', 'is_active'), __METHOD__);
        self::init(); global $wpdb;

        if (empty($stream_type_id) || empty($phase_slug) || empty($phase_name)) {
            $error = new WP_Error('missing_fields', 'Stream Type ID, Phase Slug, and Phase Name are required for update.');
            oo_log('Error updating phase: Missing fields.', $error);
            return $error;
        }

        $existing_phase = $wpdb->get_row( $wpdb->prepare("SELECT phase_id FROM " . self::$phases_table . " WHERE stream_type_id = %d AND phase_slug = %s AND phase_id != %d", $stream_type_id, $phase_slug, $phase_id) );
        if ($existing_phase) {
            $error = new WP_Error('phase_slug_exists', 'This phase slug is already in use for this stream type on another phase.');
            oo_log('Error updating phase: Slug exists for stream type on another phase.', $error);
            return $error;
        }

        $data = array(
            'stream_type_id'    => intval($stream_type_id),
            'phase_slug'        => sanitize_key($phase_slug),
            'phase_name'        => sanitize_text_field($phase_name),
            'phase_description' => sanitize_textarea_field($phase_description),
        );
        $formats = array('%d', '%s', '%s', '%s');

        if (!is_null($sort_order)) { $data['sort_order'] = intval($sort_order); $formats[] = '%d'; }
        if (!is_null($is_active)) { $data['is_active'] = intval($is_active); $formats[] = '%d'; }

        $result = $wpdb->update(self::$phases_table, $data, array( 'phase_id' => $phase_id ), $formats, array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update phase. Error: ' . $wpdb->last_error);
            oo_log('Error updating phase: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Phase updated successfully. ID: ' . $phase_id, __METHOD__);
        return true;
    }

    public static function toggle_phase_status( $phase_id, $is_active ) {
        oo_log('Toggling phase status for ID: ' . $phase_id . ' to ' . $is_active, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->update(self::$phases_table, array( 'is_active' => intval($is_active) ), array( 'phase_id' => $phase_id ), array( '%d' ), array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update phase status. Error: ' . $wpdb->last_error);
            oo_log('Error toggling phase status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Phase status toggled successfully for ID: ' . $phase_id, __METHOD__);
        return true;
    }

    public static function get_phases( $args = array() ) { 
        oo_log('Attempting to get phases with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('stream_type_id' => null, 'is_active' => null, 'orderby' => 'sort_order', 'order' => 'ASC', 'search' => '', 'number' => -1, 'offset' => 0);
        $args = wp_parse_args($args, $defaults);
        $sql_base = "SELECT * FROM " . self::$phases_table;
        $where_clauses = array(); $query_params = array();
        if ( !empty($args['stream_type_id']) ) { $where_clauses[] = "stream_type_id = %d"; $query_params[] = intval($args['stream_type_id']);}
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(phase_name LIKE %s OR phase_description LIKE %s OR phase_slug LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $orderby_clause = ""; if (!empty($args['orderby'])) { $orderby_val = sanitize_sql_orderby($args['orderby']); if ($orderby_val) { $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'; $orderby_clause = " ORDER BY $orderby_val $order_val"; }}
        $sql .= $orderby_clause;
        $limit_clause = ""; if ( isset($args['number']) && $args['number'] > 0 ) { $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));}
        $sql .= $limit_clause;
        $results = $wpdb->get_results( $sql );
        oo_log('Get phases query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }
    
    public static function get_phases_count( $args = array() ) {
        oo_log('Attempting to get phases count with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('stream_type_id' => null, 'is_active' => null, 'search' => ''); $args = wp_parse_args($args, $defaults);
        $sql = "SELECT COUNT(*) FROM " . self::$phases_table;
        $where_clauses = array(); $query_params = array();
        if ( !empty($args['stream_type_id']) ) { $where_clauses[] = "stream_type_id = %d"; $query_params[] = intval($args['stream_type_id']);}
        if ( !is_null($args['is_active']) ) { $where_clauses[] = "is_active = %d"; $query_params[] = intval($args['is_active']); }
        if ( !empty($args['search']) ) { $search_term = '%' . $wpdb->esc_like($args['search']) . '%'; $where_clauses[] = "(phase_name LIKE %s OR phase_description LIKE %s OR phase_slug LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        if ( !empty($where_clauses) ) { $sql .= " WHERE " . implode(" AND ", $where_clauses);}
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $count = $wpdb->get_var( $sql );
        oo_log('Phases count result: ' . $count . ' SQL: ' . $sql, __METHOD__);
        return $count;
    }

    // --- Job Log CRUD Methods (adapted for kpi_data) ---
    public static function start_job_phase( $employee_id, $job_number, $phase_id, $notes = '', $kpi_data = null ) {
        oo_log('Attempting to start job phase.', __METHOD__);
        oo_log(compact('employee_id', 'job_number', 'phase_id', 'notes', 'kpi_data'), __METHOD__);
        self::init(); global $wpdb;
        if (empty($employee_id) || empty($job_number) || empty($phase_id)) {
            $error = new WP_Error('missing_fields', 'Employee, Job Number, and Phase are required.');
            oo_log('Error starting job: Missing fields.', $error);
            return $error;
        }
        
        $log_to_check = self::get_open_job_log($employee_id, $job_number, $phase_id);
        if ($log_to_check) {
             $error = new WP_Error('already_started', 'This employee has already started this job phase and it has not been stopped yet. Log ID: ' . $log_to_check->log_id);
            oo_log('Error starting job: Already started.', $error);
            return $error;
        }

        $insert_data = array(
            'employee_id' => intval($employee_id),
            'job_number'  => sanitize_text_field($job_number),
            'phase_id'    => intval($phase_id),
            'start_time'  => current_time('mysql', 1),
            'status'      => 'started',
            'notes'       => sanitize_textarea_field($notes)
        );
        $formats = array('%d', '%s', '%d', '%s', '%s', '%s');
        
        if (!is_null($kpi_data) && is_array($kpi_data)) {
            if(isset($kpi_data['boxes_completed'])) {
                $insert_data['boxes_completed'] = intval($kpi_data['boxes_completed']);
                $formats[] = '%d'; 
            } else { $insert_data['boxes_completed'] = null; $formats[] = null; }
            if(isset($kpi_data['items_completed'])) {
                $insert_data['items_completed'] = intval($kpi_data['items_completed']);
                $formats[] = '%d';
            } else { $insert_data['items_completed'] = null; $formats[] = null; }
            $insert_data['kpi_data'] = wp_json_encode($kpi_data);
            $formats[] = '%s';
        } else {
            $insert_data['boxes_completed'] = null; $formats[] = null; 
            $insert_data['items_completed'] = null; $formats[] = null; 
            $insert_data['kpi_data'] = null; $formats[] = null; 
        }
         // Clean up null formats that were added if kpi_data was null or individual keys not set
        $formats = array_values(array_filter($formats, function($f) { return !is_null($f); }));

        $result = $wpdb->insert(self::$job_logs_table, $insert_data, $formats);
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not start job phase. Error: ' . $wpdb->last_error);
            oo_log('Error starting job phase: DB insert failed. ' . $wpdb->last_error . ' Data: ' . print_r($insert_data, true) . ' Formats: ' . print_r($formats, true), $error);
            return $error;
        }
        oo_log('Job phase started successfully. Log ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function stop_job_phase( $employee_id, $job_number, $phase_id, $kpi_data_updates = array(), $notes = '' ) {
        oo_log('Attempting to stop job phase.', __METHOD__);
        oo_log(compact('employee_id', 'job_number', 'phase_id', 'kpi_data_updates', 'notes'), __METHOD__);
        self::init(); global $wpdb;
        if (empty($employee_id) || empty($job_number) || empty($phase_id)) {
            $error = new WP_Error('missing_fields', 'Employee, Job Number, and Phase are required to stop a job.');
            oo_log('Error stopping job: Missing fields.', $error);
            return $error;
        }

        $log_to_update = self::get_open_job_log($employee_id, $job_number, $phase_id);
        if ( !$log_to_update ) {
            $error = new WP_Error('no_start_record', 'No matching \'started\' record found for this employee, job, and phase. Cannot stop.');
            oo_log('Error stopping job: No start record found.', $error);
            return $error;
        }

        $update_data = array(
            'end_time' => current_time('mysql', 1),
            'status'   => 'completed',
        );
        $formats = array('%s', '%s');
        
        $existing_kpi_data = !empty($log_to_update->kpi_data) ? json_decode($log_to_update->kpi_data, true) : array();
        if(!is_array($existing_kpi_data)) $existing_kpi_data = array();
        $final_kpi_data = array_merge($existing_kpi_data, $kpi_data_updates);

        $update_data['kpi_data'] = wp_json_encode($final_kpi_data);
        $formats[] = '%s';

        if (isset($kpi_data_updates['boxes_completed'])) {
            $update_data['boxes_completed'] = intval($kpi_data_updates['boxes_completed']); $formats[] = '%d';
        } elseif (array_key_exists('boxes_completed', $kpi_data_updates) && is_null($kpi_data_updates['boxes_completed'])) {
            $update_data['boxes_completed'] = null; $formats[] = '%s'; // format for NULL should be %s or handled by $wpdb
        } else if (!isset($final_kpi_data['boxes_completed'])) { // If not in new data and not in old, ensure it can be nulled if column allows
             $update_data['boxes_completed'] = null; $formats[] = '%s';
        }

        if (isset($kpi_data_updates['items_completed'])) {
            $update_data['items_completed'] = intval($kpi_data_updates['items_completed']); $formats[] = '%d';
        } elseif (array_key_exists('items_completed', $kpi_data_updates) && is_null($kpi_data_updates['items_completed'])) {
            $update_data['items_completed'] = null; $formats[] = '%s';
        } else if (!isset($final_kpi_data['items_completed'])) {
             $update_data['items_completed'] = null; $formats[] = '%s';
        }

        if (isset($notes)) { // Allow clearing notes if an empty string is explicitly passed
            $update_data['notes'] = sanitize_textarea_field($notes);
            $formats[] = '%s';
        }

        $result = $wpdb->update(self::$job_logs_table, $update_data, array( 'log_id' => $log_to_update->log_id ), $formats, array('%d'));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not stop job phase. Error: ' . $wpdb->last_error);
            oo_log('Error stopping job phase: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Job phase stopped successfully. Log ID: ' . $log_to_update->log_id, __METHOD__);
        return true;
    }

    public static function get_job_log( $log_id ) {
        oo_log('Attempting to get job log by ID: ' . $log_id, __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$job_logs_table . " WHERE log_id = %d", $log_id ) );
        oo_log('Result for get_job_log: ', $result);
        return $result;
    }

    public static function get_job_logs( $args = array() ) {
        oo_log('Attempting to get job logs with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('employee_id' => null, 'job_number' => null, 'phase_id' => null, 'stream_type_id' => null, 'date_from' => null, 'date_to' => null, 'status' => null, 'orderby' => 'jl.start_time', 'order' => 'DESC', 'number' => 25, 'offset' => 0, 'search_general' => '');
        $args = wp_parse_args($args, $defaults);
        $sql_select = "SELECT jl.*, e.first_name, e.last_name, e.employee_number, p.phase_name, p.stream_type_id, st.stream_type_name, st.stream_type_slug";
        $sql_from = " FROM " . self::$job_logs_table . " jl LEFT JOIN " . self::$employees_table . " e ON jl.employee_id = e.employee_id LEFT JOIN " . self::$phases_table . " p ON jl.phase_id = p.phase_id LEFT JOIN " . self::$stream_types_table . " st ON p.stream_type_id = st.stream_type_id";
        $sql_base = $sql_select . $sql_from;
        $where_clauses = array(); $query_params = array();
        if ( !empty($args['employee_id']) ) { $where_clauses[] = "jl.employee_id = %d"; $query_params[] = $args['employee_id'];}
        if ( !empty($args['job_number']) ) { $where_clauses[] = "jl.job_number = %s"; $query_params[] = sanitize_text_field($args['job_number']);}
        if ( !empty($args['phase_id']) ) { $where_clauses[] = "jl.phase_id = %d"; $query_params[] = $args['phase_id'];}
        if ( !empty($args['stream_type_id']) ) { $where_clauses[] = "p.stream_type_id = %d"; $query_params[] = intval($args['stream_type_id']);}
        if ( !empty($args['date_from']) ) { $where_clauses[] = "jl.start_time >= %s"; $query_params[] = sanitize_text_field($args['date_from']) . ' 00:00:00';}
        if ( !empty($args['date_to']) ) { $date_to_end_of_day = sanitize_text_field($args['date_to']) . ' 23:59:59'; if (!empty($args['date_from'])) { $date_from_start_of_day = sanitize_text_field($args['date_from']) . ' 00:00:00'; $where_clauses[] = "( (jl.start_time <= %s AND (jl.end_time IS NULL OR jl.end_time >= %s)) OR (jl.start_time >= %s AND jl.start_time <= %s) )"; $query_params[] = $date_to_end_of_day; $query_params[] = $date_from_start_of_day; $query_params[] = $date_from_start_of_day; $query_params[] = $date_to_end_of_day; } else { $where_clauses[] = "jl.start_time <= %s"; $query_params[] = $date_to_end_of_day; }}
        if ( !empty($args['status']) ) { $where_clauses[] = "jl.status = %s"; $query_params[] = sanitize_text_field($args['status']);}
        if ( !empty($args['search_general']) ) { $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search_general'])) . '%'; $where_clauses[] = "(jl.job_number LIKE %s OR e.first_name LIKE %s OR e.last_name LIKE %s OR e.employee_number LIKE %s OR p.phase_name LIKE %s OR jl.notes LIKE %s OR st.stream_type_name LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $orderby_clause = ""; if (!empty($args['orderby'])) { $allowed_orderby = array('jl.start_time', 'jl.end_time', 'e.last_name', 'e.first_name', 'e.employee_number', 'jl.job_number', 'p.phase_name', 'st.stream_type_name', 'jl.status', 'jl.boxes_completed', 'jl.items_completed'); $orderby_input = $args['orderby']; $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'; if ($orderby_input === 'e.last_name' || $orderby_input === 'e.first_name') { $orderby_clause = " ORDER BY e.last_name $order_val, e.first_name $order_val"; } elseif (in_array($orderby_input, $allowed_orderby)) { $orderby_clause = " ORDER BY $orderby_input $order_val"; } else { $orderby_clause = " ORDER BY jl.start_time $order_val"; }}
        $sql .= $orderby_clause;
        $limit_clause = ""; if ( isset($args['number']) && $args['number'] > 0 ) { $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));}
        $sql .= $limit_clause;
        $results = $wpdb->get_results( $sql );
        oo_log('Get job_logs query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }

    public static function get_job_logs_count( $args = array() ) {
        oo_log('Attempting to get job logs count with args:', __METHOD__); oo_log($args, __METHOD__);
        self::init(); global $wpdb;
        $defaults = array('employee_id' => null, 'job_number' => null, 'phase_id' => null, 'stream_type_id' => null, 'date_from' => null, 'date_to' => null, 'status' => null, 'search_general' => '');
        $args = wp_parse_args($args, $defaults);
        $sql_select = "SELECT COUNT(jl.log_id)";
        $sql_from = " FROM " . self::$job_logs_table . " jl LEFT JOIN " . self::$employees_table . " e ON jl.employee_id = e.employee_id LEFT JOIN " . self::$phases_table . " p ON jl.phase_id = p.phase_id LEFT JOIN " . self::$stream_types_table . " st ON p.stream_type_id = st.stream_type_id";
        $sql_base = $sql_select . $sql_from;
        $where_clauses = array(); $query_params = array();
        if ( !empty($args['employee_id']) ) { $where_clauses[] = "jl.employee_id = %d"; $query_params[] = $args['employee_id'];}
        if ( !empty($args['job_number']) ) { $where_clauses[] = "jl.job_number = %s"; $query_params[] = sanitize_text_field($args['job_number']);}
        if ( !empty($args['phase_id']) ) { $where_clauses[] = "jl.phase_id = %d"; $query_params[] = $args['phase_id'];}
        if ( !empty($args['stream_type_id']) ) { $where_clauses[] = "p.stream_type_id = %d"; $query_params[] = intval($args['stream_type_id']);}
        if ( !empty($args['date_from']) ) { $where_clauses[] = "jl.start_time >= %s"; $query_params[] = sanitize_text_field($args['date_from']) . ' 00:00:00';}
        if ( !empty($args['date_to']) ) { $date_to_end_of_day = sanitize_text_field($args['date_to']) . ' 23:59:59'; if (!empty($args['date_from'])) { $date_from_start_of_day = sanitize_text_field($args['date_from']) . ' 00:00:00'; $where_clauses[] = "( (jl.start_time <= %s AND (jl.end_time IS NULL OR jl.end_time >= %s)) OR (jl.start_time >= %s AND jl.start_time <= %s) )"; $query_params[] = $date_to_end_of_day; $query_params[] = $date_from_start_of_day; $query_params[] = $date_from_start_of_day; $query_params[] = $date_to_end_of_day; } else { $where_clauses[] = "jl.start_time <= %s"; $query_params[] = $date_to_end_of_day; }}
        if ( !empty($args['status']) ) { $where_clauses[] = "jl.status = %s"; $query_params[] = sanitize_text_field($args['status']);}
        if ( !empty($args['search_general']) ) { $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search_general'])) . '%'; $where_clauses[] = "(jl.job_number LIKE %s OR e.first_name LIKE %s OR e.last_name LIKE %s OR e.employee_number LIKE %s OR p.phase_name LIKE %s OR jl.notes LIKE %s OR st.stream_type_name LIKE %s)"; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term; $query_params[] = $search_term;}
        $sql_where = ""; if ( !empty($where_clauses) ) { $sql_where = " WHERE " . implode(" AND ", $where_clauses);}
        $sql = $sql_base . $sql_where;
        if (!empty($query_params)){ $sql = $wpdb->prepare($sql, $query_params); }
        $count = $wpdb->get_var( $sql );
        oo_log('Job logs count result: ' . $count . ' SQL: ' . $sql, __METHOD__);
        return $count;
    }
    
    public static function get_open_job_log($employee_id, $job_number, $phase_id) { 
        oo_log('Attempting to get open job log.', __METHOD__);
        oo_log(compact('employee_id', 'job_number', 'phase_id'), __METHOD__);
        self::init(); global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::$job_logs_table . " WHERE employee_id = %d AND job_number = %s AND phase_id = %d AND status = 'started' ORDER BY start_time DESC LIMIT 1", $employee_id, $job_number, $phase_id));
        oo_log('Result for get_open_job_log: ', $result);
        return $result;
    }
    
    public static function update_job_log($log_id, $data) {
        oo_log('Attempting to update job log ID: ' . $log_id, __METHOD__);
        oo_log('Raw data received for update:', $data);
        self::init(); global $wpdb;
        $update_data = array(); $update_formats = array();
        if (isset($data['employee_id'])) { $update_data['employee_id'] = intval($data['employee_id']); $update_formats[] = '%d'; }
        if (isset($data['job_number'])) { $update_data['job_number'] = sanitize_text_field($data['job_number']); $update_formats[] = '%s'; }
        if (isset($data['phase_id'])) { $update_data['phase_id'] = intval($data['phase_id']); $update_formats[] = '%d'; }
        if (isset($data['start_time'])) { $update_data['start_time'] = sanitize_text_field($data['start_time']); $update_formats[] = '%s'; }
        if (array_key_exists('end_time', $data)) { $update_data['end_time'] = is_null($data['end_time']) ? null : sanitize_text_field($data['end_time']); $update_formats[] = '%s'; }
        
        if (isset($data['kpi_data'])) { 
            $update_data['kpi_data'] = is_string($data['kpi_data']) ? $data['kpi_data'] : wp_json_encode($data['kpi_data']); 
            $update_formats[] = '%s';
        }
        // Legacy/direct fields (can be phased out if kpi_data is fully adopted)
        if (array_key_exists('boxes_completed', $data)) { 
            $update_data['boxes_completed'] = is_null($data['boxes_completed']) ? null : intval($data['boxes_completed']); 
            $update_formats[] = '%d'; 
        }
        if (array_key_exists('items_completed', $data)) { 
            $update_data['items_completed'] = is_null($data['items_completed']) ? null : intval($data['items_completed']); 
            $update_formats[] = '%d'; 
        }

        if (isset($data['status'])) { $update_data['status'] = sanitize_text_field($data['status']); $update_formats[] = '%s'; }
        if (isset($data['notes'])) { $update_data['notes'] = sanitize_textarea_field($data['notes']); $update_formats[] = '%s'; }

        if (empty($update_data)) {
            $error = new WP_Error('no_data_to_update', 'No valid data fields provided for update.');
            oo_log('Error updating job log: No data fields to update after sanitization.', $error);
            return $error;
        }
        oo_log('Sanitized data for $wpdb->update:', $update_data);
        oo_log('Formats for $wpdb->update:', $update_formats);
        $result = $wpdb->update(self::$job_logs_table, $update_data, array('log_id' => $log_id), $update_formats, array('%d'));
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update job log. WPDB Error: ' . $wpdb->last_error);
            oo_log('Error updating job log: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        oo_log('Job log update result (rows affected or 0 if existing data matched): ' . $result . '. For Log ID: ' . $log_id, __METHOD__);
        return true;
    }

    public static function delete_job_log( $log_id ) {
        self::init(); global $wpdb;
        oo_log('Attempting to delete job log ID: ' . $log_id, __METHOD__);
        if (empty($log_id) || !is_numeric($log_id) || intval($log_id) <= 0) {
            $error = new WP_Error('invalid_id', 'Invalid Log ID provided for deletion.');
            oo_log('Error deleting job log: ' . $error->get_error_message(), __METHOD__);
            return $error;
        }
        $result = $wpdb->delete(self::$job_logs_table, array( 'log_id' => intval($log_id) ), array( '%d' ));
        if ($result === false) {
            $error = new WP_Error('db_delete_error', 'Could not delete job log. WPDB Error: ' . $wpdb->last_error);
            oo_log('Error deleting job log: DB delete failed. ' . $wpdb->last_error, $error);
            return $error;
        } elseif ($result === 0) {
            oo_log('No job log found with ID: ' . $log_id . ' to delete. Or no rows affected.', __METHOD__);
            return true; 
        }
        oo_log('Job log deleted successfully. ID: ' . $log_id . ', Rows affected: ' . $result, __METHOD__);
        return true;
    }
}

// Initialize table names on load with new class name
add_action('plugins_loaded', array('OO_DB', 'init'), 5); 