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
                'employee_name'    => $employee_name,
                'employee_number'  => esc_html($log->employee_number),
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
} 