<?php
// /includes/class-ejpt-phase.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class EJPT_Phase {

    public function __construct() {
        // Actions for handling AJAX for phase operations can be added here if not in the main plugin file
    }

    /**
     * Display the phase management page.
     */
    public static function display_phase_management_page() {
        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        global $phases, $total_phases, $current_page, $per_page, $search_term, $active_filter;

        $search_term = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $active_filter = isset($_REQUEST['status_filter']) ? sanitize_text_field($_REQUEST['status_filter']) : 'all';

        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;
        
        $GLOBALS['orderby'] = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'phase_name';
        $GLOBALS['order'] = isset($_GET['order']) ? strtoupper(sanitize_key($_GET['order'])) : 'ASC';
        if(!in_array(strtolower($GLOBALS['order']), ['asc', 'desc'])) {
            $GLOBALS['order'] = 'ASC';
        }

        $phase_args = array(
            'number' => $per_page,
            'offset' => $offset,
            'search' => $search_term,
            'orderby' => $GLOBALS['orderby'],
            'order' => $GLOBALS['order'],
        );
        if ($active_filter === 'active') {
            $phase_args['is_active'] = 1;
        } elseif ($active_filter === 'inactive') {
            $phase_args['is_active'] = 0;
        }

        $phases = EJPT_DB::get_phases( $phase_args );
        $total_phases = EJPT_DB::get_phases_count(array('search' => $search_term, 'is_active' => ($active_filter === 'all' ? null : ($active_filter === 'active' ? 1 : 0)) ));
        
        include_once EJPT_PLUGIN_DIR . 'admin/views/phase-management-page.php';
    }

    /**
     * Handle AJAX request to add a phase.
     */
    public static function ajax_add_phase() {
        ejpt_log('AJAX: Attempting to add phase.', __METHOD__);
        ejpt_log('POST data: ', $_POST);
        check_ajax_referer('ejpt_add_phase_nonce', 'ejpt_add_phase_nonce');

        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            ejpt_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }

        $phase_name = isset( $_POST['phase_name'] ) ? sanitize_text_field( trim($_POST['phase_name']) ) : '';
        $phase_description = isset( $_POST['phase_description'] ) ? sanitize_textarea_field( trim($_POST['phase_description']) ) : '';

        if ( empty( $phase_name ) ) {
            ejpt_log('AJAX Error: Phase Name is required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Phase Name is required.' ) );
            return;
        }

        $result = EJPT_DB::add_phase( $phase_name, $phase_description );

        if ( is_wp_error( $result ) ) {
            ejpt_log('AJAX Error adding phase: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            ejpt_log('AJAX Success: Phase added. ID: ' . $result, __METHOD__);
            wp_send_json_success( array( 'message' => 'Phase added successfully.', 'phase_id' => $result ) );
        }
    }

    /**
     * Handle AJAX request to get a phase's details for editing.
     */
    public static function ajax_get_phase() {
        ejpt_log('AJAX: Attempting to get phase.', __METHOD__);
        ejpt_log('POST data: ', $_POST);
        check_ajax_referer('ejpt_edit_phase_nonce', '_ajax_nonce_get_phase');

        if ( ! current_user_can( ejpt_get_capability() ) ) {
            ejpt_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }

        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;

        if ( $phase_id <= 0 ) {
            ejpt_log('AJAX Error: Invalid phase ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) );
            return;
        }

        $phase = EJPT_DB::get_phase( $phase_id );

        if ( $phase ) {
            ejpt_log('AJAX Success: Phase found.', $phase);
            wp_send_json_success( $phase );
        } else {
            ejpt_log('AJAX Error: Phase not found for ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Phase not found.' ) );
        }
    }

    /**
     * Handle AJAX request to update a phase.
     */
    public static function ajax_update_phase() {
        ejpt_log('AJAX: Attempting to update phase.', __METHOD__);
        ejpt_log('POST data: ', $_POST);
        check_ajax_referer('ejpt_edit_phase_nonce', 'ejpt_edit_phase_nonce');

        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            ejpt_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }

        $phase_id = isset( $_POST['edit_phase_id'] ) ? intval( $_POST['edit_phase_id'] ) : 0;
        $phase_name = isset( $_POST['edit_phase_name'] ) ? sanitize_text_field( trim($_POST['edit_phase_name']) ) : '';
        $phase_description = isset( $_POST['edit_phase_description'] ) ? sanitize_textarea_field( trim($_POST['edit_phase_description']) ) : '';

        if ( $phase_id <= 0 || empty( $phase_name ) ) {
            ejpt_log('AJAX Error: Phase ID and Phase Name are required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Phase ID and Phase Name are required.' ) );
            return;
        }

        $result = EJPT_DB::update_phase( $phase_id, $phase_name, $phase_description );

        if ( is_wp_error( $result ) ) {
            ejpt_log('AJAX Error updating phase: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            ejpt_log('AJAX Success: Phase updated. ID: ' . $phase_id, __METHOD__);
            wp_send_json_success( array( 'message' => 'Phase updated successfully.' ) );
        }
    }

    /**
     * Handle AJAX request to toggle phase active status.
     */
    public static function ajax_toggle_phase_status() {
        ejpt_log('AJAX: Attempting to toggle phase status.', __METHOD__);
        ejpt_log('POST data: ', $_POST);
        check_ajax_referer('ejpt_toggle_status_nonce', '_ajax_nonce');

        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            ejpt_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
            return;
        }

        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;

        if ( $phase_id <= 0 ) {
            ejpt_log('AJAX Error: Invalid phase ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) );
            return;
        }

        $result = EJPT_DB::toggle_phase_status( $phase_id, $new_status );

        if ( is_wp_error( $result ) ) {
            ejpt_log('AJAX Error toggling phase status: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            $message = $new_status ? 'Phase activated.' : 'Phase deactivated.';
            ejpt_log('AJAX Success: ' . $message . ' ID: ' . $phase_id, __METHOD__);
            wp_send_json_success( array( 'message' => $message, 'new_status' => $new_status ) );
        }
    }
} 