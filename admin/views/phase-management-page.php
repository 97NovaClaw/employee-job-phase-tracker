<?php
// /admin/views/phase-management-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $phases, $total_phases, $current_page, $per_page, $search_term, $active_filter;

?>
<div class="wrap oo-phase-management-page">
    <h1><?php esc_html_e( 'Job Phase Management', 'operations-organizer' ); ?></h1>

    <button id="openAddOOPhaseModalBtn" class="page-title-action oo-open-modal-button" data-modal-id="addOOPhaseModal"><?php esc_html_e( 'Add New Phase', 'operations-organizer' ); ?></button>

    <form method="get" class="oo-filters-form">
        <input type="hidden" name="page" value="oo_phases" />
        <div class="wp-filter">
            <div class="filter-items">
                <label for="stream_type_filter" class="screen-reader-text"><?php esc_html_e('Filter by Stream Type', 'operations-organizer');?></label>
                <select name="stream_type_filter" id="stream_type_filter">
                    <option value=""><?php esc_html_e('All Stream Types', 'operations-organizer');?></option>
                    <?php foreach ($GLOBALS['stream_types'] as $st) : ?>
                        <option value="<?php echo esc_attr($st->stream_type_id); ?>" <?php selected($GLOBALS['selected_stream_type_id'], $st->stream_type_id); ?>><?php echo esc_html($st->stream_type_name); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="status_filter" class="screen-reader-text"><?php esc_html_e('Filter by status', 'operations-organizer');?></label>
                <select name="status_filter" id="status_filter">
                    <option value="all" <?php selected($active_filter, 'all'); ?>><?php esc_html_e('All Statuses', 'operations-organizer');?></option>
                    <option value="active" <?php selected($active_filter, 'active'); ?>><?php esc_html_e('Active', 'operations-organizer');?></option>
                    <option value="inactive" <?php selected($active_filter, 'inactive'); ?>><?php esc_html_e('Inactive', 'operations-organizer');?></option>
                </select>
                <input type="submit" name="filter_action" class="button" value="<?php esc_attr_e('Filter', 'operations-organizer');?>">
            </div>
            <p class="search-box">
                <label class="screen-reader-text" for="phase-search-input"><?php esc_html_e( 'Search Phases:', 'operations-organizer' ); ?></label>
                <input type="search" id="phase-search-input" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by Name/Description', 'operations-organizer' ); ?>" />
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Phases', 'operations-organizer' ); ?>" />
            </p>
        </div>
    </form>
    <div class="clear"></div>

    <div id="oo-phase-list-table-wrapper">
        <table class="wp-list-table widefat fixed striped table-view-list phases">
            <thead>
                <tr>
                    <?php 
                    $columns = [
                        'phase_name' => __('Phase Name', 'operations-organizer'),
                        'phase_slug' => __('Slug', 'operations-organizer'),
                        'stream_type_name' => __('Stream Type', 'operations-organizer'),
                        'phase_description' => __('Description', 'operations-organizer'),
                        'sort_order' => __('Order', 'operations-organizer'),
                        'status' => __('Status', 'operations-organizer'),
                        'actions' => __('Actions', 'operations-organizer'),
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
                            <td class="phase_name column-phase_name" data-colname="<?php esc_attr_e('Phase Name', 'operations-organizer'); ?>">
                                <?php echo esc_html( $phase->phase_name ); ?>
                            </td>
                            <td class="phase_slug column-phase_slug" data-colname="<?php esc_attr_e('Slug', 'operations-organizer'); ?>">
                                <?php echo esc_html( $phase->phase_slug ); ?>
                            </td>
                            <td class="stream_type_name column-stream_type_name" data-colname="<?php esc_attr_e('Stream Type', 'operations-organizer'); ?>">
                                <?php echo isset($phase->stream_type_name) ? esc_html($phase->stream_type_name) : 'N/A'; ?>
                            </td>
                            <td class="phase_description column-phase_description" data-colname="<?php esc_attr_e('Description', 'operations-organizer'); ?>">
                                <?php echo esc_html( $phase->phase_description ); ?>
                            </td>
                            <td class="sort_order column-sort_order" data-colname="<?php esc_attr_e('Order', 'operations-organizer'); ?>">
                                <?php echo intval( $phase->sort_order ); ?>
                            </td>
                            <td class="status column-status" data-colname="<?php esc_attr_e('Status', 'operations-organizer'); ?>">
                                <?php if ( $phase->is_active ) : ?>
                                    <span style="color: green;"><?php esc_html_e( 'Active', 'operations-organizer' ); ?></span>
                                <?php else : ?>
                                    <span style="color: red;"><?php esc_html_e( 'Inactive', 'operations-organizer' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions column-actions" data-colname="<?php esc_attr_e('Actions', 'operations-organizer'); ?>">
                                <button class="button-secondary oo-edit-phase-button" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>"><?php esc_html_e('Edit', 'operations-organizer'); ?></button>
                                <?php if ( $phase->is_active ) : ?>
                                    <button class="button-secondary oo-toggle-status-phase-button oo-deactivate" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-new-status="0"><?php esc_html_e('Deactivate', 'operations-organizer'); ?></button>
                                <?php else : ?>
                                    <button class="button-secondary oo-toggle-status-phase-button oo-activate" data-phase-id="<?php echo esc_attr( $phase->phase_id ); ?>" data-new-status="1"><?php esc_html_e('Activate', 'operations-organizer'); ?></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo count($columns); ?>"><?php esc_html_e( 'No phases found.', 'operations-organizer' ); ?></td>
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
    <div id="addOOPhaseModal" class="oo-modal" style="display:none;">
        <div class="oo-modal-content">
            <span class="oo-close-button">&times;</span>
            <h2><?php esc_html_e( 'Add New Phase', 'operations-organizer' ); ?></h2>
            <form id="oo-add-phase-form">
                <?php wp_nonce_field( 'oo_add_phase_nonce', 'oo_add_phase_nonce' ); ?>
                <table class="form-table oo-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="add_stream_type_id"><?php esc_html_e( 'Stream Type', 'operations-organizer' ); ?></label></th>
                        <td>
                            <select id="add_stream_type_id" name="stream_type_id" required>
                                <option value=""><?php esc_html_e('-- Select Stream Type --', 'operations-organizer'); ?></option>
                                <?php foreach ($GLOBALS['stream_types'] as $st) : ?>
                                    <option value="<?php echo esc_attr($st->stream_type_id); ?>"><?php echo esc_html($st->stream_type_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_phase_slug"><?php esc_html_e( 'Phase Slug', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="add_phase_slug" name="phase_slug" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_phase_name"><?php esc_html_e( 'Phase Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="add_phase_name" name="phase_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_phase_description"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></label></th>
                        <td><textarea id="add_phase_description" name="phase_description"></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="add_sort_order"><?php esc_html_e( 'Sort Order', 'operations-organizer' ); ?></label></th>
                        <td><input type="number" id="add_sort_order" name="sort_order" value="0" /></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Add Phase', 'operations-organizer' ), 'primary', 'submit_add_phase' ); ?>
            </form>
        </div>
    </div>

    <!-- Edit Phase Modal -->
    <div id="editOOPhaseModal" class="oo-modal" style="display:none;">
        <div class="oo-modal-content">
            <span class="oo-close-button">&times;</span>
            <h2><?php esc_html_e( 'Edit Phase', 'operations-organizer' ); ?></h2>
            <form id="oo-edit-phase-form">
                <?php wp_nonce_field( 'oo_edit_phase_nonce', 'oo_edit_phase_nonce' ); ?>
                <input type="hidden" id="edit_phase_id" name="edit_phase_id" value="" />
                <table class="form-table oo-form-table">
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_stream_type_id"><?php esc_html_e( 'Stream Type', 'operations-organizer' ); ?></label></th>
                        <td>
                            <select id="edit_modal_stream_type_id" name="edit_stream_type_id" required>
                                <option value=""><?php esc_html_e('-- Select Stream Type --', 'operations-organizer'); ?></option>
                                <?php foreach ($GLOBALS['stream_types'] as $st) : ?>
                                    <option value="<?php echo esc_attr($st->stream_type_id); ?>"><?php echo esc_html($st->stream_type_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_phase_slug"><?php esc_html_e( 'Phase Slug', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="edit_modal_phase_slug" name="edit_phase_slug" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_phase_name"><?php esc_html_e( 'Phase Name', 'operations-organizer' ); ?></label></th>
                        <td><input type="text" id="edit_modal_phase_name" name="edit_phase_name" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_phase_description"><?php esc_html_e( 'Description', 'operations-organizer' ); ?></label></th>
                        <td><textarea id="edit_modal_phase_description" name="edit_phase_description"></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="edit_modal_sort_order"><?php esc_html_e( 'Sort Order', 'operations-organizer' ); ?></label></th>
                        <td><input type="number" id="edit_modal_sort_order" name="edit_sort_order" /></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Changes', 'operations-organizer' ), 'primary', 'submit_edit_phase' ); ?>
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
    $('.oo-edit-phase-button').on('click', function() {
        var phaseId = $(this).data('phase-id');
        $.post(oo_data.ajax_url, {
            action: 'oo_get_phase',
            phase_id: phaseId,
            _ajax_nonce_get_phase: oo_data.nonce_edit_phase
        }, function(response) {
            if (response.success) {
                $('#edit_phase_id').val(response.data.phase_id);
                $('#edit_modal_stream_type_id').val(response.data.stream_type_id);
                $('#edit_modal_phase_slug').val(response.data.phase_slug);
                $('#edit_modal_phase_name').val(response.data.phase_name);
                $('#edit_modal_phase_description').val(response.data.phase_description);
                $('#edit_modal_sort_order').val(response.data.sort_order);
                $('#editOOPhaseModal').show();
            } else {
                 showNotice('error', response.data.message || 'Could not load phase data.');
            }
        }).fail(function() {
            showNotice('error', 'Request to load phase data failed.');
        });
    });

    // Handle "Toggle Status" button click
    $('.oo-toggle-status-phase-button').on('click', function() {
        var phaseId = $(this).data('phase-id');
        var newStatus = $(this).data('new-status');
        var confirmMessage = newStatus == 1 ? 
            '<?php echo esc_js(__("Are you sure you want to activate this phase?", "operations-organizer")); ?>' : 
            '<?php echo esc_js(__("Are you sure you want to deactivate this phase?", "operations-organizer")); ?>';

        if (!confirm(confirmMessage)) {
            return;
        }
        
        $.post(oo_data.ajax_url, {
            action: 'oo_toggle_phase_status',
            phase_id: phaseId,
            is_active: newStatus,
            _ajax_nonce: oo_data.nonce_toggle_status
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