<?php
// /includes/class-ejpt-employee.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class EJPT_Employee {

    public function __construct() {
        // Actions for handling AJAX for employee operations can be added here if not in the main plugin file
    }

    /**
     * Display the employee management page.
     */
    public static function display_employee_management_page() {
        // Check user capabilities
        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        // Handle search
        $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $active_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field($_REQUEST['status_filter']) : 'all';

        // Data for the table
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;
        
        $GLOBALS['orderby'] = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'last_name';
        $GLOBALS['order'] = isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : 'ASC';
        if(!in_array(strtolower($GLOBALS['order']), ['asc', 'desc'])) {
            $GLOBALS['order'] = 'ASC';
        }

        $employee_args = array(
            'number' => $per_page,
            'offset' => $offset,
            'search' => $search_term,
            'orderby' => $GLOBALS['orderby'],
            'order' => $GLOBALS['order'],
        );
        if ($active_filter === 'active') {
            $employee_args['is_active'] = 1;
        } elseif ($active_filter === 'inactive') {
            $employee_args['is_active'] = 0;
        }

        $employees = EJPT_DB::get_employees( $employee_args );
        $total_employees = EJPT_DB::get_employees_count(array('search' => $search_term, 'is_active' => ($active_filter === 'all' ? null : ($active_filter === 'active' ? 1 : 0)) ));

        // Make variables available to the view
        global $employees, $total_employees, $current_page, $per_page, $search_term, $active_filter;

        // Include the view file
        include_once EJPT_PLUGIN_DIR . 'admin/views/employee-management-page.php';
    }

    /**
     * Handle AJAX request to add an employee.
     */
    public static function ajax_add_employee() {
        check_ajax_referer('ejpt_add_employee_nonce', 'ejpt_add_employee_nonce');

        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }

        $employee_number = isset( $_POST['employee_number'] ) ? sanitize_text_field( $_POST['employee_number'] ) : '';
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';

        if ( empty( $employee_number ) || empty( $first_name ) || empty( $last_name ) ) {
            wp_send_json_error( array( 'message' => 'Error: All fields are required.' ) );
            return;
        }

        $result = EJPT_DB::add_employee( $employee_number, $first_name, $last_name );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            wp_send_json_success( array( 'message' => 'Employee added successfully.', 'employee_id' => $result ) );
        }
    }
    
    /**
     * Handle AJAX request to get an employee's details for editing.
     */
    public static function ajax_get_employee() {
        // Nonce is checked in the JS before sending, using the edit_employee_nonce from the form
        // However, for a GET-like operation, a specific nonce for this action is better.
        check_ajax_referer('ejpt_edit_employee_nonce', '_ajax_nonce_get_employee'); 

        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }

        $employee_id = isset( $_POST['employee_id'] ) ? intval( $_POST['employee_id'] ) : 0;

        if ( $employee_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid employee ID.' ) );
            return;
        }

        $employee = EJPT_DB::get_employee( $employee_id );

        if ( $employee ) {
            wp_send_json_success( $employee );
        } else {
            wp_send_json_error( array( 'message' => 'Employee not found.' ) );
        }
    }

    /**
     * Handle AJAX request to update an employee.
     */
    public static function ajax_update_employee() {
        check_ajax_referer('ejpt_edit_employee_nonce', 'ejpt_edit_employee_nonce');

        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }

        $employee_id = isset( $_POST['edit_employee_id'] ) ? intval( $_POST['edit_employee_id'] ) : 0;
        $employee_number = isset( $_POST['edit_employee_number'] ) ? sanitize_text_field( $_POST['edit_employee_number'] ) : '';
        $first_name = isset( $_POST['edit_first_name'] ) ? sanitize_text_field( $_POST['edit_first_name'] ) : '';
        $last_name = isset( $_POST['edit_last_name'] ) ? sanitize_text_field( $_POST['edit_last_name'] ) : '';
        // is_active is handled by a separate toggle function for simplicity in the main table

        if ( $employee_id <= 0 || empty( $employee_number ) || empty( $first_name ) || empty( $last_name ) ) {
            wp_send_json_error( array( 'message' => 'Error: All fields are required and Employee ID must be valid.' ) );
            return;
        }

        $result = EJPT_DB::update_employee( $employee_id, $employee_number, $first_name, $last_name );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            wp_send_json_success( array( 'message' => 'Employee updated successfully.' ) );
        }
    }

    /**
     * Handle AJAX request to toggle employee active status.
     */
    public static function ajax_toggle_employee_status() {
        check_ajax_referer('ejpt_toggle_status_nonce', '_ajax_nonce'); 

        if ( ! current_user_can( ejpt_get_capability() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }

        $employee_id = isset( $_POST['employee_id'] ) ? intval( $_POST['employee_id'] ) : 0;
        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;

        if ( $employee_id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid employee ID.' ) );
            return;
        }

        $result = EJPT_DB::toggle_employee_status( $employee_id, $new_status );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            $message = $new_status ? 'Employee activated.' : 'Employee deactivated.';
            wp_send_json_success( array( 'message' => $message, 'new_status' => $new_status ) );
        }
    }
}
?> 