<?php
// /admin/views/phase-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $phases, $total_phases, $current_page, $per_page, $search_term, $active_filter;

?>
<div class="wrap ejpt-phase-management-page">
    <h1><?php esc_html_e( 'Job Phase Management', 'ejpt' ); ?></h1>

    <button id="openAddPhaseModalBtn" class="page-title-action ejpt-open-modal-button" data-modal-id="addPhaseModal">
        <?php esc_html_e( 'Add New Phase', 'ejpt' ); ?>
    </button>

    <form method="get" class="ejpt-filters-form">
        <input type="hidden" name="page" value="ejpt_phases" />
        <div class="wp-filter">
            <div class="filter-items">
                <label for="status_filter" class="screen-reader-text"><?php esc_html_e('Filter by status', 'ejpt');?></label>
                <select name="status_filter" id="status_filter">
                    <option value="all" <?php selected($active_filter, 'all'); ?>><?php esc_html_e('All Statuses', 'ejpt');?></option>
                    <option value="active" <?php selected($active_filter, 'active'); ?>><?php esc_html_e('Active', 'ejpt');?></option>
                    <option value="inactive" <?php selected($active_filter, 'inactive'); ?>><?php esc_html_e('Inactive', 'ejpt');?></option>
                </select>
                <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e('Filter', 'ejpt');?>">
            </div>
            <p class="search-box">
                <label class="screen-reader-text" for="phase-search-input"><?php esc_html_e( 'Search Phases:', 'ejpt' ); ?></label>
                <input type="search" id="phase-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Name/Description', 'ejpt' ); ?>" />
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Phases', 'ejpt' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <div id="ejpt-phase-list-table-wrapper">
        <table class="wp-list-table widefat fixed striped table-view-list phases">
            <thead>
                <tr>
                    <?php 
                    $columns = [
                        'phase_name' => __('Phase Name', 'ejpt'),
                        'phase_description' => __('Description', 'ejpt'),
                        'status' => __('Status', 'ejpt'),
                        'actions' => __('Actions', 'ejpt'),
                    ];
                    $current_orderby = isset($GLOBALS['orderby']) ? $GLOBALS['orderby'] : 'phase_name';
                    $current_order = isset($GLOBALS['order']) ? strtolower($GLOBALS['order']) : 'asc';

                    foreach($columns as $slug => $title) {
                        $class = "manage-column column-$slug";
                        $sort_link = '';
                        if ($slug === 'phase_name') { // Sortable column
                            $order = ($current_orderby == $slug && $current_order == 'asc') ? 'desc' : 'asc';
                            $class .= $current_orderby == $slug ? " sorted $current_order" : " sortable $order";
                            $sort_link_url = add_query_arg(['orderby' => $slug, 'order' => $order]);
                            $sort_link = "<a href=\"".esc_url($sort_link_url)."\"><span>$title</span><span class=\"sorting-indicator\"></span></a>";
                        } else {
                            $sort_link = $title;
                        }
                        echo "<th scope=\"col\" id=\"$slug\" class=\"$class\">$sort_link</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( ! empty( $phases ) ) : ?>
                    <?php foreach ( $phases as $phase ) : ?>
                        <tr id="phase-<?php echo $phase->phase_id; ?>" class="<?php echo $phase->is_active ? 'active' : 'inactive'; ?>">
                            <td class="phase_name column-phase_name" data-colname="<?php esc_attr_e('Phase Name', 'ejpt'); ?>">
                                <?php echo esc_html( $phase->phase_name ); ?>
                            </td>
                            <td class="phase_description column-phase_description" data-colname="<?php esc_attr_e('Description', 'ejpt'); ?>">
                                <?php echo esc_html( $phase->phase_description ); ?>
                            </td>
                            <td class="status column-status" data-colname="<?php esc_attr_e('Status', 'ejpt'); ?>">
                                <?php if ( $phase->is_active ) : ?>
                                    <span style="color: green;"><?php esc_html_e( 'Active', 'ejpt' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;"><?php esc_html_e( 'Inactive', 'ejpt' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions column-actions" data-colname="<?php esc_attr_e('Actions', 'ejpt'); ?>">
                                <button class="button-secondary ejpt-edit-phase-button" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>"><?php esc_html_e('Edit', 'ejpt'); ?></button>
                                <?php if ( $phase->is_active ) : ?>
                                    <button class="button-secondary ejpt-toggle-status-phase-button ejpt-deactivate" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-new-status="0"><?php esc_html_e('Deactivate', 'ejpt'); ?></button>
                                <?php else : ?>
                                    <button class="button-secondary ejpt-toggle-status-phase-button ejpt-activate" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-new-status="1"><?php esc_html_e('Activate', 'ejpt'); ?></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count($columns); ?>"><?php esc_html_e( 'No phases found.', 'ejpt' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php 
                    foreach($columns as $slug => $title) {
                        echo "<th scope=\"col\" class=\"manage-column column-$slug\">$title</th>";
                    }
                    ?>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Pagination -->
    <?php
    if ( $total_phases > $per_page ) {
        $base_url = remove_query_arg(array('paged', 'filter_action'), wp_unslash($_SERVER['REQUEST_URI']));
        $page_links = paginate_links( array(
            'base' => $base_url . '%_%',
            'format' => '&paged=%#%', 
            'prev_text' => __( '&laquo; Previous' ),
            'next_text' => __( 'Next &raquo;' ),
            'total' => ceil( $total_phases / $per_page ),
            'current' => $current_page,
            'add_args' => array_map('urlencode', array_filter(compact('s', 'status_filter', 'orderby', 'order')))
        ) );

        if ( $page_links ) {
            echo "<div class=\"tablenav\"><div class=\"tablenav-pages\">$page_links</div></div>";
        }
    }
    ?>

    <!-- Add Phase Modal -->
    <div id="addPhaseModal" class="ejpt-modal" style="display:none;">
        <div class="ejpt-modal-content">
            <span class="ejpt-close-button">&times;</span>
            <h2><?php esc_html_e( 'Add New Phase', 'ejpt' ); ?></h2>
            <form id="ejpt-add-phase-form">
                <?php wp_nonce_field( 'ejpt_add_phase_nonce', 'ejpt_add_phase_nonce' ); ?>
                <table class="form-table ejpt-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="phase_name_add"><?php esc_html_e( 'Phase Name', 'ejpt' ); ?></label></th>
                        <td><input type="text" id="phase_name_add" name="phase_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="phase_description_add"><?php esc_html_e( 'Description', 'ejpt' ); ?></label></th>
                        <td><textarea id="phase_description_add" name="phase_description"></textarea></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Add Phase', 'ejpt' ), 'primary', 'submit_add_phase' ); ?>
            </form>
        </div>
    </div>

    <!-- Edit Phase Modal -->
    <div id="editPhaseModal" class="ejpt-modal" style="display:none;">
        <div class="ejpt-modal-content">
            <span class="ejpt-close-button">&times;</span>
            <h2><?php esc_html_e( 'Edit Phase', 'ejpt' ); ?></h2>
            <form id="ejpt-edit-phase-form">
                <?php wp_nonce_field( 'ejpt_edit_phase_nonce', 'ejpt_edit_phase_nonce' ); ?>
                <input type="hidden" id="edit_phase_id" name="edit_phase_id" value="" />
                <table class="form-table ejpt-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="edit_phase_name"><?php esc_html_e( 'Phase Name', 'ejpt' ); ?></label></th>
                        <td><input type="text" id="edit_phase_name" name="edit_phase_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_phase_description"><?php esc_html_e( 'Description', 'ejpt' ); ?></label></th>
                        <td><textarea id="edit_phase_description" name="edit_phase_description"></textarea></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Changes', 'ejpt' ), 'primary', 'submit_edit_phase' ); ?>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Modal open/close logic is in admin-scripts.js

    window.loadPhasesTable = function() {
        window.location.reload(); 
    };

    // Handle "Edit Phase" button click
    $('.ejpt-edit-phase-button').on('click', function() {
        var phaseId = $(this).data('phase-id');
        $.post(ejpt_data.ajax_url, {
            action: 'ejpt_get_phase',
            phase_id: phaseId,
            _ajax_nonce_get_phase: '<?php echo wp_create_nonce("ejpt_edit_phase_nonce"); ?>'
        }, function(response) {
            if (response.success) {
                $('#edit_phase_id').val(response.data.phase_id);
                $('#edit_phase_name').val(response.data.phase_name);
                $('#edit_phase_description').val(response.data.phase_description);
                $('#editPhaseModal').show();
            } else {
                 showNotice('error', response.data.message || 'Could not load phase data.');
            }
        }).fail(function() {
            showNotice('error', 'Request to load phase data failed.');
        });
    });

    // Handle "Toggle Status" button click
    $('.ejpt-toggle-status-phase-button').on('click', function() {
        var phaseId = $(this).data('phase-id');
        var newStatus = $(this).data('new-status');
        var confirmMessage = newStatus == 1 ? 
            '<?php echo esc_js(__("Are you sure you want to activate this phase?", "ejpt")); ?>' : 
            '<?php echo esc_js(__("Are you sure you want to deactivate this phase?", "ejpt")); ?>';

        if (!confirm(confirmMessage)) {
            return;
        }
        
        $.post(ejpt_data.ajax_url, {
            action: 'ejpt_toggle_phase_status',
            phase_id: phaseId,
            is_active: newStatus,
            _ajax_nonce: '<?php echo wp_create_nonce("ejpt_toggle_status_nonce"); ?>'
        }, function(response) {
            if (response.success) {
                showNotice('success', response.data.message);
                loadPhasesTable(); 
            } else {
                showNotice('error', response.data.message || 'Could not change phase status.');
            }
        }).fail(function() {
            showNotice('error', 'Request to change phase status failed.');
        });
    });
    
    // Common function to display notices
    if (typeof showNotice !== 'function') {
        window.showNotice = function(type, message) {
            $('.ejpt-notice').remove();
            var noticeHtml = '<div class="notice notice-' + type + ' is-dismissible ejpt-notice"><p>' + message + '</p>' +
                             '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
            $('div.wrap > h1').after(noticeHtml);
            setTimeout(function() {
                $('.ejpt-notice').fadeOut('slow', function() { $(this).remove(); });
            }, 5000);
            $('.ejpt-notice .notice-dismiss').on('click', function(event) {
                event.preventDefault();
                $(this).closest('.ejpt-notice').remove();
            });
        };
    }
});
</script> 