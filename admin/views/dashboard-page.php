<?php
// /admin/views/dashboard-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
// Data for this page ($employees, $phases) is prepared in EJPT_Dashboard::display_dashboard_page()
// and passed via global variables.
global $employees, $phases; 
?>
<div class="wrap ejpt-dashboard-page">
    <h1><?php esc_html_e( 'Job Logs Dashboard', 'ejpt' ); ?></h1>

    <div id="ejpt-dashboard-filters">
        <div class="filter-item">
            <label for="filter_date_from"><?php esc_html_e('Date From:', 'ejpt');?></label>
            <input type="text" id="filter_date_from" name="filter_date_from" class="ejpt-datepicker" placeholder="YYYY-MM-DD">
        </div>
        <div class="filter-item">
            <label for="filter_date_to"><?php esc_html_e('Date To:', 'ejpt');?></label>
            <input type="text" id="filter_date_to" name="filter_date_to" class="ejpt-datepicker" placeholder="YYYY-MM-DD">
        </div>
        <div class="filter-item">
            <label for="filter_employee_id"><?php esc_html_e('Employee:', 'ejpt');?></label>
            <select id="filter_employee_id" name="filter_employee_id">
                <option value=""><?php esc_html_e('All Employees', 'ejpt');?></option>
                <?php if (!empty($employees)): foreach ($employees as $employee): ?>
                    <option value="<?php echo esc_attr($employee->employee_id); ?>">
                        <?php echo esc_html($employee->first_name . ' ' . $employee->last_name . ' (' . $employee->employee_number . ')'); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_job_number"><?php esc_html_e('Job Number:', 'ejpt');?></label>
            <input type="text" id="filter_job_number" name="filter_job_number" placeholder="<?php esc_attr_e('Enter Job No.', 'ejpt'); ?>">
        </div>
        <div class="filter-item">
            <label for="filter_phase_id"><?php esc_html_e('Phase:', 'ejpt');?></label>
            <select id="filter_phase_id" name="filter_phase_id">
                <option value=""><?php esc_html_e('All Phases', 'ejpt');?></option>
                 <?php if (!empty($phases)): foreach ($phases as $phase): ?>
                    <option value="<?php echo esc_attr($phase->phase_id); ?>">
                        <?php echo esc_html($phase->phase_name); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_status"><?php esc_html_e('Status:', 'ejpt');?></label>
            <select id="filter_status" name="filter_status">
                <option value=""><?php esc_html_e('All Statuses', 'ejpt');?></option>
                <option value="started"><?php esc_html_e('Running', 'ejpt');?></option>
                <option value="completed"><?php esc_html_e('Completed', 'ejpt');?></option>
            </select>
        </div>
        <div class="filter-item">
            <button id="apply_filters_button" class="button button-primary"><?php esc_html_e('Apply Filters', 'ejpt');?></button>
            <button id="clear_filters_button" class="button"><?php esc_html_e('Clear Filters', 'ejpt');?></button>
        </div>
    </div>
    
    <div id="ejpt-export-options" style="margin-bottom: 20px;">
        <button id="export_csv_button" class="button"><?php esc_html_e('Export to CSV', 'ejpt');?></button>
    </div>

    <div id="ejpt-quick-actions" style="margin-bottom: 30px; padding: 15px; background-color: #fff; border: 1px solid #ccd0d4;">
        <h2><?php esc_html_e('Quick Phase Actions', 'ejpt'); ?></h2>
        <p><?php esc_html_e('Enter a Job Number and select a phase to generate Start/Stop links.', 'ejpt'); ?></p>
        <?php 
        // --- DASHBOARD VIEW DEBUGGING ---
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            ejpt_log('Dashboard View: $phases variable content just before '!empty()' check:', 'dashboard-view');
            ejpt_log($phases, 'dashboard-view-phases-var');
            ejpt_log('Dashboard View: Result of empty($phases) is: ' . (empty($phases) ? 'true (empty)' : 'false (not empty)'), 'dashboard-view');
            ejpt_log('Dashboard View: Result of count($phases) is: ' . count((array)$phases), 'dashboard-view');
        }
        // --- END DASHBOARD VIEW DEBUGGING ---
        if ( !empty($phases) && count((array)$phases) > 0 ) : ?>
            <table class="form-table">
                <?php foreach ($phases as $phase) : ?>
                    <tr valign="top" class="ejpt-phase-action-row">
                        <th scope="row" style="width: 200px;"><?php echo esc_html($phase->phase_name); ?></th>
                        <td>
                            <input type="text" class="ejpt-job-number-input" placeholder="<?php esc_attr_e('Enter Job Number', 'ejpt'); ?>" style="width: 200px; margin-right: 10px;">
                            <button class="button ejpt-start-link-btn" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Start Link', 'ejpt'); ?></button>
                            <button class="button ejpt-stop-link-btn" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Stop Link', 'ejpt'); ?></button>
                            <span class="ejpt-generated-link-area" style="margin-left: 15px;"></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No active phases available to generate actions. Please add some phases first.', 'ejpt'); ?></p>
        <?php endif; ?>
    </div>

    <table id="ejpt-dashboard-table" class="display wp-list-table widefat fixed striped" style="width:100%">
        <thead>
            <tr>
                <th><?php esc_html_e('Employee', 'ejpt'); ?></th>
                <th><?php esc_html_e('Emp. No.', 'ejpt'); ?></th>
                <th><?php esc_html_e('Job No.', 'ejpt'); ?></th>
                <th><?php esc_html_e('Phase', 'ejpt'); ?></th>
                <th><?php esc_html_e('Start Time', 'ejpt'); ?></th>
                <th><?php esc_html_e('End Time', 'ejpt'); ?></th>
                <th><?php esc_html_e('Duration', 'ejpt'); ?></th>
                <th><?php esc_html_e('Boxes', 'ejpt'); ?></th>
                <th><?php esc_html_e('Items', 'ejpt'); ?></th>
                <th><?php esc_html_e('Time/Box', 'ejpt'); ?></th>
                <th><?php esc_html_e('Time/Item', 'ejpt'); ?></th>
                <th><?php esc_html_e('Boxes/Hr', 'ejpt'); ?></th>
                <th><?php esc_html_e('Items/Hr', 'ejpt'); ?></th>
                <th><?php esc_html_e('Status', 'ejpt'); ?></th>
                <th><?php esc_html_e('Notes', 'ejpt'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Data will be loaded by DataTables via AJAX -->
        </tbody>
         <tfoot>
            <tr>
                <th><?php esc_html_e('Employee', 'ejpt'); ?></th>
                <th><?php esc_html_e('Emp. No.', 'ejpt'); ?></th>
                <th><?php esc_html_e('Job No.', 'ejpt'); ?></th>
                <th><?php esc_html_e('Phase', 'ejpt'); ?></th>
                <th><?php esc_html_e('Start Time', 'ejpt'); ?></th>
                <th><?php esc_html_e('End Time', 'ejpt'); ?></th>
                <th><?php esc_html_e('Duration', 'ejpt'); ?></th>
                <th><?php esc_html_e('Boxes', 'ejpt'); ?></th>
                <th><?php esc_html_e('Items', 'ejpt'); ?></th>
                <th><?php esc_html_e('Time/Box', 'ejpt'); ?></th>
                <th><?php esc_html_e('Time/Item', 'ejpt'); ?></th>
                <th><?php esc_html_e('Boxes/Hr', 'ejpt'); ?></th>
                <th><?php esc_html_e('Items/Hr', 'ejpt'); ?></th>
                <th><?php esc_html_e('Status', 'ejpt'); ?></th>
                <th><?php esc_html_e('Notes', 'ejpt'); ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Datepicker init is in admin-scripts.js

    var dashboardTable = $('#ejpt-dashboard-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ejpt_ajax.ajax_url,
            type: 'POST',
            data: function(d) { 
                d.action = 'ejpt_get_dashboard_data';
                d.nonce = '<?php echo wp_create_nonce("ejpt_dashboard_nonce"); ?>';
                d.filter_employee_id = $('#filter_employee_id').val();
                d.filter_job_number = $('#filter_job_number').val();
                d.filter_phase_id = $('#filter_phase_id').val();
                d.filter_date_from = $('#filter_date_from').val();
                d.filter_date_to = $('#filter_date_to').val();
                d.filter_status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'employee_name' },
            { data: 'employee_number' },
            { data: 'job_number' },
            { data: 'phase_name' },
            { data: 'start_time' },
            { data: 'end_time' },
            { data: 'duration', orderable: false }, 
            { data: 'boxes_completed' },
            { data: 'items_completed' },
            { data: 'time_per_box', orderable: false },
            { data: 'time_per_item', orderable: false },
            { data: 'boxes_per_hour', orderable: false },
            { data: 'items_per_hour', orderable: false },
            { data: 'status' },
            { data: 'notes', orderable: false, render: function(data, type, row) {
                var escData = $('<div>').text(data).html(); 
                if (type === 'display' && escData && escData.length > 50) {
                    return '<span title="'+escData.replace(/"/g, '&quot;')+'">' + escData.substr(0, 50) + '...</span>';
                }
                return escData;
              } 
            }
        ],
        order: [[4, 'desc']], // Default order by start_time descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        language: {
            search: "<?php esc_attr_e('Search table:', 'ejpt'); ?>",
            lengthMenu: "<?php esc_attr_e('Show _MENU_ entries', 'ejpt'); ?>",
            info: "<?php esc_attr_e('Showing _START_ to _END_ of _TOTAL_ entries', 'ejpt'); ?>",
            paginate: {
                first: "<?php esc_attr_e('First', 'ejpt'); ?>",
                last: "<?php esc_attr_e('Last', 'ejpt'); ?>",
                next: "<?php esc_attr_e('Next', 'ejpt'); ?>",
                previous: "<?php esc_attr_e('Previous', 'ejpt'); ?>"
            }
        }
    });

    // Apply filters button
    $('#apply_filters_button').on('click', function() {
        dashboardTable.ajax.reload(); 
    });

    // Clear filters button
    $('#clear_filters_button').on('click', function() {
        $('#ejpt-dashboard-filters input[type="text"]').val('');
        $('#ejpt-dashboard-filters input[type="date"]').val('');
        $('#ejpt-dashboard-filters select').val('');
        dashboardTable.search('').columns().search('').draw();
        // dashboardTable.ajax.reload(); // draw() above should trigger reload with cleared filters.
    });
    
    // Export to CSV
    $('#export_csv_button').on('click', function() {
        var currentAjaxParams = dashboardTable.ajax.params();
        var exportParams = $.extend({}, currentAjaxParams, {
            length: -1, // Fetch all records for export
            action: 'ejpt_get_dashboard_data', // Ensure action and nonce are correctly set for export
            nonce: '<?php echo wp_create_nonce("ejpt_dashboard_nonce"); ?>'
        });

        // We need to add custom filters to the exportParams if they are not already part of currentAjaxParams.data
        exportParams.filter_employee_id = $('#filter_employee_id').val();
        exportParams.filter_job_number = $('#filter_job_number').val();
        exportParams.filter_phase_id = $('#filter_phase_id').val();
        exportParams.filter_date_from = $('#filter_date_from').val();
        export_params.filter_date_to = $('#filter_date_to').val();
        exportParams.filter_status = $('#filter_status').val();

        // Remove DataTables specific parameters not needed for our AJAX handler or that might conflict
        delete exportParams.draw;
        delete exportParams.columns;
        delete exportParams.order;
        delete exportParams.start;
        delete exportParams.search;

        $.post(ejpt_ajax.ajax_url, exportParams, function(response) {
            if (response.data && response.data.length > 0) {
                var csvData = [];
                var headers = [
                    "Employee Name", "Employee No.", "Job No.", "Phase", 
                    "Start Time", "End Time", "Duration", "Boxes Completed", "Items Completed",
                    "Time/Box", "Time/Item", "Boxes/Hr", "Items/Hr", "Status", "Notes"
                ];
                csvData.push(headers.join(','));

                response.data.forEach(function(row) {
                    var statusText = $($.parseHTML(row.status)).text().trim(); // Strip HTML from status for CSV
                    var notesText = row.notes ? row.notes.replace(/"/g, '""').replace(/\r\n|\n|\r/g, ' ') : '';

                    var csvRow = [
                        '"' + row.employee_name.replace(/"/g, '""') + '"',
                        '"' + row.employee_number.replace(/"/g, '""') + '"',
                        '"' + row.job_number.replace(/"/g, '""') + '"',
                        '"' + row.phase_name.replace(/"/g, '""') + '"',
                        '"' + row.start_time.replace(/"/g, '""') + '"',
                        '"' + (row.end_time !== 'N/A' ? row.end_time.replace(/"/g, '""') : 'N/A') + '"',
                        '"' + row.duration.replace(/"/g, '""') + '"',
                        row.boxes_completed,
                        row.items_completed,
                        '"' + (row.time_per_box !== 'N/A' ? row.time_per_box.replace(/"/g, '""') : 'N/A') + '"', 
                        '"' + (row.time_per_item !== 'N/A' ? row.time_per_item.replace(/"/g, '""') : 'N/A') + '"',
                        row.boxes_per_hour,
                        row.items_per_hour,
                        '"' + statusText + '"',
                        '"' + notesText + '"'
                    ];
                    csvData.push(csvRow.join(','));
                });

                var csvContent = csvData.join("\n");
                var universalBOM = "\uFEFF"; // Universal BOM for UTF-8 for Excel
                var encodedUri = encodeURI("data:text/csv;charset=utf-8," + universalBOM + csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "job_logs_export_" + new Date().toISOString().slice(0,10) + ".csv");
                document.body.appendChild(link); 
                link.click();
                document.body.removeChild(link);
            } else {
                showNotice('warning', '<?php echo esc_js(__("No data to export based on current filters or an error occurred.", "ejpt")); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Failed to fetch data for CSV export.", "ejpt")); ?>');
        });
    });

    // JS for Quick Phase Actions
    $('.ejpt-start-link-btn, .ejpt-stop-link-btn').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest('.ejpt-phase-action-row');
        var jobNumber = $row.find('.ejpt-job-number-input').val();
        var phaseId = $button.data('phase-id');
        var isAdminUrl = '<?php echo admin_url("admin.php"); ?>';
        var linkArea = $row.find('.ejpt-generated-link-area');

        if (!jobNumber) {
            linkArea.html('<span style="color:red;"><?php echo esc_js(__("Please enter a Job Number.", "ejpt")); ?></span>');
            return;
        }

        var actionPage = $button.hasClass('ejpt-start-link-btn') ? 'ejpt_start_job' : 'ejpt_stop_job';
        var url = isAdminUrl + '?page=' + actionPage + '&job_number=' + encodeURIComponent(jobNumber) + '&phase_id=' + encodeURIComponent(phaseId);
        
        // Display the link and make it clickable, or navigate directly
        // For this version, let's display the link so admin can copy it for QR codes etc.
        var linkText = $button.hasClass('ejpt-start-link-btn') ? '<?php echo esc_js(__("Go to Start Form", "ejpt")); ?>' : '<?php echo esc_js(__("Go to Stop Form", "ejpt")); ?>';
        linkArea.html('<a href="' + url + '" target="_blank">' + linkText + ' (' + jobNumber + ')</a> <button class="button button-small ejpt-copy-link-btn" data-link="' + url + '"><?php echo esc_js(__("Copy")); ?></button>');
        
        // Navigate directly option (can be enabled instead of showing link):
        // window.open(url, '_blank');
    });

    $('body').on('click', '.ejpt-copy-link-btn', function(){
        var linkToCopy = $(this).data('link');
        navigator.clipboard.writeText(linkToCopy).then(function() {
            showNotice('success', '<?php echo esc_js(__("Link copied to clipboard!", "ejpt")); ?>');
        }, function(err) {
            showNotice('error', '<?php echo esc_js(__("Could not copy link: ", "ejpt")); ?>' + err);
        });
    });

});
</script> 