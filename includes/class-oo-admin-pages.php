<?php
// /includes/class-oo-admin-pages.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Admin_Pages { // Renamed class

    private $admin_page_hooks = array();

    public function add_admin_menu_pages() {
        // TODO: Redesign this entirely for Jobs, Stream Types, Content Stream KPI (Phases & Dashboard)
        // For now, let's keep the structure similar but with new slugs and class names.

        $main_menu_slug = 'oo_dashboard';

        $this->admin_page_hooks[] = add_menu_page(
            __( 'Operations Organizer', 'operations-organizer' ), // New text domain
            __( 'Operations', 'operations-organizer' ),
            oo_get_capability(), 
            $main_menu_slug,
            array( 'OO_Dashboard', 'display_dashboard_page' ), // New class name
            'dashicons-analytics', 
            25 
        );

        $this->admin_page_hooks[] = add_submenu_page(
            $main_menu_slug, 
            __( 'Manage Employees', 'operations-organizer' ),
            __( 'Employees', 'operations-organizer' ),
            oo_get_capability(), 
            'oo_employees', // New slug
            array( 'OO_Employee', 'display_employee_management_page' ) // New class name
        );
        
        // TODO: Add Menu for Stream Types
        // $this->admin_page_hooks[] = add_submenu_page(... for OO_Stream_Type::display_stream_type_management_page ...);

        $this->admin_page_hooks[] = add_submenu_page(
            $main_menu_slug, 
            __( 'Manage Phases (Content Stream)', 'operations-organizer' ), // Clarify this is for Content for now
            __( 'Content Phases', 'operations-organizer' ),
            oo_get_capability(), 
            'oo_phases', // New slug
            array( 'OO_Phase', 'display_phase_management_page' ) // New class name
        );
        
        // Hidden pages for Start/Stop forms - these will need stream_type context eventually
        $this->admin_page_hooks[] = add_submenu_page(
            null, 
            __( 'Start Job Phase', 'operations-organizer' ),
            __( 'Start Job Phase', 'operations-organizer' ),
            oo_get_form_access_capability(), 
            'oo_start_job', // New slug
            array( $this, 'display_start_job_form_page' )
        );

        $this->admin_page_hooks[] = add_submenu_page(
            null, 
            __( 'Stop Job Phase', 'operations-organizer' ),
            __( 'Stop Job Phase', 'operations-organizer' ),
            oo_get_form_access_capability(), 
            'oo_stop_job', // New slug
            array( $this, 'display_stop_job_form_page' )
        );
    }
    
    public function get_admin_page_hooks() {
        $actual_hooks = $this->admin_page_hooks;
        if (!in_array('admin_page_oo_start_job', $actual_hooks)) {
            $actual_hooks[] = 'admin_page_oo_start_job';
        }
        if (!in_array('admin_page_oo_stop_job', $actual_hooks)) {
            $actual_hooks[] = 'admin_page_oo_stop_job';
        }
        return $actual_hooks;
    }

    public function display_start_job_form_page() {
        if (!current_user_can(oo_get_form_access_capability())) { 
            wp_die(__( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ));
        }
        // TODO: This form will need to handle stream_type to show correct phases and KPI fields.
        include_once OO_PLUGIN_DIR . 'admin/views/start-job-form-page.php'; // Use new constant
    }

    public function display_stop_job_form_page() {
        if (!current_user_can(oo_get_form_access_capability())) { 
            wp_die(__( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ));
        }
        // TODO: This form will need to handle stream_type.
        include_once OO_PLUGIN_DIR . 'admin/views/stop-job-form-page.php'; // Use new constant
    }

    public static function handle_start_job_form() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_start_job_nonce', 'oo_start_job_nonce'); // TODO: Update nonce name

        if (!current_user_can(oo_get_form_access_capability())) { 
            oo_log('AJAX Error: Permission denied to start job.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied to start job.'], 403);
            return;
        }

        $employee_number_input = isset($_POST['employee_number']) ? sanitize_text_field(trim($_POST['employee_number'])) : '';
        $job_number = isset($_POST['job_number']) ? sanitize_text_field($_POST['job_number']) : '';
        $phase_id = isset($_POST['phase_id']) ? intval($_POST['phase_id']) : 0; // This phase_id is now specific to a stream type
        // TODO: Need to get stream_type_id from the form or determine it based on the phase_id
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $kpi_data_raw = isset($_POST['kpi_data']) ? $_POST['kpi_data'] : array(); // Expecting an array of KPIs
        $kpi_data = array();
        if(is_array($kpi_data_raw)) {
            foreach($kpi_data_raw as $key => $value) {
                // Basic sanitization, specific sanitization might be needed based on kpi_field_config
                $kpi_data[sanitize_key($key)] = sanitize_text_field($value);
            }
        }

        if (empty($employee_number_input) || empty($job_number) || empty($phase_id)) {
            oo_log('AJAX Error: Missing required fields for start job (EmpNo, JobNo, PhaseID).', $_POST);
            wp_send_json_error(['message' => 'Missing required fields: Employee Number, Job Number, or Phase ID.']);
            return;
        }
        
        $employee = OO_DB::get_employee_by_number($employee_number_input);
        if (!$employee || !$employee->is_active) {
            oo_log('AJAX Error: Invalid or inactive employee number: ' . $employee_number_input, __METHOD__);
            wp_send_json_error(['message' => 'Invalid or inactive Employee Number.']);
            return;
        }
        $employee_id = $employee->employee_id;

        $phase = OO_DB::get_phase($phase_id);
        if (!$phase || !$phase->is_active) {
            oo_log('AJAX Error: Selected phase not active or does not exist for start job.', $phase_id);
            wp_send_json_error(['message' => 'Selected phase is not active or does not exist.']);
            return;
        }
        // TODO: Validate that the selected phase_id belongs to the expected stream_type for this form instance.

        $result = OO_DB::start_job_phase($employee_id, $job_number, $phase_id, $notes, $kpi_data);

        if (is_wp_Error($result)) {
            oo_log('AJAX Error starting job: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error starting job: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Job phase started. Log ID: ' . $result, __METHOD__);
            wp_send_json_success(['message' => 'Job phase started successfully. Log ID: ' . $result]);
        }
    }

    public static function handle_stop_job_form() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_stop_job_nonce', 'oo_stop_job_nonce'); // TODO: Update nonce name

        if (!current_user_can(oo_get_form_access_capability())) { 
            oo_log('AJAX Error: Permission denied to stop job.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied to stop job.'], 403);
            return;
        }

        $employee_number_input = isset($_POST['employee_number']) ? sanitize_text_field(trim($_POST['employee_number'])) : '';
        $job_number = isset($_POST['job_number']) ? sanitize_text_field($_POST['job_number']) : '';
        $phase_id = isset($_POST['phase_id']) ? intval($_POST['phase_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $kpi_data_raw = isset($_POST['kpi_data']) ? $_POST['kpi_data'] : array(); // Expecting an array of KPIs
        $kpi_data_updates = array();
        if(is_array($kpi_data_raw)) {
            foreach($kpi_data_raw as $key => $value) {
                $kpi_data_updates[sanitize_key($key)] = sanitize_text_field($value);
            }
        }

        if (empty($employee_number_input) || empty($job_number) || empty($phase_id)) {
            oo_log('AJAX Error: Missing required fields for stop job (EmpNo, JobNo, PhaseID).', $_POST);
            wp_send_json_error(['message' => 'Missing required fields: Employee Number, Job Number, or Phase ID.']);
            return;
        }

        $employee = OO_DB::get_employee_by_number($employee_number_input);
        if (!$employee) { 
            oo_log('AJAX Error: Invalid employee number for stop job: ' . $employee_number_input, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Employee Number.']);
            return;
        }
        $employee_id = $employee->employee_id;
        
        // Example: Validation for specific KPIs if they were still expected directly and not just in kpi_data
        // if (isset($kpi_data_updates['boxes_completed']) && intval($kpi_data_updates['boxes_completed']) < 0) { /* error */ }

        $result = OO_DB::stop_job_phase($employee_id, $job_number, $phase_id, $kpi_data_updates, $notes);

        if (is_wp_Error($result)) {
            oo_log('AJAX Error stopping job: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error stopping job: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Job phase stopped and KPIs recorded.', __METHOD__);
            wp_send_json_success(['message' => 'Job phase stopped and KPIs recorded successfully.']);
        }
    }
} 