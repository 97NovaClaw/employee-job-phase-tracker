jQuery(document).ready(function($) {
    // Debounce function to limit how often a function can run.
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    // Common function to display notices
    // Ensures only one notice is visible at a time and fades out.
    window.showNotice = debounce(function(type, message) {
        $('.ejpt-notice').remove(); // Remove any existing notices
        var noticeHtml = '<div class="notice notice-' + type + ' is-dismissible ejpt-notice"><p>' + message + '</p>' +
                         '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
        
        // Try to place notice after h1, or at the top of .wrap if h1 not specific enough
        if ($('.wrap h1').length) {
            $('.wrap h1').first().after(noticeHtml);
        } else {
            $('.wrap').prepend(noticeHtml);
        }

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.ejpt-notice').fadeOut('slow', function() { $(this).remove(); });
        }, 5000);

        // Allow manual dismiss
        $('.ejpt-notice .notice-dismiss').on('click', function(event) {
            event.preventDefault();
            $(this).closest('.ejpt-notice').remove();
        });
    }, 250); // Debounce for 250ms to prevent multiple rapid notices

    // AJAX form submission for Add Employee
    $('body').on('submit', '#ejpt-add-employee-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);

        var formData = $form.serialize();
        $.post(ejpt_ajax.ajax_url, formData + '&action=ejpt_add_employee', function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                $form[0].reset();
                if (typeof window.loadEmployeesTable === 'function') {
                    window.loadEmployeesTable();
                }
                $('#addEmployeeModal').hide();
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // AJAX form submission for Edit Employee
    $('body').on('submit', '#ejpt-edit-employee-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);
        
        var formData = $form.serialize();
        $.post(ejpt_ajax.ajax_url, formData + '&action=ejpt_update_employee', function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                if (typeof window.loadEmployeesTable === 'function') {
                    window.loadEmployeesTable();
                }
                $('#editEmployeeModal').hide();
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // AJAX form submission for Add Phase
    $('body').on('submit', '#ejpt-add-phase-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);

        var formData = $form.serialize();
        $.post(ejpt_ajax.ajax_url, formData + '&action=ejpt_add_phase', function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                $form[0].reset();
                if (typeof window.loadPhasesTable === 'function') {
                    window.loadPhasesTable();
                }
                $('#addPhaseModal').hide();
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // AJAX form submission for Edit Phase
    $('body').on('submit', '#ejpt-edit-phase-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        $submitButton.prop('disabled', true);

        var formData = $form.serialize();
        $.post(ejpt_ajax.ajax_url, formData + '&action=ejpt_update_phase', function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                if (typeof window.loadPhasesTable === 'function') {
                    window.loadPhasesTable(); 
                }
                $('#editPhaseModal').hide();
            } else {
                showNotice('error', response.data.message || 'An unknown error occurred.');
            }
        }).fail(function() {
            showNotice('error', 'Request failed. Please try again.');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // Modal Open/Close (ensure these are bound even if modals are added dynamically, though not the case here)
    $('body').on('click', '.ejpt-open-modal-button', function() {
        var targetModal = $(this).data('modal-id');
        $('#' + targetModal).show();
    });

    $('body').on('click', '.ejpt-close-button', function() {
        $(this).closest('.ejpt-modal').hide();
    });

    // Close modal if clicked outside of content
    $(window).on('click', function(event) {
        if ($(event.target).is('.ejpt-modal')) {
            $(event.target).hide();
        }
    });
    
    // Start Job Form AJAX Submission
    $('body').on('submit', '#ejpt-start-job-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#start-job-submit');
        $submitButton.prop('disabled', true).text('Starting...');
        
        var formData = $form.serialize();
        formData += '&action=ejpt_start_job_action';

        $.post(ejpt_ajax.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    // Redirect to dashboard
                    window.location.href = '<?php echo admin_url("admin.php?page=ejpt_dashboard"); ?>';
                } else {
                    showNotice('error', response.data.message || 'An unknown error occurred.');
                    $submitButton.prop('disabled', false).text('Start Job'); // Re-enable only on error
                }
            })
            .fail(function() {
                showNotice('error', 'Request failed. Please try again.');
                $submitButton.prop('disabled', false).text('Start Job');
            });
    });

    // Stop Job Form AJAX Submission
    $('body').on('submit', '#ejpt-stop-job-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('#stop-job-submit');
        $submitButton.prop('disabled', true).text('Stopping...');
        
        var formData = $form.serialize();
        formData += '&action=ejpt_stop_job_action';

        $.post(ejpt_ajax.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    // Redirect to dashboard
                    window.location.href = '<?php echo admin_url("admin.php?page=ejpt_dashboard"); ?>';
                } else {
                    showNotice('error', response.data.message || 'An unknown error occurred.');
                    $submitButton.prop('disabled', false).text('Stop Job & Save'); // Re-enable only on error
                }
            })
            .fail(function() {
                showNotice('error', 'Request failed. Please try again.');
                $submitButton.prop('disabled', false).text('Stop Job & Save');
            });
    });

    // Initialize Datepicker for any elements with class .ejpt-datepicker
    // This might be on dashboard page or other admin pages if added later
    if (typeof $.fn.datepicker === 'function') {
        $('.ejpt-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true
        });
    }

}); 