<?php
// /includes/class-oo-dashboard.php // Renamed file

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class OO_Dashboard { // Renamed class

    public static function display_dashboard_page() {
        if ( ! current_user_can( oo_get_capability() ) ) { 
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'operations-organizer' ) );
        }
        
        $GLOBALS['employees'] = OO_DB::get_employees(array('is_active' => 1, 'orderby' => 'last_name', 'order' => 'ASC', 'number' => -1));
        // For the Quick Actions, we might want all stream types, or let user select a stream type first, then its phases.
        // For now, let's get all phases and group them by stream type in the view, or filter later.
        $GLOBALS['phases'] = OO_DB::get_phases(array('is_active' => 1, 'orderby' => 'stream_type_id, sort_order')); 
        $GLOBALS['stream_types'] = OO_DB::get_stream_types(array('is_active' => 1)); // For phase management context

        include_once OO_PLUGIN_DIR . 'admin/views/dashboard-page.php';
    }

    private static function calculate_duration($start_time_str, $end_time_str, $format = 'string') {
        // ... (this method remains the same, no OO_ specific changes needed internally)
        if (empty($end_time_str) || empty($start_time_str)) {
            if ($format === 'seconds') return 0;
            if ($format === 'hours') return 0;
            return 'N/A';
        }
        try {
            $start_time = new DateTime($start_time_str, new DateTimeZone(wp_timezone_string()));
            $end_time = new DateTime($end_time_str, new DateTimeZone(wp_timezone_string()));
            $timestamp_diff = $end_time->getTimestamp() - $start_time->getTimestamp();
            if ($timestamp_diff < 0) $timestamp_diff = 0;
            if ($format === 'seconds') return $timestamp_diff;
            if ($format === 'hours') return $timestamp_diff > 0 ? $timestamp_diff / 3600 : 0;
            $hours = floor($timestamp_diff / 3600);
            $mins = floor(($timestamp_diff % 3600) / 60);
            $secs = $timestamp_diff % 60;
            $duration_str = '';
            if ($hours > 0) $duration_str .= $hours . 'h ';
            if ($mins > 0) $duration_str .= $mins . 'm ';
            $duration_str .= $secs . 's';
            return trim($duration_str) ?: '0s';
        } catch (Exception $e) {
            oo_log("Error calculating duration: " . $e->getMessage(), __METHOD__); // Use oo_log
            if ($format === 'seconds') return 0;
            if ($format === 'hours') return 0;
            return 'Error';
        }
    }

    public static function ajax_get_dashboard_data() {
        oo_log('AJAX call received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        check_ajax_referer('oo_dashboard_nonce', 'nonce'); // TODO: Update nonce name

        if (!current_user_can(oo_get_capability())) {
            oo_log('AJAX Error: Permission denied for dashboard data.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        // ... (data parsing from $_POST remains similar for now) ...
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 25;
        if ($length === -1) { $length = 999999; }
        $search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
        $filter_employee_id = isset($_POST['filter_employee_id']) && !empty($_POST['filter_employee_id']) ? intval($_POST['filter_employee_id']) : null;
        $filter_job_number = isset($_POST['filter_job_number']) && !empty($_POST['filter_job_number']) ? sanitize_text_field($_POST['filter_job_number']) : null;
        $filter_phase_id = isset($_POST['filter_phase_id']) && !empty($_POST['filter_phase_id']) ? intval($_POST['filter_phase_id']) : null;
        $filter_stream_type_id = isset($_POST['filter_stream_type_id']) && !empty($_POST['filter_stream_type_id']) ? intval($_POST['filter_stream_type_id']) : null; // New filter
        $filter_date_from = isset($_POST['filter_date_from']) && !empty($_POST['filter_date_from']) ? sanitize_text_field($_POST['filter_date_from']) : null;
        $filter_date_to = isset($_POST['filter_date_to']) && !empty($_POST['filter_date_to']) ? sanitize_text_field($_POST['filter_date_to']) : null;
        $filter_status = isset($_POST['filter_status']) && !empty($_POST['filter_status']) ? sanitize_text_field($_POST['filter_status']) : null;
        $order_column_index = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 4;
        $order_dir = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'desc';
        
        $columns = [
            'employee_name' => 'e.last_name',
            'job_number' => 'jl.job_number',
            'stream_type_name' => 'st.stream_type_name', // New column
            'phase_name' => 'p.phase_name',
            'start_time' => 'jl.start_time',
            'end_time' => 'jl.end_time',
            'duration' => null,
            // KPI columns might become dynamic based on stream_type_id via kpi_fields_config
            'boxes_completed' => 'jl.boxes_completed', 
            'items_completed' => 'jl.items_completed',
            'kpi_data' => 'jl.kpi_data', // New general KPI data
            'time_per_box' => null,
            'time_per_item' => null,
            'boxes_per_hour' => null,
            'items_per_hour' => null,
            'status' => 'jl.status',
            'notes' => 'jl.notes'
        ];
        $column_keys = array_keys($columns);
        $orderby_db_col = isset($column_keys[$order_column_index]) && !is_null($columns[$column_keys[$order_column_index]]) 
                            ? $columns[$column_keys[$order_column_index]] 
                            : 'jl.start_time'; // Default if column not directly sortable or mapping missing
        
        if ($column_keys[$order_column_index] == 'employee_name') {
             $orderby_db_col = 'e.last_name ' . $order_dir . ', e.first_name'; 
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
            'stream_type_id' => $filter_stream_type_id, // New arg for DB query
            'date_from'      => $filter_date_from,
            'date_to'        => $filter_date_to,
            'status'         => $filter_status,
        );
        oo_log($args, 'Dashboard data query args: ');

        $logs = OO_DB::get_job_logs($args);
        $count_args = $args; unset($count_args['number']); unset($count_args['offset']); unset($count_args['orderby']); unset($count_args['order']);
        $total_filtered_records = OO_DB::get_job_logs_count($count_args);
        $total_records = OO_DB::get_job_logs_count(array('job_number' => null)); // Simpler count for all

        oo_log('Dashboard data counts: Total=' . $total_records . ', Filtered=' . $total_filtered_records . ', Fetched for page=' . count($logs), 'Results for ' . __METHOD__);

        $data = array();
        foreach ($logs as $log) {
            $duration_seconds = self::calculate_duration($log->start_time, $log->end_time, 'seconds');
            $duration_hours = self::calculate_duration($log->start_time, $log->end_time, 'hours');
            $duration_display = self::calculate_duration($log->start_time, $log->end_time, 'string');

            // Handle existing and new kpi_data
            $kpis = !empty($log->kpi_data) ? json_decode($log->kpi_data, true) : array();
            if (!is_array($kpis)) $kpis = array();

            $boxes_completed = isset($kpis['boxes_completed']) ? intval($kpis['boxes_completed']) : (!is_null($log->boxes_completed) ? intval($log->boxes_completed) : 0);
            $items_completed = isset($kpis['items_completed']) ? intval($kpis['items_completed']) : (!is_null($log->items_completed) ? intval($log->items_completed) : 0);

            // Generic KPI display (example - could be more complex)
            $kpi_display_array = array();
            if ($boxes_completed > 0) $kpi_display_array[] = "Boxes: $boxes_completed";
            if ($items_completed > 0) $kpi_display_array[] = "Items: $items_completed";
            // Loop through other items in $kpis if defined by stream_type kpi_config
            // This part will need significant work once kpi_fields_config is used

            $time_per_box_seconds = ($boxes_completed > 0 && $duration_seconds > 0) ? round($duration_seconds / $boxes_completed) : 0; 
            $time_per_item_seconds = ($items_completed > 0 && $duration_seconds > 0) ? round($duration_seconds / $items_completed) : 0; 
            $boxes_per_hour = ($duration_hours > 0 && $boxes_completed > 0) ? round($boxes_completed / $duration_hours, 2) : 0;
            $items_per_hour = ($duration_hours > 0 && $items_completed > 0) ? round($items_completed / $duration_hours, 2) : 0;

            $employee_name = esc_html($log->first_name . ' ' . $log->last_name);
            $status_badge = ''; // (status badge logic remains same)
            if ($log->status === 'started') {
                $status_badge = '<span class="dashicons dashicons-clock" style="color:orange; font-size:1.2em; vertical-align:middle;" title="Started"></span> <span style="vertical-align:middle;">' . __('Running', 'operations-organizer') . '</span>';
            } elseif ($log->status === 'completed') {
                $status_badge = '<span class="dashicons dashicons-yes-alt" style="color:green; font-size:1.2em; vertical-align:middle;" title="Completed"></span> <span style="vertical-align:middle;">' . __('Completed', 'operations-organizer') . '</span>';
            } else {
                $status_badge = esc_html(ucfirst($log->status));
            }

            $data[] = array(
                'log_id'           => $log->log_id,
                'employee_name'    => $employee_name,
                'job_number'       => esc_html($log->job_number),
                'stream_type_name' => isset($log->stream_type_name) ? esc_html($log->stream_type_name) : __('N/A', 'operations-organizer'),
                'phase_name'       => esc_html($log->phase_name),
                'start_time'       => esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->start_time))),
                'end_time'         => $log->end_time ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->end_time))) : 'N/A',
                'duration'         => $duration_display,
                'boxes_completed'  => $boxes_completed, // Still here for now
                'items_completed'  => $items_completed, // Still here for now
                'kpi_data'         => !empty($kpi_display_array) ? implode(', ', $kpi_display_array) : ( !empty($log->kpi_data) && $log->kpi_data !=='null' && $log->kpi_data !=='[]' ? esc_html($log->kpi_data) : 'N/A'), // Simple display of kpi_data for now
                'time_per_box'     => $time_per_box_seconds > 0 ? sprintf('%02dh %02dm %02ds', floor($time_per_box_seconds/3600), floor(($time_per_box_seconds%3600)/60), ($time_per_box_seconds%60)) : 'N/A',
                'time_per_item'    => $time_per_item_seconds > 0 ? sprintf('%02dh %02dm %02ds', floor($time_per_item_seconds/3600), floor(($time_per_item_seconds%3600)/60), ($time_per_item_seconds%60)) : 'N/A',
                'boxes_per_hour'   => $boxes_per_hour,
                'items_per_hour'   => $items_per_hour,
                'status'           => $status_badge,
                'notes'            => nl2br(esc_html($log->notes)),
            );
        }

        oo_log('Sending dashboard data to DataTables. Draw: ' . $draw . ', Records: ' . count($data), 'Response for ' . __METHOD__);
        wp_send_json(array(
            'draw'            => $draw,
            'recordsTotal'    => $total_records,
            'recordsFiltered' => $total_filtered_records,
            'data'            => $data,
        ));
    }

    public static function ajax_get_job_log_details() {
        check_ajax_referer('oo_edit_log_nonce', 'nonce'); // TODO: Update nonce name
        oo_log('AJAX: Get job log details', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        if (!current_user_can(oo_get_capability())) {
            wp_send_json_error(['message' => 'Permission denied.'], 403); return;
        }
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if ($log_id <= 0) {
            wp_send_json_error(['message' => 'Invalid Log ID.']); return;
        }
        $log_details = OO_DB::get_job_log($log_id);
        if ($log_details) {
            if (!empty($log_details->start_time)) { $log_details->start_time = date('Y-m-d\TH:i', strtotime($log_details->start_time)); }
            if (!empty($log_details->end_time)) { $log_details->end_time = date('Y-m-d\TH:i', strtotime($log_details->end_time)); }
            // Prepare kpi_data for form (potentially pre-fill individual fields if they map directly)
            if (!empty($log_details->kpi_data)) {
                $kpis = json_decode($log_details->kpi_data, true);
                if (is_array($kpis)) {
                    // If we had specific form fields for kpi_data, we'd populate them here.
                    // For now, we might just pass the raw kpi_data string or decoded array.
                    // The edit form would need to dynamically generate fields based on stream_type kpi_config
                }
            }
            wp_send_json_success($log_details);
        } else {
            wp_send_json_error(['message' => 'Job log not found.']);
        }
    }

    public static function ajax_update_job_log() {
        check_ajax_referer('oo_edit_log_nonce', 'oo_edit_log_nonce_field'); // TODO: Update nonce names
        oo_log('AJAX: Update job log received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        if (!current_user_can(oo_get_capability())) {
            oo_log('AJAX Error: Permission denied.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403); return;
        }
        $log_id = isset($_POST['edit_log_id']) ? intval($_POST['edit_log_id']) : 0;
        if ($log_id <= 0) {
            oo_log('AJAX Error: Invalid Log ID for update: ' . $log_id, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Log ID for update.']); return;
        }
        $data_to_update = array();
        if (isset($_POST['edit_log_employee_id'])) $data_to_update['employee_id'] = intval($_POST['edit_log_employee_id']);
        if (isset($_POST['edit_log_job_number'])) $data_to_update['job_number'] = sanitize_text_field($_POST['edit_log_job_number']);
        if (isset($_POST['edit_log_phase_id'])) $data_to_update['phase_id'] = intval($_POST['edit_log_phase_id']);
        if (isset($_POST['edit_log_start_time']) && !empty($_POST['edit_log_start_time'])) {
            try { $start_time_dt = new DateTime($_POST['edit_log_start_time'], wp_timezone()); $data_to_update['start_time'] = $start_time_dt->format('Y-m-d H:i:s'); }
            catch (Exception $e) { oo_log('AJAX Error: Invalid start_time format: ' . $_POST['edit_log_start_time'], __METHOD__); wp_send_json_error(['message' => 'Invalid Start Time format.']); return; }
        } 
        if (isset($_POST['edit_log_end_time'])) {
            if (!empty($_POST['edit_log_end_time'])) {
                try { $end_time_dt = new DateTime($_POST['edit_log_end_time'], wp_timezone()); $data_to_update['end_time'] = $end_time_dt->format('Y-m-d H:i:s'); }
                catch (Exception $e) { oo_log('AJAX Error: Invalid end_time format: ' . $_POST['edit_log_end_time'], __METHOD__); wp_send_json_error(['message' => 'Invalid End Time format.']); return; }
            } else { $data_to_update['end_time'] = null; }
        }
        // Handle generic kpi_data from a textarea or dynamically generated fields
        if (isset($_POST['edit_log_kpi_data'])) { // Assuming a field named 'edit_log_kpi_data' holds JSON string
            $kpi_json_string = stripslashes($_POST['edit_log_kpi_data']);
            $decoded_kpis = json_decode($kpi_json_string, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_kpis)) {
                $data_to_update['kpi_data'] = $kpi_json_string; // Store valid JSON
                // Optionally re-populate boxes_completed/items_completed if they are part of this structure
                if (isset($decoded_kpis['boxes_completed'])) $data_to_update['boxes_completed'] = intval($decoded_kpis['boxes_completed']);
                if (isset($decoded_kpis['items_completed'])) $data_to_update['items_completed'] = intval($decoded_kpis['items_completed']);
            } else {
                oo_log('AJAX Warning: kpi_data was not valid JSON, not updating kpi_data field.', $_POST['edit_log_kpi_data']);
            }
        } else { // Fallback to individual KPI fields if kpi_data field is not present
            if (isset($_POST['edit_log_boxes_completed'])) $data_to_update['boxes_completed'] = !empty($_POST['edit_log_boxes_completed']) || $_POST['edit_log_boxes_completed'] === '0' ? intval($_POST['edit_log_boxes_completed']) : null;
            if (isset($_POST['edit_log_items_completed'])) $data_to_update['items_completed'] = !empty($_POST['edit_log_items_completed']) || $_POST['edit_log_items_completed'] === '0' ? intval($_POST['edit_log_items_completed']) : null;
        }

        if (isset($_POST['edit_log_status'])) $data_to_update['status'] = sanitize_text_field($_POST['edit_log_status']);
        if (isset($_POST['edit_log_notes'])) $data_to_update['notes'] = sanitize_textarea_field($_POST['edit_log_notes']);
        if (empty($data_to_update['employee_id']) || empty($data_to_update['job_number']) || empty($data_to_update['phase_id']) || empty($data_to_update['start_time']) || empty($data_to_update['status'])) {
            oo_log('AJAX Error: Missing required fields for log update.', $data_to_update);
            wp_send_json_error(['message' => 'Missing required fields for log update (Employee, Job, Phase, Start Time, Status).']); return;
        }
        oo_log('Data prepared for DB update_job_log: ', $data_to_update);
        $result = OO_DB::update_job_log($log_id, $data_to_update);
        if (is_wp_error($result)) {
            oo_log('AJAX Error from DB update_job_log: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error updating job log: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Job log updated. Log ID: ' . $log_id, __METHOD__);
            wp_send_json_success(['message' => 'Job log updated successfully.']);
        }
    }

    public static function ajax_delete_job_log() {
        check_ajax_referer('oo_delete_log_nonce', 'nonce'); // TODO: Update nonce name 
        oo_log('AJAX: Delete job log request received.', __METHOD__);
        oo_log($_POST, 'POST data for ' . __METHOD__);
        if (!current_user_can(oo_get_capability())) { 
            oo_log('AJAX Error: Permission denied for deleting job log.', __METHOD__);
            wp_send_json_error(['message' => 'Permission denied.'], 403); return;
        }
        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if ($log_id <= 0) {
            oo_log('AJAX Error: Invalid Log ID for deletion: ' . $log_id, __METHOD__);
            wp_send_json_error(['message' => 'Invalid Log ID for deletion.']); return;
        }
        $result = OO_DB::delete_job_log($log_id);
        if (is_wp_error($result)) {
            oo_log('AJAX Error from DB delete_job_log: ' . $result->get_error_message(), __METHOD__);
            wp_send_json_error(['message' => 'Error deleting job log: ' . $result->get_error_message()]);
        } else {
            oo_log('AJAX Success: Job log deleted. Log ID: ' . $log_id, __METHOD__);
            wp_send_json_success(['message' => 'Job log deleted successfully.']);
        }
    }
} 