<?php
/**
 * Plugin Name:       Operations Organizer
 * Plugin URI:        https://legworkmedia.ca/
 * Description:       Track job phases, employee KPIs, and stream-specific operational data.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Legwork Media
 * Author URI:        https://legworkmedia.ca/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       operations-organizer
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants with new prefix
define( 'OO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OO_PLUGIN_FILE', __FILE__ ); // Define the main plugin file path

// Activation hook
register_activation_hook( OO_PLUGIN_FILE, 'oo_activate_plugin' );
function oo_activate_plugin() {
    require_once OO_PLUGIN_DIR . 'includes/class-oo-db.php'; // New class name
    OO_DB::create_tables(); // New class name
    // Optionally, add a default 'Content' stream type if it doesn't exist
    if (class_exists('OO_DB') && method_exists('OO_DB', 'get_stream_type_by_slug')) {
        $content_stream = OO_DB::get_stream_type_by_slug('content');
        if (!$content_stream) {
            OO_DB::add_stream_type('content', 'Content', wp_json_encode(array(
                array('field_name' => 'boxes_completed', 'field_label' => 'Boxes Completed', 'field_type' => 'number'),
                array('field_name' => 'items_completed', 'field_label' => 'Items Completed', 'field_type' => 'number')
            )));
        }
    }
}

// Include core files (will be renamed)
require_once OO_PLUGIN_DIR . 'includes/class-oo-db.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-employee.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-phase.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-stream-type.php'; // New class for Stream Types
require_once OO_PLUGIN_DIR . 'includes/class-oo-admin-pages.php';
require_once OO_PLUGIN_DIR . 'includes/class-oo-dashboard.php';
require_once OO_PLUGIN_DIR . 'includes/functions.php'; // Functions will also be prefixed

// Instantiate classes and hook into WordPress
if ( is_admin() ) {
    $oo_admin_pages = new OO_Admin_Pages();
    add_action( 'admin_menu', array( $oo_admin_pages, 'add_admin_menu_pages' ) );

    // Enqueue admin scripts and styles
    add_action( 'admin_enqueue_scripts', 'oo_enqueue_admin_assets' );
    function oo_enqueue_admin_assets($hook_suffix) {
        global $oo_admin_pages; // Relies on $oo_admin_pages being instantiated above
        $plugin_page_hooks = array();
        if ($oo_admin_pages && method_exists($oo_admin_pages, 'get_admin_page_hooks')) {
            $plugin_page_hooks = $oo_admin_pages->get_admin_page_hooks();
        }
        // Fallback or additional checks for specific pages if get_admin_page_hooks is not comprehensive yet
        $is_oo_page = false;
        if (in_array($hook_suffix, $plugin_page_hooks)) {
            $is_oo_page = true;
        } else {
            // Check for our specific page slugs if not caught by hooks (e.g. for pages added with null parent)
            $page_query_var = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
            if (strpos($page_query_var, 'oo_') === 0) {
                $is_oo_page = true;
            }
        }

        if ( $is_oo_page ) {
            wp_enqueue_style( 'oo-admin-styles', OO_PLUGIN_URL . 'admin/css/admin-styles.css', array(), '1.1.0' );
            wp_enqueue_script( 'oo-admin-scripts', OO_PLUGIN_URL . 'admin/js/admin-scripts.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.1.0', true );
            
            $localized_data = array(
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'dashboard_url'   => admin_url( 'admin.php?page=oo_dashboard' ),
                'nonce_add_employee' => wp_create_nonce('oo_add_employee_nonce'),
                'nonce_edit_employee' => wp_create_nonce('oo_edit_employee_nonce'),
                'nonce_toggle_status' => wp_create_nonce('oo_toggle_status_nonce'),
                'nonce_add_phase'     => wp_create_nonce('oo_add_phase_nonce'),
                'nonce_edit_phase'    => wp_create_nonce('oo_edit_phase_nonce'),
                'nonce_add_stream_type' => wp_create_nonce('oo_add_stream_type_nonce'), 
                'nonce_edit_log'      => wp_create_nonce('oo_edit_log_nonce'),      
                'nonce_delete_log'    => wp_create_nonce('oo_delete_log_nonce'),
                'nonce_dashboard'     => wp_create_nonce('oo_dashboard_nonce'),
                'text_please_select_employee' => __('Please select an employee.', 'operations-organizer'),
                'text_please_enter_emp_no' => __('Please enter an employee number.', 'operations-organizer'),
            );
            wp_localize_script( 'oo-admin-scripts', 'oo_data', $localized_data );

            wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
            wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
        }
    }
}

// Load plugin textdomain for internationalization
add_action( 'plugins_loaded', 'oo_load_textdomain' );
function oo_load_textdomain() {
    load_plugin_textdomain( 'operations-organizer', false, dirname( plugin_basename( OO_PLUGIN_FILE ) ) . '/languages/' );
}

// Register AJAX handlers with new OO_ prefixes
add_action('wp_ajax_oo_start_job_action', array('OO_Admin_Pages', 'handle_start_job_form'));
add_action('wp_ajax_oo_stop_job_action', array('OO_Admin_Pages', 'handle_stop_job_form'));

add_action('wp_ajax_oo_add_employee', array('OO_Employee', 'ajax_add_employee'));
add_action('wp_ajax_oo_get_employee', array('OO_Employee', 'ajax_get_employee'));
add_action('wp_ajax_oo_update_employee', array('OO_Employee', 'ajax_update_employee'));
add_action('wp_ajax_oo_toggle_employee_status', array('OO_Employee', 'ajax_toggle_employee_status'));

add_action('wp_ajax_oo_add_phase', array('OO_Phase', 'ajax_add_phase'));
add_action('wp_ajax_oo_get_phase', array('OO_Phase', 'ajax_get_phase'));
add_action('wp_ajax_oo_update_phase', array('OO_Phase', 'ajax_update_phase'));
add_action('wp_ajax_oo_toggle_phase_status', array('OO_Phase', 'ajax_toggle_phase_status'));

add_action('wp_ajax_oo_add_stream_type', array('OO_Stream_Type', 'ajax_add_stream_type'));
// TODO: Add AJAX handlers for get, update, toggle status for Stream Types

add_action('wp_ajax_oo_get_dashboard_data', array('OO_Dashboard', 'ajax_get_dashboard_data'));
add_action('wp_ajax_oo_get_job_log_details', array('OO_Dashboard', 'ajax_get_job_log_details'));
add_action('wp_ajax_oo_update_job_log', array('OO_Dashboard', 'ajax_update_job_log'));
add_action('wp_ajax_oo_delete_job_log', array('OO_Dashboard', 'ajax_delete_job_log'));

// TODO: Create class OO_Stream_Type and its AJAX handlers
// TODO: Update admin menu registration with new structure and OO_Admin_Menus class 