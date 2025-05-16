<?php
// /admin/views/start-job-form-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get pre-filled data from URL parameters
$job_number_get = isset( $_GET['job_number'] ) ? sanitize_text_field( $_GET['job_number'] ) : '';
$phase_id_get = isset( $_GET['phase_id'] ) ? intval( $_GET['phase_id'] ) : 0;

$phase_name_display = 'N/A';
$phase_valid = false;
if ( $phase_id_get > 0 ) {
    $phase = EJPT_DB::get_phase( $phase_id_get );
    if ( $phase && $phase->is_active) {
        $phase_name_display = esc_html( $phase->phase_name );
        $phase_valid = true;
    } else if ($phase && !$phase->is_active) {
        $phase_name_display = '<span style="color:orange;">' . esc_html($phase->phase_name) . ' (' . __('Inactive', 'ejpt') . ')</span>';
        // Allow starting an inactive phase if explicitly linked? For now, we allow, but can be restricted.
        $phase_valid = true; // Or set to false to prevent starting inactive phases via QR
    } else {
        $phase_name_display = '<span style="color:red;">' . __('Error: Invalid Phase ID', 'ejpt') . '</span>';
    }
}

$active_employees = ejpt_get_active_employees_for_select();
$current_time_display = ejpt_get_current_timestamp_display();
$form_disabled = empty( $job_number_get ) || !$phase_valid;

// --- START JOB FORM DEBUGGING ---
if (defined('WP_DEBUG') && WP_DEBUG === true) {
    ejpt_log(array(
        'GET_job_number' => isset($_GET['job_number']) ? $_GET['job_number'] : 'Not Set',
        'GET_phase_id' => isset($_GET['phase_id']) ? $_GET['phase_id'] : 'Not Set',
        'job_number_get' => $job_number_get,
        'phase_id_get' => $phase_id_get,
        'phase_object' => isset($phase) ? $phase : 'Not Fetched',
        'phase_valid' => $phase_valid,
        'form_disabled' => $form_disabled
    ), 'Start Job Form - Initial Params');
}
// --- END START JOB FORM DEBUGGING ---

?>
<div class="wrap ejpt-start-job-form-page">
    <h1><?php esc_html_e( 'Start Job Phase', 'ejpt' ); ?></h1>

    <?php if ( empty( $job_number_get ) || empty( $phase_id_get ) ): ?>
        <div class="notice notice-error ejpt-notice"><p>
            <?php esc_html_e( 'Error: Job Number and Phase ID must be provided in the URL and the Phase ID must be valid.', 'ejpt' ); ?>
            <br>
            <?php esc_html_e( 'Example QR Code URL:', 'ejpt' ); ?>
            <code><?php echo esc_url( admin_url('admin.php?page=ejpt_start_job&job_number=YOUR_JOB_ID&phase_id=YOUR_PHASE_ID') ); ?></code>
        </p></div>
        <?php 
        // Do not return; allow form to show but be disabled if phase_id was bad but job_number was okay.
        // The $form_disabled variable will handle disabling submit.
        if (empty( $job_number_get ) || empty( $phase_id_get )) return; // Hard return if core params missing
        ?>
    <?php elseif (!$phase_valid && $phase_id_get > 0): // phase_id was given but invalid/not found ?>
         <div class="notice notice-error ejpt-notice"><p>
            <?php esc_html_e( 'Error: The specified Phase ID is invalid or the phase could not be found.', 'ejpt' ); ?>
        </p></div>
    <?php endif; ?>


    <form id="ejpt-start-job-form" method="post">
        <?php wp_nonce_field( 'ejpt_start_job_nonce', 'ejpt_start_job_nonce' ); ?>
        <input type="hidden" name="job_number" value="<?php echo esc_attr( $job_number_get ); ?>" />
        <input type="hidden" name="phase_id" value="<?php echo esc_attr( $phase_id_get ); ?>" />

        <table class="form-table ejpt-form-table">
            <tr valign="top">
                <th scope="row"><label for="employee_number_start"><?php esc_html_e( 'Employee Number', 'ejpt' ); ?></label></th>
                <td>
                    <input type="text" id="employee_number_start" name="employee_number" required <?php disabled($form_disabled); ?> placeholder="<?php esc_attr_e('Enter your Employee No.', 'ejpt'); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Job Number', 'ejpt' ); ?></th>
                <td><span class="ejpt-readonly-field"><?php echo esc_html( $job_number_get ); ?></span></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Phase', 'ejpt' ); ?></th>
                <td><span class="ejpt-readonly-field"><?php echo $phase_name_display; // Already escaped or marked safe ?></span></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="notes_start"><?php esc_html_e( 'Notes (Optional)', 'ejpt' ); ?></label></th>
                <td><textarea id="notes_start" name="notes" rows="3" class="widefat" <?php disabled($form_disabled); ?>></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e( 'Current Timestamp', 'ejpt' ); ?></th>
                <td><span class="ejpt-readonly-field"><?php echo esc_html( $current_time_display ); ?></span> (Server time will be used on submit)</td>
            </tr>
        </table>
        <div class="form-buttons">
            <?php submit_button( __( 'Start Job', 'ejpt' ), 'primary', 'start_job_submit', false, array('id' => 'start-job-submit', 'disabled' => $form_disabled) ); ?>
        </div>
    </form>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Common function to display notices (if needed on this page specifically)
    if (typeof window.showNotice !== 'function') {
        window.showNotice = function(type, message) {
            $('.ejpt-notice').remove();
            var noticeHtml = '<div class="notice notice-' + type + ' is-dismissible ejpt-notice"><p>' + message + '</p>' +
                             '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            $('div.wrap > h1').first().after(noticeHtml);
            setTimeout(function() {
                $('.ejpt-notice').fadeOut('slow', function() { $(this).remove(); });
            }, 5000);
            $('.ejpt-notice .notice-dismiss').on('click', function(event) {
                event.preventDefault();
                $(this).closest('.ejpt-notice').remove();
            });
        };
    }

    const startJobButton = $('#start-job-submit');
    const employeeNumberInput = $('#employee_number_start');
    const phpFormDisabled = <?php echo json_encode($form_disabled); ?>;

    function updateButtonState() {
        if (phpFormDisabled) {
            startJobButton.prop('disabled', true);
            console.log('Start Job button disabled by PHP.');
        } else if (employeeNumberInput.val().trim() === '') {
            startJobButton.prop('disabled', true);
            console.log('Start Job button disabled (no employee number entered).');
        } else {
            startJobButton.prop('disabled', false);
            console.log('Start Job button enabled.');
        }
    }

    // Initial state check
    updateButtonState();

    // Update button state when employee number input changes
    employeeNumberInput.on('input', updateButtonState);

    // AJAX submission is in admin-scripts.js
});
</script> 