<?php
/**
 * Plugin Name:       Employee Job Phase Tracker
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Track employee job phases, start and stop times, and performance KPIs.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Your Name
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ejpt
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'EJPT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EJPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Activation hook
register_activation_hook( __FILE__, 'ejpt_activate_plugin' );
function ejpt_activate_plugin() {
    require_once EJPT_PLUGIN_DIR . 'includes/class-ejpt-db.php';
    EJPT_DB::create_tables();
}

// Deactivation hook (optional, can be added later if specific cleanup is needed)
// register_deactivation_hook( __FILE__, 'ejpt_deactivate_plugin' );
// function ejpt_deactivate_plugin() {
//     // Code to run on deactivation
// }

// Include core files
require_once EJPT_PLUGIN_DIR . 'includes/class-ejpt-db.php';
require_once EJPT_PLUGIN_DIR . 'includes/class-ejpt-employee.php';
require_once EJPT_PLUGIN_DIR . 'includes/class-ejpt-phase.php';
require_once EJPT_PLUGIN_DIR . 'includes/class-ejpt-admin-pages.php';
require_once EJPT_PLUGIN_DIR . 'includes/class-ejpt-dashboard.php';
require_once EJPT_PLUGIN_DIR . 'includes/functions.php';

// Instantiate classes and hook into WordPress
if ( is_admin() ) {
    $ejpt_admin_pages = new EJPT_Admin_Pages();
    add_action( 'admin_menu', array( $ejpt_admin_pages, 'add_admin_menu_pages' ) );

    // Enqueue admin scripts and styles
    add_action( 'admin_enqueue_scripts', 'ejpt_enqueue_admin_assets' );
    function ejpt_enqueue_admin_assets($hook_suffix) {
        // Only load on our plugin pages
        $plugin_pages = array(
            'toplevel_page_ejpt_dashboard',
            'employee-job-phase-tracker_page_ejpt_employees',
            'employee-job-phase-tracker_page_ejpt_phases',
            'admin_page_ejpt_start_job', // Correct hook suffix for pages added with add_menu_page
            'admin_page_ejpt_stop_job'   // Correct hook suffix for pages added with add_menu_page
        );
         // A more robust way to get the hook suffixes for our pages
        global $ejpt_admin_pages;
        if ($ejpt_admin_pages) {
            $plugin_pages = $ejpt_admin_pages->get_admin_page_hooks();
        }


        if ( in_array($hook_suffix, $plugin_pages) || 
             strpos($hook_suffix, 'ejpt_start_job') !== false || // for direct access links
             strpos($hook_suffix, 'ejpt_stop_job') !== false ) { // for direct access links
            wp_enqueue_style( 'ejpt-admin-styles', EJPT_PLUGIN_URL . 'admin/css/admin-styles.css', array(), '1.0.0' );
            wp_enqueue_script( 'ejpt-admin-scripts', EJPT_PLUGIN_URL . 'admin/js/admin-scripts.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.0.0', true );
            
            $localized_data = array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'dashboard_url' => admin_url( 'admin.php?page=ejpt_dashboard' )
            );
            wp_localize_script( 'ejpt-admin-scripts', 'ejpt_data', $localized_data );

             // For DataTables
            wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
            wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
        }
    }
}

// Load plugin textdomain for internationalization
add_action( 'plugins_loaded', 'ejpt_load_textdomain' );
function ejpt_load_textdomain() {
    load_plugin_textdomain( 'ejpt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Register AJAX handlers for start/stop job forms
// These will be defined in class-ejpt-admin-pages.php
add_action('wp_ajax_ejpt_start_job_action', array('EJPT_Admin_Pages', 'handle_start_job_form'));
add_action('wp_ajax_ejpt_stop_job_action', array('EJPT_Admin_Pages', 'handle_stop_job_form'));

// AJAX handlers for Employee CRUD
add_action('wp_ajax_ejpt_add_employee', array('EJPT_Employee', 'ajax_add_employee'));
add_action('wp_ajax_ejpt_get_employee', array('EJPT_Employee', 'ajax_get_employee'));
add_action('wp_ajax_ejpt_update_employee', array('EJPT_Employee', 'ajax_update_employee'));
add_action('wp_ajax_ejpt_toggle_employee_status', array('EJPT_Employee', 'ajax_toggle_employee_status'));

// AJAX handlers for Phase CRUD
add_action('wp_ajax_ejpt_add_phase', array('EJPT_Phase', 'ajax_add_phase'));
add_action('wp_ajax_ejpt_get_phase', array('EJPT_Phase', 'ajax_get_phase'));
add_action('wp_ajax_ejpt_update_phase', array('EJPT_Phase', 'ajax_update_phase'));
add_action('wp_ajax_ejpt_toggle_phase_status', array('EJPT_Phase', 'ajax_toggle_phase_status'));

// AJAX handler for dashboard data
add_action('wp_ajax_ejpt_get_dashboard_data', array('EJPT_Dashboard', 'ajax_get_dashboard_data'));

// AJAX handlers for editing job logs from dashboard
add_action('wp_ajax_ejpt_get_job_log_details', array('EJPT_Dashboard', 'ajax_get_job_log_details'));
add_action('wp_ajax_ejpt_update_job_log', array('EJPT_Dashboard', 'ajax_update_job_log'));