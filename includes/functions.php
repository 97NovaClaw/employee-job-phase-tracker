<?php
// /includes/functions.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Helper functions for the plugin can go here.

/**
 * Get a list of active employees for use in dropdowns.
 *
 * @return array Array of employee data (id, name).
 */
function ejpt_get_active_employees_for_select() {
    $employees = EJPT_DB::get_employees( array( 'is_active' => 1, 'orderby' => 'last_name', 'order' => 'ASC', 'number' => -1 ) );
    $options = array();
    if ( $employees ) {
        foreach ( $employees as $employee ) {
            $options[] = array(
                'id' => $employee->employee_id,
                'name' => esc_html( $employee->first_name . ' ' . $employee->last_name . ' (' . $employee->employee_number . ')' )
            );
        }
    }
    return $options;
}

/**
 * Get a list of active phases for use in dropdowns.
 *
 * @return array Array of phase data (id, name).
 */
function ejpt_get_active_phases_for_select() {
    $phases = EJPT_DB::get_phases( array( 'is_active' => 1, 'orderby' => 'phase_name', 'order' => 'ASC', 'number' => -1 ) );
    $options = array();
    if ( $phases ) {
        foreach ( $phases as $phase ) {
            $options[] = array(
                'id' => $phase->phase_id,
                'name' => esc_html( $phase->phase_name )
            );
        }
    }
    return $options;
}

/**
 * Get current timestamp for display, respecting WordPress timezone settings.
 * @return string Formatted date and time.
 */
function ejpt_get_current_timestamp_display() {
    return wp_date(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp'), wp_timezone());
}

/**
 * Get the capability required to manage plugin settings and view full dashboards.
 * Filters `ejpt_manage_capability` can be used to change this.
 * @return string The capability string.
 */
function ejpt_get_capability() {
    return apply_filters('ejpt_manage_capability', 'manage_options');
}

/**
 * Get the capability required to access the start/stop job forms (e.g., via QR code).
 * Filters `ejpt_form_access_capability` can be used to change this.
 * @return string The capability string.
 */
function ejpt_get_form_access_capability() {
    // 'read' means any logged-in user. 
    // Consider creating a custom role/capability for more fine-grained control.
    return apply_filters('ejpt_form_access_capability', 'read'); 
}

/**
 * Helper function for logging plugin debug messages.
 * Only logs if WP_DEBUG is true.
 *
 * @param mixed $message The message or data to log.
 * @param string $context Optional context for the log entry (e.g., function name).
 */
function ejpt_log($message, $context = '') {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        $log_entry = '[EJPT_DEBUG';
        if (!empty($context)) {
            $log_entry .= ' - ' . $context;
        }
        $log_entry .= ']: ';

        if (is_array($message) || is_object($message)) {
            $log_entry .= print_r($message, true);
        } else {
            $log_entry .= $message;
        }
        error_log($log_entry);
    }
} 