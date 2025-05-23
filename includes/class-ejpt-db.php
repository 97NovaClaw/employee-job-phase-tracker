<?php
// /includes/class-ejpt-db.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class EJPT_DB {

    private static $employees_table;
    private static $phases_table;
    private static $job_logs_table;

    public static function init() {
        global $wpdb;
        self::$employees_table = $wpdb->prefix . 'ejpt_employees';
        self::$phases_table = $wpdb->prefix . 'ejpt_phases';
        self::$job_logs_table = $wpdb->prefix . 'ejpt_job_logs';
    }

    /**
     * Create custom database tables.
     */
    public static function create_tables() {
        ejpt_log('Attempting to create database tables...', __METHOD__);
        self::init();
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create employees table
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

        // SQL to create phases table
        $sql_phases = "CREATE TABLE " . self::$phases_table . " (
            phase_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            phase_name VARCHAR(100) NOT NULL,
            phase_description TEXT NULL,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (phase_id),
            UNIQUE KEY uq_phase_name (phase_name),
            INDEX idx_is_active (is_active)
        ) $charset_collate;";

        // SQL to create job logs table
        $sql_job_logs = "CREATE TABLE " . self::$job_logs_table . " (
            log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id BIGINT UNSIGNED NOT NULL,
            job_number VARCHAR(50) NOT NULL,
            phase_id INT UNSIGNED NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NULL,
            boxes_completed INT UNSIGNED NULL,
            items_completed INT UNSIGNED NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'started',
            notes TEXT NULL,
            PRIMARY KEY (log_id),
            INDEX idx_employee_id (employee_id),
            INDEX idx_job_number (job_number),
            INDEX idx_phase_id (phase_id),
            INDEX idx_start_time (start_time),
            INDEX idx_end_time (end_time),
            INDEX idx_status (status),
            FOREIGN KEY (employee_id) REFERENCES " . self::$employees_table . "(employee_id) ON DELETE RESTRICT ON UPDATE CASCADE,
            FOREIGN KEY (phase_id) REFERENCES " . self::$phases_table . "(phase_id) ON DELETE RESTRICT ON UPDATE CASCADE
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        ejpt_log('Running dbDelta for employees table.', __METHOD__);
        $dbdelta_employees_result = dbDelta( $sql_employees );
        ejpt_log('dbDelta employees result: ', __METHOD__);
        ejpt_log($dbdelta_employees_result, __METHOD__);
        
        ejpt_log('Running dbDelta for phases table.', __METHOD__);
        $dbdelta_phases_result = dbDelta( $sql_phases );
        ejpt_log('dbDelta phases result: ', __METHOD__);
        ejpt_log($dbdelta_phases_result, __METHOD__);

        ejpt_log('Running dbDelta for job logs table.', __METHOD__);
        $dbdelta_job_logs_result = dbDelta( $sql_job_logs );
        ejpt_log('dbDelta job logs result: ', __METHOD__);
        ejpt_log($dbdelta_job_logs_result, __METHOD__);
        ejpt_log('Finished creating database tables.', __METHOD__);
    }

    // --- Employee CRUD Methods ---
    public static function add_employee( $employee_number, $first_name, $last_name ) {
        ejpt_log('Attempting to add employee.', __METHOD__);
        ejpt_log(array(
            'employee_number' => $employee_number,
            'first_name' => $first_name,
            'last_name' => $last_name
        ), __METHOD__);

        self::init();
        global $wpdb;
        
        if (empty($employee_number) || empty($first_name) || empty($last_name)) {
            $error = new WP_Error('missing_fields', 'All fields (Employee Number, First Name, Last Name) are required.');
            ejpt_log('Error adding employee: Missing fields.', __METHOD__);
            ejpt_log($error, __METHOD__);
            return $error;
        }

        // Check if employee number already exists
        $exists = $wpdb->get_var( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE employee_number = %s", $employee_number) );
        if ($exists) {
            $error = new WP_Error('employee_exists', 'Employee number already exists.');
            ejpt_log('Error adding employee: Employee number exists.', __METHOD__);
            ejpt_log($error, __METHOD__);
            return $error;
        }

        $result = $wpdb->insert(
            self::$employees_table,
            array(
                'employee_number' => sanitize_text_field($employee_number),
                'first_name'      => sanitize_text_field($first_name),
                'last_name'       => sanitize_text_field($last_name),
                'is_active'       => 1,
                'created_at'      => current_time('mysql', 1)
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );

        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not add employee. Error: ' . $wpdb->last_error);
            ejpt_log('Error adding employee: DB insert failed.', __METHOD__);
            ejpt_log($error, __METHOD__);
            ejpt_log('WPDB Last Error: ' . $wpdb->last_error, __METHOD__);
            return $error;
        }
        ejpt_log('Employee added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function get_employee( $employee_id ) {
        self::init();
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$employees_table . " WHERE employee_id = %d", $employee_id ) );
    }
    
    public static function get_employee_by_number( $employee_number ) {
        ejpt_log('Attempting to get employee by number: ' . $employee_number, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$employees_table . " WHERE employee_number = %s", $employee_number ) );
        ejpt_log('Result for get_employee_by_number: ', $result);
        return $result;
    }

    public static function update_employee( $employee_id, $employee_number, $first_name, $last_name, $is_active = null ) {
        ejpt_log('Attempting to update employee ID: ' . $employee_id, __METHOD__);
        ejpt_log(compact('employee_id', 'employee_number', 'first_name', 'last_name', 'is_active'), __METHOD__);
        self::init();
        global $wpdb;

        if (empty($employee_number) || empty($first_name) || empty($last_name)) {
            $error = new WP_Error('missing_fields', 'All fields (Employee Number, First Name, Last Name) are required.');
            ejpt_log('Error updating employee: Missing fields.', $error);
            return $error;
        }
        
        $existing_employee = $wpdb->get_row( $wpdb->prepare("SELECT employee_id FROM " . self::$employees_table . " WHERE employee_number = %s AND employee_id != %d", $employee_number, $employee_id) );
        if ($existing_employee) {
            $error = new WP_Error('employee_number_exists', 'This employee number is already assigned to another employee.');
            ejpt_log('Error updating employee: Employee number exists for another ID.', $error);
            return $error;
        }

        $data = array(
            'employee_number' => sanitize_text_field($employee_number),
            'first_name'      => sanitize_text_field($first_name),
            'last_name'       => sanitize_text_field($last_name),
        );
        $formats = array('%s', '%s', '%s');

        if ( !is_null($is_active) ) {
            $data['is_active'] = intval($is_active);
            $formats[] = '%d';
        }

        $result = $wpdb->update(
            self::$employees_table,
            $data,
            array( 'employee_id' => $employee_id ),
            $formats,
            array( '%d' )
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update employee. Error: ' . $wpdb->last_error);
            ejpt_log('Error updating employee: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        ejpt_log('Employee updated successfully. ID: ' . $employee_id, __METHOD__);
        return true;
    }

    public static function toggle_employee_status( $employee_id, $is_active ) {
        ejpt_log('Toggling employee status for ID: ' . $employee_id . ' to ' . $is_active, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->update(
            self::$employees_table,
            array( 'is_active' => intval($is_active) ),
            array( 'employee_id' => $employee_id ),
            array( '%d' ),
            array( '%d' )
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update employee status. Error: ' . $wpdb->last_error);
            ejpt_log('Error toggling employee status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        ejpt_log('Employee status toggled successfully for ID: ' . $employee_id, __METHOD__);
        return true;
    }

    public static function get_employees( $args = array() ) {
        ejpt_log('Attempting to get employees with args:', __METHOD__);
        ejpt_log($args, __METHOD__);
        self::init();
        global $wpdb;
        $defaults = array(
            'is_active' => null, 
            'orderby'   => 'last_name',
            'order'     => 'ASC',
            'search'    => '',
            'number'    => -1, 
            'offset'    => 0
        );
        $args = wp_parse_args($args, $defaults);

        $sql_base = "SELECT * FROM " . self::$employees_table;
        $where_clauses = array();
        $query_params = array();

        if ( !is_null($args['is_active']) ) {
            $where_clauses[] = "is_active = %d";
            $query_params[] = $args['is_active'];
        }
        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = "(employee_number LIKE %s OR first_name LIKE %s OR last_name LIKE %s)";
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }

        $sql_where = "";
        if ( !empty($where_clauses) ) {
            $sql_where = " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql = $sql_base . $sql_where;

        if (!empty($query_params)){
            $sql = $wpdb->prepare($sql, $query_params);
        }
        
        $orderby_clause = "";
        if (!empty($args['orderby'])) {
            $orderby_val = sanitize_sql_orderby($args['orderby']);
            if ($orderby_val) {
                $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
                $orderby_clause = " ORDER BY $orderby_val $order_val";
            }
        }
        $sql .= $orderby_clause;

        $limit_clause = "";
        if ( isset($args['number']) && $args['number'] > 0 ) {
            $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));
        }
        $sql .= $limit_clause;
        
        $results = $wpdb->get_results( $sql );
        ejpt_log('Get employees query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }
    
    public static function get_employees_count( $args = array() ) {
        ejpt_log('Attempting to get employees count with args:', __METHOD__);
        ejpt_log($args, __METHOD__);
        self::init();
        global $wpdb;
        // Similar to get_employees but for COUNT(*)
        $defaults = array(
            'is_active' => null,
            'search'    => ''
        );
        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT COUNT(*) FROM " . self::$employees_table;
        $where_clauses = array();

        if ( !is_null($args['is_active']) ) {
            $where_clauses[] = $wpdb->prepare("is_active = %d", $args['is_active']);
        }
        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = $wpdb->prepare("(employee_number LIKE %s OR first_name LIKE %s OR last_name LIKE %s)", $search_term, $search_term, $search_term);
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $count = $wpdb->get_var( $sql );
        ejpt_log('Employees count result: ' . $count, __METHOD__);
        return $count;
    }

    // --- Phase CRUD Methods ---
    public static function add_phase( $phase_name, $phase_description ) {
        ejpt_log('Attempting to add phase.', __METHOD__);
        ejpt_log(compact('phase_name', 'phase_description'), __METHOD__);
        self::init();
        global $wpdb;

        if (empty($phase_name)) {
            $error = new WP_Error('missing_field', 'Phase Name is required.');
            ejpt_log('Error adding phase: Missing Phase Name.', $error);
            return $error;
        }

        $exists = $wpdb->get_var( $wpdb->prepare("SELECT phase_id FROM " . self::$phases_table . " WHERE phase_name = %s", $phase_name) );
        if ($exists) {
            $error = new WP_Error('phase_exists', 'Phase name already exists.');
            ejpt_log('Error adding phase: Phase name exists.', $error);
            return $error;
        }

        $result = $wpdb->insert(
            self::$phases_table,
            array(
                'phase_name'        => sanitize_text_field($phase_name),
                'phase_description' => sanitize_textarea_field($phase_description),
                'is_active'         => 1,
                'created_at'        => current_time('mysql', 1)
            ),
            array('%s', '%s', '%d', '%s')
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not add phase. Error: ' . $wpdb->last_error);
            ejpt_log('Error adding phase: DB insert failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        ejpt_log('Phase added successfully. ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function get_phase( $phase_id ) {
        ejpt_log('Attempting to get phase by ID: ' . $phase_id, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$phases_table . " WHERE phase_id = %d", $phase_id ) );
        ejpt_log('Result for get_phase: ', $result);
        return $result;
    }

    public static function update_phase( $phase_id, $phase_name, $phase_description, $is_active = null ) {
        ejpt_log('Attempting to update phase ID: ' . $phase_id, __METHOD__);
        ejpt_log(compact('phase_id', 'phase_name', 'phase_description', 'is_active'), __METHOD__);
        self::init();
        global $wpdb;

        if (empty($phase_name)) {
            $error = new WP_Error('missing_field', 'Phase Name is required.');
            ejpt_log('Error updating phase: Missing Phase Name.', $error);
            return $error;
        }

        $existing_phase = $wpdb->get_row( $wpdb->prepare("SELECT phase_id FROM " . self::$phases_table . " WHERE phase_name = %s AND phase_id != %d", $phase_name, $phase_id) );
        if ($existing_phase) {
            $error = new WP_Error('phase_name_exists', 'This phase name is already in use.');
            ejpt_log('Error updating phase: Phase name exists for another ID.', $error);
            return $error;
        }

        $data = array(
            'phase_name'        => sanitize_text_field($phase_name),
            'phase_description' => sanitize_textarea_field($phase_description),
        );
        $formats = array('%s', '%s');

        if ( !is_null($is_active) ) {
            $data['is_active'] = intval($is_active);
            $formats[] = '%d';
        }

        $result = $wpdb->update(
            self::$phases_table,
            $data,
            array( 'phase_id' => $phase_id ),
            $formats,
            array( '%d' )
        );
         if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update phase. Error: ' . $wpdb->last_error);
            ejpt_log('Error updating phase: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        ejpt_log('Phase updated successfully. ID: ' . $phase_id, __METHOD__);
        return true;
    }

    public static function toggle_phase_status( $phase_id, $is_active ) {
        ejpt_log('Toggling phase status for ID: ' . $phase_id . ' to ' . $is_active, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->update(
            self::$phases_table,
            array( 'is_active' => intval($is_active) ),
            array( 'phase_id' => $phase_id ),
            array( '%d' ),
            array( '%d' )
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update phase status. Error: ' . $wpdb->last_error);
            ejpt_log('Error toggling phase status: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        ejpt_log('Phase status toggled successfully for ID: ' . $phase_id, __METHOD__);
        return true;
    }

    public static function get_phases( $args = array() ) {
        ejpt_log('Attempting to get phases with args:', __METHOD__);
        ejpt_log($args, __METHOD__);
        self::init();
        global $wpdb;
        $defaults = array(
            'is_active' => null, 
            'orderby'   => 'phase_name',
            'order'     => 'ASC',
            'search'    => '',
            'number'    => -1, 
            'offset'    => 0
        );
        $args = wp_parse_args($args, $defaults);

        $sql_base = "SELECT * FROM " . self::$phases_table;
        $where_clauses = array();
        $query_params = array();

        if ( !is_null($args['is_active']) ) {
            $where_clauses[] = "is_active = %d";
            $query_params[] = $args['is_active'];
        }
        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = "(phase_name LIKE %s OR phase_description LIKE %s)";
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }

        $sql_where = "";
        if ( !empty($where_clauses) ) {
            $sql_where = " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql = $sql_base . $sql_where;
        
        if (!empty($query_params)){
            $sql = $wpdb->prepare($sql, $query_params);
        }

        $orderby_clause = "";
        if (!empty($args['orderby'])) {
            $orderby_val = sanitize_sql_orderby($args['orderby']);
            if ($orderby_val) {
                $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
                $orderby_clause = " ORDER BY $orderby_val $order_val";
            }
        }
        $sql .= $orderby_clause;

        $limit_clause = "";
        if ( isset($args['number']) && $args['number'] > 0 ) {
            $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));
        }
        $sql .= $limit_clause;

        $results = $wpdb->get_results( $sql );
        ejpt_log('Get phases query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }
    
    public static function get_phases_count( $args = array() ) {
        ejpt_log('Attempting to get phases count with args:', __METHOD__);
        ejpt_log($args, __METHOD__);
        self::init();
        global $wpdb;
        $defaults = array(
            'is_active' => null,
            'search'    => ''
        );
        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT COUNT(*) FROM " . self::$phases_table;
        $where_clauses = array();

        if ( !is_null($args['is_active']) ) {
            $where_clauses[] = $wpdb->prepare("is_active = %d", $args['is_active']);
        }
        if ( !empty($args['search']) ) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = $wpdb->prepare("phase_name LIKE %s OR phase_description LIKE %s", $search_term, $search_term);
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $count = $wpdb->get_var( $sql );
        ejpt_log('Phases count result: ' . $count, __METHOD__);
        return $count;
    }

    // --- Job Log CRUD Methods ---
    public static function start_job_phase( $employee_id, $job_number, $phase_id, $notes = '' ) {
        ejpt_log('Attempting to start job phase.', __METHOD__);
        ejpt_log(compact('employee_id', 'job_number', 'phase_id', 'notes'), __METHOD__);
        self::init();
        global $wpdb;

        if (empty($employee_id) || empty($job_number) || empty($phase_id)) {
            return new WP_Error('missing_fields', 'Employee, Job Number, and Phase are required.');
        }
        
        // Check if there's an already started (but not stopped) entry for this exact combination
        $existing_log = $wpdb->get_row($wpdb->prepare(
            "SELECT log_id FROM " . self::$job_logs_table . " WHERE employee_id = %d AND job_number = %s AND phase_id = %d AND status = 'started'",
            $employee_id, $job_number, $phase_id
        ));

        if ($existing_log) {
            return new WP_Error('already_started', 'This employee has already started this job phase and it has not been stopped yet.');
        }

        $result = $wpdb->insert(
            self::$job_logs_table,
            array(
                'employee_id' => $employee_id,
                'job_number'  => sanitize_text_field($job_number),
                'phase_id'    => $phase_id,
                'start_time'  => current_time('mysql', 1),
                'status'      => 'started',
                'notes'       => sanitize_textarea_field($notes) // Added notes here as per schema (nullable)
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s')
        );
        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not start job phase. Error: ' . $wpdb->last_error);
            ejpt_log('Error starting job phase: DB insert failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        ejpt_log('Job phase started successfully. Log ID: ' . $wpdb->insert_id, __METHOD__);
        return $wpdb->insert_id;
    }

    public static function stop_job_phase( $employee_id, $job_number, $phase_id, $boxes_completed, $items_completed, $notes = '' ) {
        ejpt_log('Attempting to stop job phase.', __METHOD__);
        ejpt_log(compact('employee_id', 'job_number', 'phase_id', 'boxes_completed', 'items_completed', 'notes'), __METHOD__);
        self::init();
        global $wpdb;

        if (empty($employee_id) || empty($job_number) || empty($phase_id)) {
            return new WP_Error('missing_fields', 'Employee, Job Number, and Phase are required to stop a job.');
        }
        
        if (!is_numeric($boxes_completed) || $boxes_completed < 0 || !is_numeric($items_completed) || $items_completed < 0) {
            return new WP_Error('invalid_kpi', 'Boxes and Items completed must be non-negative numbers.');
        }

        // Find the most recent 'started' log for this employee, job, and phase
        $log_to_update = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::$job_logs_table . " 
             WHERE employee_id = %d AND job_number = %s AND phase_id = %d AND status = 'started' 
             ORDER BY start_time DESC LIMIT 1",
            $employee_id, $job_number, $phase_id
        ) );

        if ( !$log_to_update ) {
            return new WP_Error('no_start_record', 'No matching \'started\' record found for this employee, job, and phase. Cannot stop.');
        }

        $data_to_update = array(
            'end_time'         => current_time('mysql', 1),
            'boxes_completed'  => intval($boxes_completed),
            'items_completed'  => intval($items_completed),
            'status'           => 'completed',
        );
        $formats = array('%s', '%d', '%d', '%s');
        
        // Only update notes if provided, otherwise keep existing (if any from start)
        if ( !empty($notes) ) {
            $data_to_update['notes'] = sanitize_textarea_field($notes);
            $formats[] = '%s';
        }

        $result = $wpdb->update(
            self::$job_logs_table,
            $data_to_update,
            array( 'log_id' => $log_to_update->log_id ),
            $formats,
            array('%d')
        );

        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not stop job phase. Error: ' . $wpdb->last_error);
            ejpt_log('Error stopping job phase: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        ejpt_log('Job phase stopped successfully for conditions.', __METHOD__);
        return true;
    }

    public static function get_job_log( $log_id ) {
        ejpt_log('Attempting to get job log by ID: ' . $log_id, __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$job_logs_table . " WHERE log_id = %d", $log_id ) );
        ejpt_log('Result for get_job_log: ', $result);
        return $result;
    }

    public static function get_job_logs( $args = array() ) {
        ejpt_log('Attempting to get job logs with args:', __METHOD__);
        ejpt_log($args, __METHOD__);
        self::init();
        global $wpdb;

        $defaults = array(
            'employee_id'   => null,
            'job_number'    => null,
            'phase_id'      => null,
            'date_from'     => null,
            'date_to'       => null,
            'status'        => null,
            'orderby'       => 'jl.start_time',
            'order'         => 'DESC',
            'number'        => 25, 
            'offset'        => 0,
            'search_general' => '' 
        );
        $args = wp_parse_args($args, $defaults);

        $sql_select = "SELECT jl.*, e.first_name, e.last_name, e.employee_number, p.phase_name";
        $sql_from = " FROM " . self::$job_logs_table . " jl
                     LEFT JOIN " . self::$employees_table . " e ON jl.employee_id = e.employee_id
                     LEFT JOIN " . self::$phases_table . " p ON jl.phase_id = p.phase_id";
        
        $sql_base = $sql_select . $sql_from;
        $where_clauses = array();
        $query_params = array();

        if ( !empty($args['employee_id']) ) {
            $where_clauses[] = "jl.employee_id = %d";
            $query_params[] = $args['employee_id'];
        }
        if ( !empty($args['job_number']) ) {
            $where_clauses[] = "jl.job_number = %s";
            $query_params[] = sanitize_text_field($args['job_number']);
        }
        if ( !empty($args['phase_id']) ) {
            $where_clauses[] = "jl.phase_id = %d";
            $query_params[] = $args['phase_id'];
        }
        if ( !empty($args['date_from']) ) {
            $where_clauses[] = "jl.start_time >= %s";
            $query_params[] = sanitize_text_field($args['date_from']) . ' 00:00:00';
        }
        if ( !empty($args['date_to']) ) {
            $date_to_end_of_day = sanitize_text_field($args['date_to']) . ' 23:59:59';
            if (!empty($args['date_from'])) {
                 $date_from_start_of_day = sanitize_text_field($args['date_from']) . ' 00:00:00';
                 $where_clauses[] = "( (jl.start_time <= %s AND (jl.end_time IS NULL OR jl.end_time >= %s)) OR (jl.start_time >= %s AND jl.start_time <= %s) )"; 
                 $query_params[] = $date_to_end_of_day;
                 $query_params[] = $date_from_start_of_day;
                 $query_params[] = $date_from_start_of_day;
                 $query_params[] = $date_to_end_of_day;
            } else {
                $where_clauses[] = "jl.start_time <= %s";
                $query_params[] = $date_to_end_of_day;
            }
        }
        if ( !empty($args['status']) ) {
            $where_clauses[] = "jl.status = %s";
            $query_params[] = sanitize_text_field($args['status']);
        }
        if ( !empty($args['search_general']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search_general'])) . '%';
            $where_clauses[] = "(jl.job_number LIKE %s OR e.first_name LIKE %s OR e.last_name LIKE %s OR e.employee_number LIKE %s OR p.phase_name LIKE %s OR jl.notes LIKE %s)";
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }

        $sql_where = "";
        if ( !empty($where_clauses) ) {
            $sql_where = " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $sql = $sql_base . $sql_where;

        if (!empty($query_params)){
            $sql = $wpdb->prepare($sql, $query_params);
        }

        $orderby_clause = "";
        if (!empty($args['orderby'])) {
            $allowed_orderby = array('jl.start_time', 'jl.end_time', 'e.last_name', 'e.first_name', 'e.employee_number', 'jl.job_number', 'p.phase_name', 'jl.status', 'jl.boxes_completed', 'jl.items_completed');
            $orderby_input = $args['orderby'];
            $order_val = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
            
            if ($orderby_input === 'e.last_name' || $orderby_input === 'e.first_name') {
                // Special handling for sorting by employee name (last then first)
                $orderby_clause = " ORDER BY e.last_name $order_val, e.first_name $order_val";
            } elseif (in_array($orderby_input, $allowed_orderby)) {
                $orderby_clause = " ORDER BY $orderby_input $order_val";
            } else {
                 $orderby_clause = " ORDER BY jl.start_time $order_val"; // Default sort
            }
        }
        $sql .= $orderby_clause;

        $limit_clause = "";
        if ( isset($args['number']) && $args['number'] > 0 ) {
            $limit_clause = sprintf(" LIMIT %d OFFSET %d", intval($args['number']), intval($args['offset']));
        }
        $sql .= $limit_clause;

        $results = $wpdb->get_results( $sql );
        ejpt_log('Get job_logs query executed. SQL: ' . $sql . ' Number of results: ' . count($results), __METHOD__);
        return $results;
    }

    public static function get_job_logs_count( $args = array() ) {
        ejpt_log('Attempting to get job logs count with args:', __METHOD__);
        ejpt_log($args, __METHOD__);
        self::init();
        global $wpdb;
        $defaults = array(
            'employee_id'   => null,
            'job_number'    => null,
            'phase_id'      => null,
            'date_from'     => null,
            'date_to'       => null,
            'status'        => null,
            'search_general' => ''
        );
        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT COUNT(jl.log_id) 
                FROM " . self::$job_logs_table . " jl
                LEFT JOIN " . self::$employees_table . " e ON jl.employee_id = e.employee_id
                LEFT JOIN " . self::$phases_table . " p ON jl.phase_id = p.phase_id";

        $where_clauses = array();
        if ( !empty($args['employee_id']) ) {
            $where_clauses[] = $wpdb->prepare("jl.employee_id = %d", $args['employee_id']);
        }
        if ( !empty($args['job_number']) ) {
            $where_clauses[] = $wpdb->prepare("jl.job_number = %s", sanitize_text_field($args['job_number']));
        }
        if ( !empty($args['phase_id']) ) {
            $where_clauses[] = $wpdb->prepare("jl.phase_id = %d", $args['phase_id']);
        }
        if ( !empty($args['date_from']) ) {
            $where_clauses[] = $wpdb->prepare("jl.start_time >= %s", sanitize_text_field($args['date_from']) . ' 00:00:00');
        }
        if ( !empty($args['date_to']) ) {
            $date_to_end_of_day = sanitize_text_field($args['date_to']) . ' 23:59:59';
            if (!empty($args['date_from'])) {
                 $date_from_start_of_day = sanitize_text_field($args['date_from']) . ' 00:00:00';
                 $where_clauses[] = $wpdb->prepare("( (jl.start_time <= %s AND (jl.end_time IS NULL OR jl.end_time >= %s)) OR (jl.start_time >= %s AND jl.start_time <= %s) )", 
                                                $date_to_end_of_day, $date_from_start_of_day, $date_from_start_of_day, $date_to_end_of_day);
            } else {
                $where_clauses[] = $wpdb->prepare("jl.start_time <= %s", $date_to_end_of_day);
            }
        }
        if ( !empty($args['status']) ) {
            $where_clauses[] = $wpdb->prepare("jl.status = %s", sanitize_text_field($args['status']));
        }
        if ( !empty($args['search_general']) ) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($args['search_general'])) . '%';
            $where_clauses[] = $wpdb->prepare("(jl.job_number LIKE %s OR e.first_name LIKE %s OR e.last_name LIKE %s OR e.employee_number LIKE %s OR p.phase_name LIKE %s OR jl.notes LIKE %s)", 
                                            $search_term, $search_term, $search_term, $search_term, $search_term, $search_term);
        }

        if ( !empty($where_clauses) ) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $count = $wpdb->get_var( $sql );
        ejpt_log('Job logs count result: ' . $count, __METHOD__);
        return $count;
    }
    
    /**
     * Get a specific job log for update, typically the one that was started but not stopped.
     */
    public static function get_open_job_log($employee_id, $job_number, $phase_id) {
        ejpt_log('Attempting to get open job log.', __METHOD__);
        ejpt_log(compact('employee_id', 'job_number', 'phase_id'), __METHOD__);
        self::init();
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$job_logs_table . " 
             WHERE employee_id = %d AND job_number = %s AND phase_id = %d AND status = 'started' 
             ORDER BY start_time DESC LIMIT 1",
            $employee_id, $job_number, $phase_id
        ));
        ejpt_log('Result for get_open_job_log: ', $result);
        return $result;
    }
    
    /**
     * Update an existing job log entry.
     * Used for manual edits if implemented in the future.
     */
    public static function update_job_log($log_id, $data) {
        ejpt_log('Attempting to update job log ID: ' . $log_id, __METHOD__);
        ejpt_log('Raw data received for update:', $data);
        self::init();
        global $wpdb;

        $update_data = array();
        $update_formats = array();

        // Whitelist and sanitize fields
        if (isset($data['employee_id'])) { 
            $update_data['employee_id'] = intval($data['employee_id']); $update_formats[] = '%d'; 
        }
        if (isset($data['job_number'])) { 
            $update_data['job_number'] = sanitize_text_field($data['job_number']); $update_formats[] = '%s'; 
        }
        if (isset($data['phase_id'])) { 
            $update_data['phase_id'] = intval($data['phase_id']); $update_formats[] = '%d'; 
        }
        if (isset($data['start_time'])) { // Already formatted to YYYY-MM-DD HH:MM:SS
            $update_data['start_time'] = sanitize_text_field($data['start_time']); $update_formats[] = '%s'; 
        }
        if (array_key_exists('end_time', $data)) { // Check if key exists to allow setting to NULL
            $update_data['end_time'] = is_null($data['end_time']) ? null : sanitize_text_field($data['end_time']); 
            $update_formats[] = '%s'; 
        }
        if (array_key_exists('boxes_completed', $data)) {
            $update_data['boxes_completed'] = is_null($data['boxes_completed']) ? null : intval($data['boxes_completed']); 
            $update_formats[] = '%d'; 
        }
        if (array_key_exists('items_completed', $data)) {
            $update_data['items_completed'] = is_null($data['items_completed']) ? null : intval($data['items_completed']); 
            $update_formats[] = '%d'; 
        }
        if (isset($data['status'])) { 
            $update_data['status'] = sanitize_text_field($data['status']); $update_formats[] = '%s'; 
        }
        if (isset($data['notes'])) { // notes can be empty string
            $update_data['notes'] = sanitize_textarea_field($data['notes']); $update_formats[] = '%s'; 
        }

        if (empty($update_data)) {
            $error = new WP_Error('no_data_to_update', 'No valid data fields provided for update.');
            ejpt_log('Error updating job log: No data fields to update after sanitization.', $error);
            return $error;
        }
        
        ejpt_log('Sanitized data for $wpdb->update:', $update_data);
        ejpt_log('Formats for $wpdb->update:', $update_formats);

        $result = $wpdb->update(
            self::$job_logs_table,
            $update_data,
            array('log_id' => $log_id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            $error = new WP_Error('db_error', 'Could not update job log. WPDB Error: ' . $wpdb->last_error);
            ejpt_log('Error updating job log: DB update failed. ' . $wpdb->last_error, $error);
            return $error;
        }
        ejpt_log('Job log update result (rows affected or 0 if existing data matched): ' . $result . '. For Log ID: ' . $log_id, __METHOD__);
        return true;
    }

    /**
     * Delete a specific job log entry.
     * @param int $log_id The ID of the log entry to delete.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function delete_job_log( $log_id ) {
        self::init();
        global $wpdb;
        ejpt_log('Attempting to delete job log ID: ' . $log_id, __METHOD__);

        if (empty($log_id) || !is_numeric($log_id) || intval($log_id) <= 0) {
            $error = new WP_Error('invalid_id', 'Invalid Log ID provided for deletion.');
            ejpt_log('Error deleting job log: ' . $error->get_error_message(), __METHOD__);
            return $error;
        }

        $result = $wpdb->delete(
            self::$job_logs_table,
            array( 'log_id' => intval($log_id) ),
            array( '%d' ) // Format of the WHERE clause value
        );

        if ($result === false) {
            $error = new WP_Error('db_delete_error', 'Could not delete job log. WPDB Error: ' . $wpdb->last_error);
            ejpt_log('Error deleting job log: DB delete failed. ' . $wpdb->last_error, $error);
            return $error;
        } elseif ($result === 0) {
            // No rows were deleted, which could mean the log_id didn't exist
            // Depending on desired behavior, this could be an error or just a non-event
            ejpt_log('No job log found with ID: ' . $log_id . ' to delete. Or no rows affected.', __METHOD__);
            // return new WP_Error('not_found', 'No job log found with that ID to delete.'); // Optionally treat as error
            return true; // Or treat as success if no error but 0 rows affected
        }

        ejpt_log('Job log deleted successfully. ID: ' . $log_id . ', Rows affected: ' . $result, __METHOD__);
        return true;
    }

}

// Initialize table names on load
add_action('plugins_loaded', array('EJPT_DB', 'init'), 5); // Lower priority to ensure $wpdb is available