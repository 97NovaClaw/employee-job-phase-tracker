<?php
// /includes/class-oo-stream-type.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Stream_Type {

    public function __construct() {
        // Future constructor actions
    }

    /**
     * Display the Stream Type management page.
     */
    public static function display_stream_type_management_page() {
        if ( ! current_user_can( oo_get_capability() ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ) );
        }

        // Fetch stream types for listing
        // global $stream_types, $total_stream_types, ... etc. for view
        $GLOBALS['stream_types'] = OO_DB::get_stream_types(array('number' => 20, 'offset' => 0 /* add pagination args */));
        // $GLOBALS['total_stream_types'] = OO_DB::get_stream_types_count();

        // TODO: Create admin/views/stream-type-management-page.php
        // include_once OO_PLUGIN_DIR . 'admin/views/stream-type-management-page.php';
        echo "<div class=\"wrap\"><h1>Stream Type Management (Placeholder)</h1><p>Functionality to list, add, edit stream types and their KPI configurations will be here.</p></div>";
    }

    /**
     * Handle AJAX request to add a stream type.
     */
    public static function ajax_add_stream_type() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_add_stream_type_nonce', 'oo_add_stream_type_nonce'); 

        if ( ! current_user_can( oo_get_capability() ) ) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 ); return;
        }

        $slug = isset($_POST['stream_type_slug']) ? sanitize_key($_POST['stream_type_slug']) : '';
        $name = isset($_POST['stream_type_name']) ? sanitize_text_field($_POST['stream_type_name']) : '';
        $kpi_config_json = isset($_POST['kpi_fields_config']) ? stripslashes($_POST['kpi_fields_config']) : null;
        // Validate kpi_config_json as valid JSON before saving
        if(!is_null($kpi_config_json) && json_decode($kpi_config_json) === null && json_last_error() !== JSON_ERROR_NONE){
             oo_log('AJAX Error: Invalid JSON for KPI Fields Config.', $_POST);
             wp_send_json_error( array( 'message' => 'Error: Invalid JSON format for KPI Fields Configuration.' ) ); return;
        }

        if ( empty($slug) || empty($name) ) {
            oo_log('AJAX Error: Stream Type Slug and Name are required.', $_POST);
            wp_send_json_error( array( 'message' => 'Error: Stream Type Slug and Name are required.' ) ); return;
        }

        $result = OO_DB::add_stream_type( $slug, $name, $kpi_config_json );

        if ( is_wp_error( $result ) ) {
            oo_log('AJAX Error adding stream type: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error( array( 'message' => 'Error: ' . $result->get_error_message() ) );
        } else {
            oo_log('AJAX Success: Stream Type added. ID: ' . $result, __METHOD__);
            wp_send_json_success( array( 'message' => 'Stream Type added successfully.', 'stream_type_id' => $result ) );
        }
    }
    
    // TODO: Add ajax_get_stream_type, ajax_update_stream_type, ajax_toggle_stream_type_status

} 