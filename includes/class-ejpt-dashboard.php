<?php
// /includes/class-ejpt-dashboard.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class EJPT_Dashboard {

    /**
     * Display the main dashboard page.
     */
    public static function display_dashboard_page() {
        if ( ! current_user_can( ejpt_get_capability() ) ) { 
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        
        // Fetch data and assign to $GLOBALS to ensure view access
        $GLOBALS['employees'] = EJPT_DB::get_employees(array('is_active' => 1, 'orderby' => 'last_name', 'order' => 'ASC', 'number' => -1));
        $GLOBALS['phases'] = EJPT_DB::get_phases(array('is_active' => 1, 'orderby' => 'phase_name', 'order' => 'ASC', 'number' => -1));

        // The view file (dashboard-page.php) will use: global $employees, $phases;
        include_once EJPT_PLUGIN_DIR . 'admin/views/dashboard-page.php';
    }

    /**
     * Calculate duration between two datetime strings.
     * Returns a human-readable string (e.g., "1h 30m 15s") or total seconds/hours.
     */
    private static function calculate_duration($start_time_str, $end_time_str, $format = 'string') {
        if (empty($end_time_str) || empty($start_time_str)) {
            if ($format === 'seconds') return 0;
            if ($format === 'hours') return 0;
            return 'N/A';
        }

        try {
            $start_time = new DateTime($start_time_str, new DateTimeZone(wp_timezone_string()));
            $end_time = new DateTime($end_time_str, new DateTimeZone(wp_timezone_string()));
            
            $timestamp_diff = $end_time->getTimestamp() - $start_time->getTimestamp();
            if ($timestamp_diff < 0) $timestamp_diff = 0; // Duration cannot be negative

            if ($format === 'seconds') {
                return $timestamp_diff;
            }
            if ($format === 'hours') {
                 return $timestamp_diff > 0 ? $timestamp_diff / 3600 : 0;
            }

            // Format as string H M S
            $hours = floor($timestamp_diff / 3600);
            $mins = floor(($timestamp_diff % 3600) / 60);
            $secs = $timestamp_diff % 60;

            $duration_str = '';
            if ($hours > 0) $duration_str .= $hours . 'h ';
            if ($mins > 0) $duration_str .= $mins . 'm ';
            $duration_str .= $secs . 's';
            
            return trim($duration_str) ?: '0s';

        } catch (Exception $e) {
            // Log error or handle it
            error_log("Error calculating duration: " . $e->getMessage());
            if ($format === 'seconds') return 0;
            if ($format === 'hours') return 0;
            return 'Error';
        }
    }


    /**
     * Handle AJAX request for dashboard data.
     */
    public static function ajax_get_dashboard_data() {
        ejpt_log('AJAX call received.', __METHOD__);
        ejpt_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('ejpt_dashboard_nonce', 'nonce');

        if (!current_user_can(ejpt_get_capability())) {
            ejpt_log('AJAX Error: Permission denied for dashboard data.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 25;
        if ($length === -1) { 
            $length = 999999; 
        }
        
        $search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

        $filter_employee_id = isset($_POST['filter_employee_id']) && !empty($_POST['filter_employee_id']) ? intval($_POST['filter_employee_id']) : null;
        $filter_job_number = isset($_POST['filter_job_number']) && !empty($_POST['filter_job_number']) ? sanitize_text_field($_POST['filter_job_number']) : null;
        $filter_phase_id = isset($_POST['filter_phase_id']) && !empty($_POST['filter_phase_id']) ? intval($_POST['filter_phase_id']) : null;
        $filter_date_from = isset($_POST['filter_date_from']) && !empty($_POST['filter_date_from']) ? sanitize_text_field($_POST['filter_date_from']) : null;
        $filter_date_to = isset($_POST['filter_date_to']) && !empty($_POST['filter_date_to']) ? sanitize_text_field($_POST['filter_date_to']) : null;
        $filter_status = isset($_POST['filter_status']) && !empty($_POST['filter_status']) ? sanitize_text_field($_POST['filter_status']) : null;

        $order_column_index = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 4; 
        $order_dir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'desc';
        
        $columns = array(
            'employee_name' => 'e.last_name', 
            'employee_number' => 'e.employee_number',
            'job_number' => 'jl.job_number',
            'phase_name' => 'p.phase_name',
            'start_time' => 'jl.start_time',
            'end_time' => 'jl.end_time',
            'duration' => null, 
            'boxes_completed' => 'jl.boxes_completed',
            'items_completed' => 'jl.items_completed',
            'time_per_box' => null,
            'time_per_item' => null,
            'boxes_per_hour' => null,
            'items_per_hour' => null,
            'status' => 'jl.status',
            'notes' => 'jl.notes'
        );
        $column_keys = array_keys($columns);
        $orderby_db_col = isset($column_keys[$order_column_index]) ? $columns[$column_keys[$order_column_index]] : 'jl.start_time';
        
        if ($column_keys[$order_column_index] == 'employee_name') {
             $orderby_db_col = 'e.last_name ' . $order_dir . ', e.first_name'; 
        } elseif (is_null($orderby_db_col)) {
            $orderby_db_col = 'jl.start_time'; 
        }

        $args = array(
            'number'         => $length,
            'offset'         => $start,
            'orderby'        => $orderby_db_col,
            'order'          => $order_dir,
            'search_general' => $search_value, 
            'employee_id'    => $filter_employee_id,
            'job_number'     => $filter_job_number,
            'phase_id'       => $filter_phase_id,
            'date_from'      => $filter_date_from,
            'date_to'        => $filter_date_to,
            'status'         => $filter_status,
        );
        ejpt_log($args, 'Dashboard data query args: ');

        $logs = EJPT_DB::get_job_logs($args);
        $total_records_args = array_intersect_key($args, array_flip(['search_general', 'employee_id', 'job_number', 'phase_id', 'date_from', 'date_to', 'status']));
        $total_filtered_records = EJPT_DB::get_job_logs_count($total_records_args);
        $total_records = EJPT_DB::get_job_logs_count(); 

        ejpt_log('Dashboard data counts: Total=' . $total_records . ', Filtered=' . $total_filtered_records . ', Fetched for page=' . count($logs), 'Results for ' . __METHOD__);

        $data = array();
        foreach ($logs as $log) {
            $duration_seconds = self::calculate_duration($log->start_time, $log->end_time, 'seconds');
            $duration_hours = self::calculate_duration($log->start_time, $log->end_time, 'hours');
            $duration_display = self::calculate_duration($log->start_time, $log->end_time, 'string');

            $boxes_completed = !is_null($log->boxes_completed) ? intval($log->boxes_completed) : 0;
            $items_completed = !is_null($log->items_completed) ? intval($log->items_completed) : 0;

            $time_per_box_seconds = ($boxes_completed > 0 && $duration_seconds > 0) ? round($duration_seconds / $boxes_completed) : 0; 
            $time_per_item_seconds = ($items_completed > 0 && $duration_seconds > 0) ? round($duration_seconds / $items_completed) : 0; 
            
            $boxes_per_hour = ($duration_hours > 0 && $boxes_completed > 0) ? round($boxes_completed / $duration_hours, 2) : 0;
            $items_per_hour = ($duration_hours > 0 && $items_completed > 0) ? round($items_completed / $duration_hours, 2) : 0;

            $employee_name = esc_html($log->first_name . ' ' . $log->last_name);
            $status_badge = '';
            if ($log->status === 'started') {
                $status_badge = '<span class="dashicons dashicons-clock" style="color:orange; font-size:1.2em; vertical-align:middle;" title="Started"></span> <span style="vertical-align:middle;">' . __('Running', 'ejpt') . '</span>';
            } elseif ($log->status === 'completed') {
                $status_badge = '<span class="dashicons dashicons-yes-alt" style="color:green; font-size:1.2em; vertical-align:middle;" title="Completed"></span> <span style="vertical-align:middle;">' . __('Completed', 'ejpt') . '</span>';
            } else {
                $status_badge = esc_html(ucfirst($log->status));
            }

            $data[] = array(
                'log_id'           => $log->log_id,
                'employee_name'    => $employee_name,
                // 'employee_number'  => esc_html($log->employee_number), // Removed as per request
                'job_number'       => esc_html($log->job_number),
                'phase_name'       => esc_html($log->phase_name),
                'start_time'       => esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->start_time))),
                'end_time'         => $log->end_time ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->end_time))) : 'N/A',
                'duration'         => $duration_display,
                'boxes_completed'  => $boxes_completed,
                'items_completed'  => $items_completed,
                'time_per_box'     => $time_per_box_seconds > 0 ? sprintf('%02dh %02dm %02ds', floor($time_per_box_seconds/3600), floor(($time_per_box_seconds%3600)/60), ($time_per_box_seconds%60)) : 'N/A',
                'time_per_item'    => $time_per_item_seconds > 0 ? sprintf('%02dh %02dm %02ds', floor($time_per_item_seconds/3600), floor(($time_per_item_seconds%3600)/60), ($time_per_item_seconds%60)) : 'N/A',
                'boxes_per_hour'   => $boxes_per_hour,
                'items_per_hour'   => $items_per_hour,
                'status'           => $status_badge,
                'notes'            => nl2br(esc_html($log->notes)),
            );
        }

        ejpt_log('Sending dashboard data to DataTables. Draw: ' . $draw . ', Records: ' . count($data), 'Response for ' . __METHOD__);
        wp_send_json(array(
            'draw'            => $draw,
            'recordsTotal'    => $total_records,
            'recordsFiltered' => $total_filtered_records,
            'data'            => $data,
        ));
    }

    /**
     * Handle AJAX request to get details for a single job log for editing.
     */
    public static function ajax_get_job_log_details() {
        check_ajax_referer('ejpt_edit_log_nonce', 'nonce'); // Or a more specific nonce
        ejpt_log('AJAX: Get job log details', __METHOD__);
        ejpt_log($_POST, 'POST data for ' . __METHOD__);

        if (!current_user_can(ejpt_get_capability())) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if ($log_id <= 0) {
            wp_send_json_error(['message' => 'Invalid Log ID.']);
            return;
        }

        $log_details = EJPT_DB::get_job_log($log_id);

        if ($log_details) {
            // Format datetime fields for datetime-local input
            if (!empty($log_details->start_time)) {
                $log_details->start_time = date('Y-m-d\TH:i', strtotime($log_details->start_time));
            }
            if (!empty($log_details->end_time)) {
                $log_details->end_time = date('Y-m-d\TH:i', strtotime($log_details->end_time));
            }
            wp_send_json_success($log_details);
        } else {
            wp_send_json_error(['message' => 'Job log not found.']);
        }
    }

    /**
     * Handle AJAX request to update a job log entry.
     */
    public static function ajax_update_job_log() {
        check_ajax_referer('ejpt_edit_log_nonce', 'ejpt_edit_log_nonce_field'); // Corrected nonce action
        ejpt_log('AJAX: Update job log received.', __METHOD__);
        ejpt_log($_POST, 'POST data for ' . __METHOD__);

        if (!current_user_can(ejpt_get_capability())) {
            ejpt_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $log_id = isset($_POST['edit_log_id']) ? intval($_POST['edit_log_id']) : 0;
        if ($log_id <= 0) {
            ejpt_log('AJAX Error: Invalid Log ID for update: ' . $log_id, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Log ID for update.']);
            return;
        }

        $data_to_update = array();
        
        if (isset($_POST['edit_log_employee_id'])) $data_to_update['employee_id'] = intval($_POST['edit_log_employee_id']);
        if (isset($_POST['edit_log_job_number'])) $data_to_update['job_number'] = sanitize_text_field($_POST['edit_log_job_number']);
        if (isset($_POST['edit_log_phase_id'])) $data_to_update['phase_id'] = intval($_POST['edit_log_phase_id']);
        
        if (isset($_POST['edit_log_start_time']) && !empty($_POST['edit_log_start_time'])) {
            try {
                $start_time_dt = new DateTime($_POST['edit_log_start_time'], wp_timezone());
                $data_to_update['start_time'] = $start_time_dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                ejpt_log('AJAX Error: Invalid start_time format: ' . $_POST['edit_log_start_time'], __METHOD__);
                wp_send_json_error(['message' => 'Invalid Start Time format.']); return;
            }
        } 

        if (isset($_POST['edit_log_end_time'])) {
            if (!empty($_POST['edit_log_end_time'])) {
                try {
                    $end_time_dt = new DateTime($_POST['edit_log_end_time'], wp_timezone());
                    $data_to_update['end_time'] = $end_time_dt->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    ejpt_log('AJAX Error: Invalid end_time format: ' . $_POST['edit_log_end_time'], __METHOD__);
                    wp_send_json_error(['message' => 'Invalid End Time format.']); return;
                }
            } else {
                $data_to_update['end_time'] = null; 
            }
        }

        if (isset($_POST['edit_log_boxes_completed'])) {
            $data_to_update['boxes_completed'] = !empty($_POST['edit_log_boxes_completed']) || $_POST['edit_log_boxes_completed'] === '0' ? intval($_POST['edit_log_boxes_completed']) : null;
        }
        if (isset($_POST['edit_log_items_completed'])) {
            $data_to_update['items_completed'] = !empty($_POST['edit_log_items_completed']) || $_POST['edit_log_items_completed'] === '0' ? intval($_POST['edit_log_items_completed']) : null;
        }
        if (isset($_POST['edit_log_status'])) $data_to_update['status'] = sanitize_text_field($_POST['edit_log_status']);
        if (isset($_POST['edit_log_notes'])) $data_to_update['notes'] = sanitize_textarea_field($_POST['edit_log_notes']);

        if (empty($data_to_update['employee_id']) || empty($data_to_update['job_number']) || empty($data_to_update['phase_id']) || empty($data_to_update['start_time']) || empty($data_to_update['status'])) {
            ejpt_log('AJAX Error: Missing required fields for log update.', $data_to_update);
            wp_send_json_error(['message' => 'Missing required fields for log update (Employee, Job, Phase, Start Time, Status).']);
            return;
        }
        
        ejpt_log('Data prepared for DB update_job_log: ', $data_to_update);
        $result = EJPT_DB::update_job_log($log_id, $data_to_update);

        if (is_wp_error($result)) {
            ejpt_log('AJAX Error from DB update_job_log: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error updating job log: ' . $result->get_error_message()]);
        } else {
            ejpt_log('AJAX Success: Job log updated. Log ID: ' . $log_id, __METHOD__);
            wp_send_json_success(['message' => 'Job log updated successfully.']);
        }
    }

    /**
     * Handle AJAX request to delete a job log entry.
     */
    public static function ajax_delete_job_log() {
        check_ajax_referer('ejpt_delete_log_nonce', 'nonce'); // A new nonce for delete action
        ejpt_log('AJAX: Delete job log request received.', __METHOD__);
        ejpt_log($_POST, 'POST data for ' . __METHOD__);

        if (!current_user_can(ejpt_get_capability())) { // Ensure admin capability
            ejpt_log('AJAX Error: Permission denied for deleting job log.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if ($log_id <= 0) {
            ejpt_log('AJAX Error: Invalid Log ID for deletion: ' . $log_id, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Log ID for deletion.']);
            return;
        }

        $result = EJPT_DB::delete_job_log($log_id);

        if (is_wp_error($result)) {
            ejpt_log('AJAX Error from DB delete_job_log: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error deleting job log: ' . $result->get_error_message()]);
        } else {
            ejpt_log('AJAX Success: Job log deleted. Log ID: ' . $log_id, __METHOD__);
            wp_send_json_success(['message' => 'Job log deleted successfully.']);
        }
    }
} 