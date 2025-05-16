<?php
// /admin/views/dashboard-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
// Data for this page ($employees, $phases) is prepared in EJPT_Dashboard::display_dashboard_page()
// and passed via global variables.
global $employees, $phases; 
?>
<div class="wrap oo-dashboard-page">
    <h1><?php esc_html_e( 'Job Logs Dashboard', 'operations-organizer' ); ?></h1>

    <div id="oo-dashboard-filters">
        <div class="filter-item">
            <label for="filter_date_from"><?php esc_html_e('Date From:', 'operations-organizer');?></label>
            <input type="text" id="filter_date_from" name="filter_date_from" class="ejpt-datepicker" placeholder="YYYY-MM-DD">
        </div>
        <div class="filter-item">
            <label for="filter_date_to"><?php esc_html_e('Date To:', 'operations-organizer');?></label>
            <input type="text" id="filter_date_to" name="filter_date_to" class="ejpt-datepicker" placeholder="YYYY-MM-DD">
        </div>
        <div class="filter-item">
            <label for="filter_employee_id"><?php esc_html_e('Employee:', 'operations-organizer');?></label>
            <select id="filter_employee_id" name="filter_employee_id">
                <option value=""><?php esc_html_e('All Employees', 'operations-organizer');?></option>
                <?php if (!empty($employees)): foreach ($employees as $employee): ?>
                    <option value="<?php echo esc_attr($employee->employee_id); ?>">
                        <?php echo esc_html($employee->first_name . ' ' . $employee->last_name . ' (' . $employee->employee_number . ')'); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_job_number"><?php esc_html_e('Job Number:', 'operations-organizer');?></label>
            <input type="text" id="filter_job_number" name="filter_job_number" placeholder="<?php esc_attr_e('Enter Job No.', 'operations-organizer'); ?>">
        </div>
        <div class="filter-item">
            <label for="filter_phase_id"><?php esc_html_e('Phase:', 'operations-organizer');?></label>
            <select id="filter_phase_id" name="filter_phase_id">
                <option value=""><?php esc_html_e('All Phases', 'operations-organizer');?></option>
                 <?php if (!empty($phases)): foreach ($phases as $phase): ?>
                    <option value="<?php echo esc_attr($phase->phase_id); ?>">
                        <?php echo esc_html($phase->phase_name); ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_status"><?php esc_html_e('Status:', 'operations-organizer');?></label>
            <select id="filter_status" name="filter_status">
                <option value=""><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
                <option value="started"><?php esc_html_e('Running', 'operations-organizer');?></option>
                <option value="completed"><?php esc_html_e('Completed', 'operations-organizer');?></option>
            </select>
        </div>
        <div class="filter-item">
            <label for="filter_stream_type_id"><?php esc_html_e('Stream Type:', 'operations-organizer');?></label>
            <select id="filter_stream_type_id" name="filter_stream_type_id">
                <option value=""><?php esc_html_e('All Stream Types', 'operations-organizer');?></option>
                <?php foreach ($GLOBALS['stream_types'] as $st): ?>
                    <option value="<?php echo esc_attr($st->stream_type_id); ?>"><?php echo esc_html($st->stream_type_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <button id="apply_filters_button" class="button button-primary"><?php esc_html_e('Apply Filters', 'operations-organizer');?></button>
            <button id="clear_filters_button" class="button"><?php esc_html_e('Clear Filters', 'operations-organizer');?></button>
        </div>
    </div>
    
    <div id="oo-export-options" style="margin-bottom: 20px;">
        <button id="export_csv_button" class="button"><?php esc_html_e('Export to CSV', 'operations-organizer');?></button>
    </div>

    <div id="oo-quick-actions" style="margin-bottom: 30px; padding: 15px; background-color: #fff; border: 1px solid #ccd0d4;">
        <h2><?php esc_html_e('Quick Phase Actions', 'operations-organizer'); ?></h2>
        <p><?php esc_html_e('Enter a Job Number and select a phase to start or stop.', 'operations-organizer'); ?></p>
        <?php 
        // --- DASHBOARD VIEW DEBUGGING ---
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            ejpt_log('Dashboard View (DEBUG): $GLOBALS[\'phases\'] content just before conditional check:', 'dashboard-view');
            ejpt_log(isset($GLOBALS['phases']) ? $GLOBALS['phases'] : 'GLOBALS[\'phases\'] is not set', 'dashboard-view-phases-var (DEBUG)');
            ejpt_log('Dashboard View (DEBUG): empty($GLOBALS[\'phases\']) result: ' . (empty($GLOBALS['phases']) ? 'true (is empty)' : 'false (not empty)'), 'dashboard-view');
            if (isset($GLOBALS['phases']) && is_array($GLOBALS['phases'])) {
                 ejpt_log('Dashboard View (DEBUG): count($GLOBALS[\'phases\']) result: ' . count($GLOBALS['phases']), 'dashboard-view');
            } else {
                 ejpt_log('Dashboard View (DEBUG): $GLOBALS[\'phases\'] is not an array or not set, count not applicable directly.', 'dashboard-view');
            }
        }
        // --- END DASHBOARD VIEW DEBUGGING ---
        ?>
        <?php 
        // Use $GLOBALS['phases'] directly for the condition
        $phases_data_for_view = isset($GLOBALS['phases']) ? $GLOBALS['phases'] : array(); 
        $phases_is_not_empty = !empty($phases_data_for_view);
        $phases_is_an_array = is_array($phases_data_for_view);
        $phases_has_items = ($phases_is_an_array && count($phases_data_for_view) > 0);

        if ( $phases_is_not_empty && $phases_has_items ) : 
        ?>
            <table class="form-table">
                <?php foreach ($phases_data_for_view as $phase) : // Use the local $phases_data_for_view here ?>
                    <tr valign="top" class="oo-phase-action-row">
                        <th scope="row" style="width: 200px;">
                           <?php echo esc_html($phase->phase_name); ?>
                           <small style="display:block; color:#777;">(<?php 
                           // Find stream type name for this phase
                           $stream_name = 'Unknown Stream';
                           foreach($GLOBALS['stream_types'] as $st) {
                               if($st->stream_type_id == $phase->stream_type_id) {
                                   $stream_name = $st->stream_type_name;
                                   break;
                               }
                           }
                           echo esc_html($stream_name);
                           ?>)</small>
                        </th>
                        <td>
                            <input type="text" class="oo-job-number-input" placeholder="<?php esc_attr_e('Enter Job Number', 'operations-organizer'); ?>" style="width: 200px; margin-right: 10px;">
                            <button class="button button-primary oo-start-link-btn" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Start', 'operations-organizer'); ?></button>
                            <button class="button oo-stop-link-btn" data-phase-id="<?php echo esc_attr($phase->phase_id); ?>"><?php esc_html_e('Stop', 'operations-organizer'); ?></button>
                            <span class="oo-generated-link-area" style="margin-left: 15px;"></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No active phases available. Add stream types and then phases for them.', 'operations-organizer'); ?></p>
        <?php endif; ?>
    </div>

    <table id="oo-dashboard-table" class="display wp-list-table widefat fixed striped" style="width:100%">
        <thead>
            <tr>
                <th><?php esc_html_e('Employee Name', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Job No.', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Stream Type', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Phase', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Start Time', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('End Time', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Duration', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Boxes', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Items', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Time/Box', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Time/Item', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Boxes/Hr', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Items/Hr', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Notes', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('KPI Data', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Data will be loaded by DataTables via AJAX -->
        </tbody>
         <tfoot>
            <tr>
                <th><?php esc_html_e('Employee Name', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Job No.', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Stream Type', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Phase', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Start Time', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('End Time', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Duration', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Boxes', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Items', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Time/Box', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Time/Item', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Boxes/Hr', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Items/Hr', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Status', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Notes', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('KPI Data', 'operations-organizer'); ?></th>
                <th><?php esc_html_e('Actions', 'operations-organizer'); ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Datepicker init is in admin-scripts.js

    var dashboardTable = $('#oo-dashboard-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: oo_data.ajax_url,
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
                d.filter_stream_type_id = $('#filter_stream_type_id').val();
            }
        },
        columns: [
            { data: 'employee_name' },
            { data: 'job_number' },
            { data: 'stream_type_name' },
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
            },
            { data: 'kpi_data' },
            {
                data: null, // Using null for custom content
                orderable: false,
                searchable: false,
                className: 'oo-actions-column',
                render: function(data, type, row) {
                    let buttons = '<button class="button button-secondary oo-edit-log-button" data-log-id="' + row.log_id + '"><?php echo esc_js(__("Edit", "operations-organizer")); ?></button>';
                    buttons += ' <button class="button button-link-delete oo-delete-log-button" data-log-id="' + row.log_id + '" style="color:#b32d2e;margin-left:5px;"><?php echo esc_js(__("Delete", "operations-organizer")); ?></button>';
                    return buttons;
                }
            }
        ],
        order: [[4, 'desc']], // Default order by start_time descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        language: {
            search: "<?php esc_attr_e('Search table:', 'operations-organizer'); ?>",
            lengthMenu: "<?php esc_attr_e('Show _MENU_ entries', 'operations-organizer'); ?>",
            info: "<?php esc_attr_e('Showing _START_ to _END_ of _TOTAL_ entries', 'operations-organizer'); ?>",
            paginate: {
                first: "<?php esc_attr_e('First', 'operations-organizer'); ?>",
                last: "<?php esc_attr_e('Last', 'operations-organizer'); ?>",
                next: "<?php esc_attr_e('Next', 'operations-organizer'); ?>",
                previous: "<?php esc_attr_e('Previous', 'operations-organizer'); ?>"
            }
        }
    });

    // Apply filters button
    $('#apply_filters_button').on('click', function() {
        dashboardTable.ajax.reload(); 
    });

    // Clear filters button
    $('#clear_filters_button').on('click', function() {
        $('#oo-dashboard-filters input[type="text"]').val('');
        $('#oo-dashboard-filters input[type="date"]').val('');
        $('#oo-dashboard-filters select').val('');
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
        exportParams.filter_stream_type_id = $('#filter_stream_type_id').val();

        // Remove DataTables specific parameters not needed for our AJAX handler or that might conflict
        delete exportParams.draw;
        delete exportParams.columns;
        delete exportParams.order;
        delete exportParams.start;
        delete exportParams.search;

        $.post(oo_data.ajax_url, exportParams, function(response) {
            if (response.data && response.data.length > 0) {
                var csvData = [];
                var headers = [
                    "Employee Name", "Job No.", "Stream Type", "Phase", 
                    "Start Time", "End Time", "Duration", "Boxes Completed", "Items Completed",
                    "Time/Box", "Time/Item", "Boxes/Hr", "Items/Hr", "Status", "Notes", "KPI Data"
                ];
                csvData.push(headers.join(','));

                response.data.forEach(function(row) {
                    var statusText = $($.parseHTML(row.status)).text().trim(); // Strip HTML from status for CSV
                    var notesText = row.notes ? row.notes.replace(/"/g, '""').replace(/\r\n|\n|\r/g, ' ') : '';
                    var kpiDataText = (typeof row.kpi_data === 'string') ? row.kpi_data : JSON.stringify(row.kpi_data || {});

                    var csvRow = [
                        '"' + row.employee_name.replace(/"/g, '""') + '"',
                        '"' + row.job_number.replace(/"/g, '""') + '"',
                        '"' + row.stream_type_name.replace(/"/g, '""') + '"',
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
                        '"' + notesText + '"',
                        '"' + kpiDataText + '"'
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
                showNotice('warning', '<?php echo esc_js(__("No data to export based on current filters or an error occurred.", "operations-organizer")); ?>');
            }
        }).fail(function() {
            showNotice('error', '<?php echo esc_js(__("Failed to fetch data for CSV export.", "operations-organizer")); ?>');
        });
    });

    // JS for Quick Phase Actions
    $('.oo-start-link-btn, .oo-stop-link-btn').on('click', function(e) {
        e.preventDefault();
        console.log('Quick action button clicked.');
        var $button = $(this);
        var $row = $button.closest('.oo-phase-action-row');
        var jobNumber = $row.find('.oo-job-number-input').val();
        var phaseId = $button.data('phase-id');
        var isAdminUrl = '<?php echo admin_url("admin.php"); ?>';
        // var linkArea = $row.find('.oo-generated-link-area'); // No longer needed for displaying link

        console.log('Job Number:', jobNumber, 'Phase ID:', phaseId, 'Row found:', $row.length);

        if (!jobNumber) {
            // linkArea.html('<span style="color:red;"><?php echo esc_js(__("Please enter a Job Number.", "operations-organizer")); ?></span>');
            showNotice('error', '<?php echo esc_js(__("Please enter a Job Number first.", "operations-organizer")); ?>');
            console.log('Job number is missing.');
            return;
        }

        var actionPage = $button.hasClass('oo-start-link-btn') ? 'ejpt_start_job' : 'ejpt_stop_job';
        var url = isAdminUrl + '?page=' + actionPage + '&job_number=' + encodeURIComponent(jobNumber) + '&phase_id=' + encodeURIComponent(phaseId);
        console.log('Generated URL:', url);
        
        // Navigate directly 
        window.location.href = url; // Changed from window.open to navigate in the same tab
        
        // Remove previously displayed link and copy button logic
        // var linkText = $button.hasClass('oo-start-link-btn') ? '<?php echo esc_js(__("Go to Start Form", "operations-organizer")); ?>' : '<?php echo esc_js(__("Go to Stop Form", "operations-organizer")); ?>';
        // linkArea.html('<a href="' + url + '" target="_blank">' + linkText + ' (' + jobNumber + ')</a> <button class="button button-small oo-copy-link-btn" data-link="' + url + '"><?php echo esc_js(__("Copy")); ?></button>');
    });

    // The .oo-copy-link-btn logic can be removed if not used elsewhere, or kept if useful for other generated links.
    // For now, I'll comment it out as it's not directly used by the modified quick actions.
    /*
    $('body').on('click', '.oo-copy-link-btn', function(){ 
        console.log('Copy button clicked.');
        var linkToCopy = $(this).data('link');
        console.log('Link to copy:', linkToCopy);
        navigator.clipboard.writeText(linkToCopy).then(function() {
            showNotice('success', '<?php echo esc_js(__("Link copied to clipboard!", "operations-organizer")); ?>');
        }, function(err) {
            showNotice('error', '<?php echo esc_js(__("Could not copy link: ", "operations-organizer")); ?>' + err);
        });
    });
    */

    // JS for Edit Job Log Modal
    var editLogModal = $('#ooEditLogModal');
    var editLogForm = $('#oo-edit-log-form');

    // Handle click on "Edit" button in DataTable
    $('#oo-dashboard-table tbody').on('click', '.oo-edit-log-button', function () {
        console.log('Dashboard JS: Edit button event triggered.'); // DEBUG Line 1
        var logId = $(this).data('log-id');
        // ejpt_log('Edit button clicked for log ID: ' + logId, 'Dashboard JS'); // Server-side log via separate mechanism if needed
        console.log('Dashboard JS: Edit button clicked for log ID:', logId); // DEBUG Line 2

        if (!logId) {
            console.error('Dashboard JS: Log ID is missing from button data attribute.');
            showNotice('error', '<?php echo esc_js(__("Error: Log ID is missing.", "operations-organizer")); ?>');
            return;
        }

        // Disable button to prevent multiple clicks
        $(this).prop('disabled', true).text('<?php echo esc_js(__("Loading...", "operations-organizer")); ?>');
        var $clickedButton = $(this);

        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_get_job_log_details',
                nonce: oo_data.nonce_edit_log,
                log_id: logId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Dashboard JS: AJAX success for get_job_log_details. Response:', response); // DEBUG Line 3
                if (response.success) {
                    console.log('Dashboard JS: Successfully fetched log details (data to populate modal):', response.data); // JS-side log
                    var log = response.data;
                    editLogForm.find('#edit_log_id').val(log.log_id);
                    editLogForm.find('#edit_log_employee_id').val(log.employee_id);
                    editLogForm.find('#edit_log_job_number').val(log.job_number);
                    editLogForm.find('#edit_log_phase_id').val(log.phase_id);
                    editLogForm.find('#edit_log_start_time').val(log.start_time); 
                    editLogForm.find('#edit_log_end_time').val(log.end_time);     
                    editLogForm.find('#edit_log_boxes_completed').val(log.boxes_completed !== null ? log.boxes_completed : 0);
                    editLogForm.find('#edit_log_items_completed').val(log.items_completed !== null ? log.items_completed : 0);
                    editLogForm.find('#edit_log_status').val(log.status);
                    editLogForm.find('#edit_log_notes').val(log.notes);
                    editLogForm.find('#edit_log_kpi_data').val( (typeof log.kpi_data === 'string') ? log.kpi_data : JSON.stringify(log.kpi_data || {}) );
                    editLogModal.show();
                } else {
                    showNotice('error', response.data.message || '<?php echo esc_js(__("Could not fetch log details.", "operations-organizer")); ?>');
                }
                $clickedButton.prop('disabled', false).text('<?php echo esc_js(__("Edit", "operations-organizer")); ?>'); // Re-enable button
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Dashboard JS: AJAX error for get_job_log_details:', textStatus, errorThrown, jqXHR.responseText); // DEBUG Line 4
                showNotice('error', '<?php echo esc_js(__("AJAX request failed: ", "operations-organizer")); ?>' + textStatus + ' - ' + errorThrown);
                ejpt_log('AJAX error fetching log details: ' + textStatus + ' - ' + errorThrown + ' | Response: ' + jqXHR.responseText, 'Dashboard JS');
                $clickedButton.prop('disabled', false).text('<?php echo esc_js(__("Edit", "operations-organizer")); ?>'); // Re-enable button
            }
        });
    });

    // Handle Edit Job Log form submission
    editLogForm.on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true).val('<?php echo esc_js(__("Saving...", "operations-organizer")); ?>');
        
        var formData = $form.serialize(); 
        formData += '&action=oo_update_job_log';

        console.log('Dashboard JS: Submitting Edit Log Form Data:', formData); // DEBUG: Changed from ejpt_log

        $.post(oo_data.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    editLogModal.hide();
                    if (typeof dashboardTable !== 'undefined') {
                        dashboardTable.ajax.reload(null, false); // Reload DataTable, false = don't reset pagination
                    }
                } else {
                    showNotice('error', response.data.message || '<?php echo esc_js(__("Error updating job log.", "operations-organizer")); ?>');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                showNotice('error', '<?php echo esc_js(__("AJAX request failed: ", "operations-organizer")); ?>' + textStatus + ' - ' + errorThrown);
            })
            .always(function() {
                $submitButton.prop('disabled', false).val('<?php echo esc_js(__("Save Log Changes", "operations-organizer")); ?>');
            });
    });

    // Handle click on "Delete" button in DataTable
    $('#oo-dashboard-table tbody').on('click', '.oo-delete-log-button', function () {
        var logId = $(this).data('log-id');
        console.log('Dashboard JS: Delete button clicked for log ID:', logId);

        if (!logId) {
            showNotice('error', '<?php echo esc_js(__("Error: Log ID is missing for delete.", "operations-organizer")); ?>');
            return;
        }

        if (!confirm('<?php echo esc_js(__("Are you sure you want to permanently delete this job log entry? This action cannot be undone.", "operations-organizer")); ?>')) {
            return;
        }

        // Disable button to prevent multiple clicks
        $(this).prop('disabled', true).text('<?php echo esc_js(__("Deleting...", "operations-organizer")); ?>');
        var $clickedButton = $(this);

        $.ajax({
            url: oo_data.ajax_url,
            type: 'POST',
            data: {
                action: 'oo_delete_job_log',
                nonce: oo_data.nonce_delete_log,
                log_id: logId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    ejpt_log('Successfully deleted log ID: ' + logId, 'Dashboard JS Delete');
                    if (typeof dashboardTable !== 'undefined') {
                        dashboardTable.ajax.reload(null, false); // Reload DataTable, false = don't reset pagination
                    }
                } else {
                    showNotice('error', response.data.message || '<?php echo esc_js(__("Could not delete log entry.", "operations-organizer")); ?>');
                    ejpt_log('Error deleting log: ', response.data);
                    $clickedButton.prop('disabled', false).text('<?php echo esc_js(__("Delete", "operations-organizer")); ?>'); // Re-enable button on error
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showNotice('error', '<?php echo esc_js(__("AJAX request failed: ", "operations-organizer")); ?>' + textStatus + ' - ' + errorThrown);
                ejpt_log('AJAX error deleting log: ' + textStatus + ' - ' + errorThrown + ' | Response: ' + jqXHR.responseText, 'Dashboard JS Delete');
                $clickedButton.prop('disabled', false).text('<?php echo esc_js(__("Delete", "operations-organizer")); ?>'); // Re-enable button on error
            }
        });
    });

});
</script>

<!-- Edit Job Log Modal -->
<div id="ooEditLogModal" class="oo-modal" style="display:none;">
    <div class="oo-modal-content">
        <span class="oo-close-button">&times;</span>
        <h2><?php esc_html_e( 'Edit Job Log Entry', 'operations-organizer' ); ?></h2>
        <form id="oo-edit-log-form">
            <?php wp_nonce_field( 'oo_edit_log_nonce', 'oo_edit_log_nonce_field' ); ?>
            <input type="hidden" id="edit_log_id" name="edit_log_id" value="" />
            <table class="form-table oo-form-table">
                <tr valign="top">
                    <th scope="row"><label for="edit_log_employee_id"><?php esc_html_e( 'Employee', 'operations-organizer' ); ?></label></th>
                    <td>
                        <select id="edit_log_employee_id" name="edit_log_employee_id" required>
                            <option value=""><?php esc_html_e('-- Select Employee --', 'operations-organizer'); ?></option>
                            <?php 
                            // Use the already available $GLOBALS['employees'] if populated, otherwise fetch them.
                            // This assumes $GLOBALS['employees'] has active employees for the dashboard filters.
                            $modal_employees = isset($GLOBALS['employees']) && is_array($GLOBALS['employees']) ? $GLOBALS['employees'] : ejpt_get_active_employees_for_select();
                            if (!empty($modal_employees)) {
                                foreach ( $modal_employees as $emp_obj_or_arr ) {
                                    // Handle if $emp_obj_or_arr is object from DB or array from ejpt_get_active_employees_for_select
                                    $emp_id = is_object($emp_obj_or_arr) ? $emp_obj_or_arr->employee_id : $emp_obj_or_arr['id'];
                                    $emp_name = is_object($emp_obj_or_arr) ? esc_html( $emp_obj_or_arr->first_name . ' ' . $emp_obj_or_arr->last_name . ' (' . $emp_obj_or_arr->employee_number . ')' ) : $emp_obj_or_arr['name'];
                                    echo '<option value="' . esc_attr( $emp_id ) . '">' . $emp_name . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_job_number"><?php esc_html_e( 'Job Number', 'operations-organizer' ); ?></label></th>
                    <td><input type="text" id="edit_log_job_number" name="edit_log_job_number" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_phase_id"><?php esc_html_e( 'Phase', 'operations-organizer' ); ?></label></th>
                    <td>
                        <select id="edit_log_phase_id" name="edit_log_phase_id" required>
                            <option value=""><?php esc_html_e('-- Select Phase --', 'operations-organizer'); ?></option>
                            <?php 
                            $modal_phases = isset($GLOBALS['phases']) && is_array($GLOBALS['phases']) ? $GLOBALS['phases'] : ejpt_get_active_phases_for_select();
                            if (!empty($modal_phases)) {
                                foreach ( $modal_phases as $phase_obj_or_arr ) {
                                    $phase_item_id = is_object($phase_obj_or_arr) ? $phase_obj_or_arr->phase_id : $phase_obj_or_arr['id'];
                                    $phase_item_name = is_object($phase_obj_or_arr) ? esc_html($phase_obj_or_arr->phase_name) : $phase_obj_or_arr['name'];
                                    echo '<option value="' . esc_attr( $phase_item_id ) . '">' . $phase_item_name . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_start_time"><?php esc_html_e( 'Start Time', 'operations-organizer' ); ?></label></th>
                    <td><input type="datetime-local" id="edit_log_start_time" name="edit_log_start_time" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_end_time"><?php esc_html_e( 'End Time', 'operations-organizer' ); ?></label></th>
                    <td><input type="datetime-local" id="edit_log_end_time" name="edit_log_end_time" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_boxes_completed"><?php esc_html_e( 'Boxes Completed', 'operations-organizer' ); ?></label></th>
                    <td><input type="number" id="edit_log_boxes_completed" name="edit_log_boxes_completed" min="0" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_items_completed"><?php esc_html_e( 'Items Completed', 'operations-organizer' ); ?></label></th>
                    <td><input type="number" id="edit_log_items_completed" name="edit_log_items_completed" min="0" /></td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><label for="edit_log_status"><?php esc_html_e( 'Status', 'operations-organizer' ); ?></label></th>
                    <td>
                        <select id="edit_log_status" name="edit_log_status">
                            <option value="started"><?php esc_html_e('Started', 'operations-organizer'); ?></option>
                            <option value="completed"><?php esc_html_e('Completed', 'operations-organizer'); ?></option>
                            <!-- Add other statuses if implemented, e.g., Paused -->
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_notes"><?php esc_html_e( 'Notes', 'operations-organizer' ); ?></label></th>
                    <td><textarea id="edit_log_notes" name="edit_log_notes" rows="3"></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="edit_log_kpi_data"><?php esc_html_e( 'KPI Data (JSON)', 'operations-organizer' ); ?></label></th>
                    <td><textarea id="edit_log_kpi_data" name="edit_log_kpi_data" rows="4" class="widefat"></textarea><p class="description"><?php esc_html_e('Enter additional KPIs as a valid JSON string. E.g. {"bags":5, "weight_kg":22.5}','operations-organizer');?></p></td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Log Changes', 'operations-organizer' ), 'primary', 'submit_edit_log' ); ?>
        </form>
    </div>
</div> 