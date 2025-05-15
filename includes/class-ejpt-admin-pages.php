<?php
// /includes/class-ejpt-admin-pages.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class EJPT_Admin_Pages {

    private $admin_page_hooks = array();

    public function add_admin_menu_pages() {
        // Main Dashboard Page
        $this->admin_page_hooks[] = add_menu_page(
            __( 'Job Phase Tracker', 'ejpt' ),
            __( 'Job Tracker', 'ejpt' ),
            ejpt_get_capability(), // Capability
            'ejpt_dashboard',
            array( 'EJPT_Dashboard', 'display_dashboard_page' ),
            'dashicons-chart-line', // Icon
            25 // Position
        );

        // Employee Management Page (Submenu of Job Tracker)
        $this->admin_page_hooks[] = add_submenu_page(
            'ejpt_dashboard', // Parent slug
            __( 'Manage Employees', 'ejpt' ),
            __( 'Employees', 'ejpt' ),
            ejpt_get_capability(), // Capability
            'ejpt_employees',
            array( 'EJPT_Employee', 'display_employee_management_page' )
        );

        // Phase Management Page (Submenu of Job Tracker)
        $this->admin_page_hooks[] = add_submenu_page(
            'ejpt_dashboard', // Parent slug
            __( 'Manage Job Phases', 'ejpt' ),
            __( 'Job Phases', 'ejpt' ),
            ejpt_get_capability(), // Capability
            'ejpt_phases',
            array( 'EJPT_Phase', 'display_phase_management_page' )
        );
        
        // Hidden pages for Start/Stop forms (not in main menu, accessed via URL/QR)
        $this->admin_page_hooks[] = add_submenu_page(
            null, // No parent menu makes it a hidden page
            __( 'Start Job Phase', 'ejpt' ),
            __( 'Start Job Phase', 'ejpt' ),
            ejpt_get_form_access_capability(), // Minimum capability to access the form 
            'ejpt_start_job',
            array( $this, 'display_start_job_form_page' )
        );

        $this->admin_page_hooks[] = add_submenu_page(
            null, // No parent menu
            __( 'Stop Job Phase', 'ejpt' ),
            __( 'Stop Job Phase', 'ejpt' ),
            ejpt_get_form_access_capability(), 
            'ejpt_stop_job',
            array( $this, 'display_stop_job_form_page' )
        );
    }
    
    public function get_admin_page_hooks() {
        // These hooks are used by the main plugin file to enqueue assets only on our pages.
        // Hooks for pages added via add_submenu_page(null, ...) are like 'admin_page_{$menu_slug}'.
        // e.g. 'admin_page_ejpt_start_job'
        // The main plugin enqueuing logic needs to correctly identify these.
        // Let's explicitly add them if they are not already in the format expected by the enqueuing logic.
        $actual_hooks = $this->admin_page_hooks;
        if (!in_array('admin_page_ejpt_start_job', $actual_hooks)) {
            $actual_hooks[] = 'admin_page_ejpt_start_job';
        }
        if (!in_array('admin_page_ejpt_stop_job', $actual_hooks)) {
            $actual_hooks[] = 'admin_page_ejpt_stop_job';
        }
        return $actual_hooks;
    }


    public function display_start_job_form_page() {
        if (!current_user_can(ejpt_get_form_access_capability())) { 
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include_once EJPT_PLUGIN_DIR . 'admin/views/start-job-form-page.php';
    }

    public function display_stop_job_form_page() {
        if (!current_user_can(ejpt_get_form_access_capability())) { 
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        include_once EJPT_PLUGIN_DIR . 'admin/views/stop-job-form-page.php';
    }

    /**
     * Handle the submission of the "Start Job Phase" form.
     */
    public static function handle_start_job_form() {
        ejpt_log('AJAX call received.', __METHOD__);
        ejpt_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('ejpt_start_job_nonce', 'ejpt_start_job_nonce');

        if (!current_user_can(ejpt_get_form_access_capability())) { 
            ejpt_log('AJAX Error: Permission denied to start job.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied to start job.'], 403);
            return;
        }

        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        $job_number = isset($_POST['job_number']) ? sanitize_text_field($_POST['job_number']) : '';
        $phase_id = isset($_POST['phase_id']) ? intval($_POST['phase_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (empty($employee_id) || empty($job_number) || empty($phase_id)) {
            ejpt_log('AJAX Error: Missing required fields for start job.', $_POST);
            wp_send_json_error(['message' => 'Missing required fields: Employee, Job Number, or Phase ID.']);
            return;
        }
        
        $employee = EJPT_DB::get_employee($employee_id);
        if (!$employee || !$employee->is_active) {
            ejpt_log('AJAX Error: Selected employee not active or does not exist for start job.', $employee_id);
            wp_send_json_error(['message' => 'Selected employee is not active or does not exist.']);
            return;
        }
        $phase = EJPT_DB::get_phase($phase_id);
        if (!$phase || !$phase->is_active) {
            // Note: The start-job-form-page.php view also checks this, but double check here.
            ejpt_log('AJAX Error: Selected phase not active or does not exist for start job.', $phase_id);
            wp_send_json_error(['message' => 'Selected phase is not active or does not exist.']);
            return;
        }

        $result = EJPT_DB::start_job_phase($employee_id, $job_number, $phase_id, $notes);

        if (is_wp_Error($result)) {
            ejpt_log('AJAX Error starting job: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error starting job: ' . $result->get_error_message()]);
        } else {
            ejpt_log('AJAX Success: Job phase started. Log ID: ' . $result, __METHOD__);
            wp_send_json_success(['message' => 'Job phase started successfully. Log ID: ' . $result]);
        }
    }

    /**
     * Handle the submission of the "Stop Job Phase" form.
     */
    public static function handle_stop_job_form() {
        ejpt_log('AJAX call received.', __METHOD__);
        ejpt_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('ejpt_stop_job_nonce', 'ejpt_stop_job_nonce');

        if (!current_user_can(ejpt_get_form_access_capability())) { 
            ejpt_log('AJAX Error: Permission denied to stop job.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied to stop job.'], 403);
            return;
        }

        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
        $job_number = isset($_POST['job_number']) ? sanitize_text_field($_POST['job_number']) : '';
        $phase_id = isset($_POST['phase_id']) ? intval($_POST['phase_id']) : 0;
        $boxes_completed = isset($_POST['boxes_completed']) ? intval($_POST['boxes_completed']) : 0;
        $items_completed = isset($_POST['items_completed']) ? intval($_POST['items_completed']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (empty($employee_id) || empty($job_number) || empty($phase_id)) {
            ejpt_log('AJAX Error: Missing required fields for stop job.', $_POST);
            wp_send_json_error(['message' => 'Missing required fields: Employee, Job Number, or Phase ID.']);
            return;
        }
        
        if ($boxes_completed < 0 || $items_completed < 0) {
            ejpt_log('AJAX Error: Boxes/Items completed must be non-negative.', $_POST);
            wp_send_json_error(['message' => 'Boxes and Items completed must be non-negative.']);
            return;
        }

        $result = EJPT_DB::stop_job_phase($employee_id, $job_number, $phase_id, $boxes_completed, $items_completed, $notes);

        if (is_wp_Error($result)) {
            ejpt_log('AJAX Error stopping job: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error stopping job: ' . $result->get_error_message()]);
        } else {
            ejpt_log('AJAX Success: Job phase stopped and KPIs recorded.', __METHOD__);
            wp_send_json_success(['message' => 'Job phase stopped and KPIs recorded successfully.']);
        }
    }
} 