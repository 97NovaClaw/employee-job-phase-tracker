<?php
// /includes/class-oo-phase.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Phase { // Renamed class

    public function __construct() {
        // Constructor
    }

    public static function display_phase_management_page() {
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        global $phases, $total_phases, $current_page, $per_page, $search_term, $active_filter, $stream_types, $selected_stream_type_id;

        // Get all stream types for a filter dropdown
        $stream_types = OO_DB::get_stream_types(array('is_active' => 1)); // Fetch active stream types
        $selected_stream_type_id = isset($_REQUEST['stream_type_filter']) ? intval($_REQUEST['stream_type_filter']) : null;

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
        if ($selected_stream_type_id) {
            $phase_args['stream_type_id'] = $selected_stream_type_id;
        }
        if ($active_filter === 'active') {
            $phase_args['is_active'] = 1;
        } elseif ($active_filter === 'inactive') {
            $phase_args['is_active'] = 0;
        }

        $GLOBALS['phases'] = OO_DB::get_phases( $phase_args );
        $GLOBALS['total_phases'] = OO_DB::get_phases_count($phase_args); // Count should use same filters
        
        $GLOBALS['current_page'] = $current_page;
        $GLOBALS['per_page'] = $per_page;
        $GLOBALS['search_term'] = $search_term;
        $GLOBALS['active_filter'] = $active_filter;
        $GLOBALS['stream_types'] = $stream_types; // Make stream types available to view
        $GLOBALS['selected_stream_type_id'] = $selected_stream_type_id;

        include_once OO_PLUGIN_DIR . 'admin/views/phase-management-page.php';
    }

    public static function ajax_add_phase() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_add_phase_nonce', 'oo_add_phase_nonce'); 

        if ( ! current_user_can( oo_get_capability() ) ) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }

        $stream_type_id = isset( $_POST['stream_type_id'] ) ? intval( $_POST['stream_type_id'] ) : 0;
        $phase_slug = isset( $_POST['phase_slug'] ) ? sanitize_key( trim($_POST['phase_slug']) ) : '';
        $phase_name = isset( $_POST['phase_name'] ) ? sanitize_text_field( trim($_POST['phase_name']) ) : '';
        $phase_description = isset( $_POST['phase_description'] ) ? sanitize_textarea_field( trim($_POST['phase_description']) ) : '';
        $sort_order = isset( $_POST['sort_order'] ) ? intval( $_POST['sort_order'] ) : 0;

        if ( empty($stream_type_id) || empty($phase_slug) || empty($phase_name) ) {
            oo_log('AJAX Error: Stream Type, Phase Slug and Name are required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Stream Type, Phase Slug and Phase Name are required.' ) ); return;
        }

        $result = OO_DB::add_phase( $stream_type_id, $phase_slug, $phase_name, $phase_description, $sort_order );

        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error adding phase: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Phase added. ID: ' . $result, __METHOD__);
            wp_send_json_success( array( 'message' => 'Phase added successfully.', 'phase_id' => $result ) );
        }
    }

    public static function ajax_get_phase() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_edit_phase_nonce', '_ajax_nonce_get_phase'); 
        if ( ! current_user_can( oo_get_capability() ) ) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        if ( $phase_id <= 0 ) {
            oo_log('AJAX Error: Invalid phase ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) ); return;
        }
        $phase = OO_DB::get_phase( $phase_id );
        if ( $phase ) {
            oo_log('AJAX Success: Phase found.', $phase);
            wp_send_json_success( $phase );
        } else {
            oo_log('AJAX Error: Phase not found for ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Phase not found.' ) );
        }
    }

    public static function ajax_update_phase() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_edit_phase_nonce', 'oo_edit_phase_nonce');
        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['edit_phase_id'] ) ? intval( $_POST['edit_phase_id'] ) : 0;
        $stream_type_id = isset( $_POST['edit_stream_type_id'] ) ? intval( $_POST['edit_stream_type_id'] ) : 0;
        $phase_slug = isset( $_POST['edit_phase_slug'] ) ? sanitize_key( trim($_POST['edit_phase_slug']) ) : '';
        $phase_name = isset( $_POST['edit_phase_name'] ) ? sanitize_text_field( trim($_POST['edit_phase_name']) ) : '';
        $phase_description = isset( $_POST['edit_phase_description'] ) ? sanitize_textarea_field( trim($_POST['edit_phase_description']) ) : '';
        $sort_order = isset( $_POST['edit_sort_order'] ) ? intval( $_POST['edit_sort_order'] ) : null; // Null if not set to keep existing

        if ( $phase_id <= 0 || empty($stream_type_id) || empty($phase_slug) || empty($phase_name) ) {
            oo_log('AJAX Error: Phase ID, Stream Type, Slug and Name are required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Phase ID, Stream Type, Phase Slug and Phase Name are required.' ) ); return;
        }
        $result = OO_DB::update_phase( $phase_id, $stream_type_id, $phase_slug, $phase_name, $phase_description, $sort_order );
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error updating phase: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Phase updated. ID: ' . $phase_id, __METHOD__);
            wp_send_json_success( array( 'message' => 'Phase updated successfully.' ) );
        }
    }

    public static function ajax_toggle_phase_status() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_toggle_status_nonce', '_ajax_nonce');
        if ( ! current_user_can( oo_get_capability() ) ) { 
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }
        $phase_id = isset( $_POST['phase_id'] ) ? intval( $_POST['phase_id'] ) : 0;
        $new_status = isset( $_POST['is_active'] ) ? intval( $_POST['is_active'] ) : 0;
        if ( $phase_id <= 0 ) {
            oo_log('AJAX Error: Invalid phase ID: ' . $phase_id, __METHOD__);
            wp_send_json_error( array( 'message' => 'Invalid phase ID.' ) ); return;
        }
        $result = OO_DB::toggle_phase_status( $phase_id, $new_status );
        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error toggling phase status: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            $message = $new_status ? 'Phase activated.' : 'Phase deactivated.';
            oo_log('AJAX Success: ' . $message . ' ID: ' . $phase_id, __METHOD__);
            wp_send_json_success( array( 'message' => $message, 'new_status' => $new_status ) );
        }
    }
} 