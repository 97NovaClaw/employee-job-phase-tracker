<?php
// /includes/class-oo-employee.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Employee { // Renamed class

    public function __construct() {
        // Constructor actions, if any, would be prefixed OO_
    }

    public static function display_employee_management_page() {
        if ( ! current_user_can( oo_get_capability() ) ) { // Use new function prefix
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        global $employees, $total_employees, $current_page, $per_page, $search_term, $active_filter;

        $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $active_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field($_REQUEST['status_filter']) : 'all';
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

        $GLOBALS['employees'] = OO_DB::get_employees( $employee_args ); // Use new DB class
        $GLOBALS['total_employees'] = OO_DB::get_employees_count(array('search' => $search_term, 'is_active' => ($active_filter === 'all' ? null : ($active_filter === 'active' ? 1 : 0)) ));
        $GLOBALS['current_page'] = $current_page;
        $GLOBALS['per_page'] = $per_page;
        $GLOBALS['search_term'] = $search_term;
        $GLOBALS['active_filter'] = $active_filter;
        
        include_once OO_PLUGIN_DIR . 'admin/views/employee-management-page.php'; // Use new constant
    }

    public static function ajax_add_employee() {
        oo_log('AJAX call received.', __METHOD__); // Use new log function
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_add_employee_nonce', 'oo_add_employee_nonce'); // TODO: Update nonce name in JS/View

        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }
        // ... (rest of the method, ensuring OO_DB is used and oo_log) ...
        $employee_number = isset( $_POST['employee_number'] ) ? sanitize_text_field( $_POST['employee_number'] ) : '';
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';

        if ( empty( $employee_number ) || empty( $first_name ) || empty( $last_name ) ) {
            oo_log('AJAX Error: All fields are required.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: All fields are required.' ) );
            return;
        }
        $result = OO_DB::add_employee( $employee_number, $first_name, $last_name );
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error adding employee: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Employee added. ID: ' . $result, __METHOD__);
            wp_send_json_success( array( 'message' => 'Employee added successfully.', 'employee_id' => $result ) );
        }
    }
    
    public static function ajax_get_employee() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_edit_employee_nonce', '_ajax_nonce_get_employee'); // TODO: Update nonce name 
        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $employee_id = isset( $_POST['employee_id'] ) ? intval( $_POST['employee_id'] ) : 0;
        if ( $employee_id <= 0 ) {
            oo_log('AJAX Error: Invalid employee ID: ' . $employee_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid employee ID.' ) ); return;
        }
        $employee = OO_DB::get_employee( $employee_id );
        if ( $employee ) {
            oo_log('AJAX Success: Employee found.', $employee);
            wp_send_json_success( $employee );
        } else {
            oo_log('AJAX Error: Employee not found for ID: ' . $employee_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Employee not found.' ) );
        }
    }

    public static function ajax_update_employee() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_edit_employee_nonce', 'oo_edit_employee_nonce'); // TODO: Update nonce name
        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $employee_id = isset( $_POST['edit_employee_id'] ) ? intval( $_POST['edit_employee_id'] ) : 0;
        $employee_number = isset( $_POST['edit_employee_number'] ) ? sanitize_text_field( $_POST['edit_employee_number'] ) : '';
        $first_name = isset( $_POST['edit_first_name'] ) ? sanitize_text_field( $_POST['edit_first_name'] ) : '';
        $last_name = isset( $_POST['edit_last_name'] ) ? sanitize_text_field( $_POST['edit_last_name'] ) : '';
        if ( $employee_id <= 0 || empty( $employee_number ) || empty( $first_name ) || empty( $last_name ) ) {
            oo_log('AJAX Error: All fields are required and Employee ID must be valid.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: All fields are required and Employee ID must be valid.' ) ); return;
        }
        $result = OO_DB::update_employee( $employee_id, $employee_number, $first_name, $last_name );
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error updating employee: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Employee updated. ID: ' . $employee_id, __METHOD__);
            wp_send_json_success( array( 'message' => 'Employee updated successfully.' ) );
        }
    }

    public static function ajax_toggle_employee_status() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_toggle_status_nonce', '_ajax_nonce'); // TODO: Update nonce name 
        if ( ! current_user_can( oo_get_capability() ) ) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $employee_id = isset( $_POST['employee_id'] ) ? intval( $_POST['employee_id'] ) : 0;
        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;
        if ( $employee_id <= 0 ) {
            oo_log('AJAX Error: Invalid employee ID: ' . $employee_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid employee ID.' ) ); return;
        }
        $result = OO_DB::toggle_employee_status( $employee_id, $new_status );
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error toggling employee status: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            $message = $new_status ? 'Employee activated.' : 'Employee deactivated.';
            oo_log('AJAX Success: ' . $message . ' ID: ' . $employee_id, __METHOD__);
            wp_send_json_success( array( 'message' => $message, 'new_status' => $new_status ) );
        }
    }
} 