<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Add menu item to WordPress admin
function wp_feedback_form_admin_menu() {
    add_menu_page(
        __('Feedback Submissions', 'wp-feedback-form'),
        __('Feedback', 'wp-feedback-form'),
        'manage_options',
        'wp-feedback-form',
        'wp_feedback_form_admin_page',
        'dashicons-feedback',
        30
    );
}
add_action('admin_menu', 'wp_feedback_form_admin_menu');

// Admin page display
function wp_feedback_form_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle feedback deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['feedback_id'])) {
        if (check_admin_referer('delete_feedback_' . $_POST['feedback_id'])) {
            wp_feedback_form_delete_submission(intval($_POST['feedback_id']));
            echo '<div class="notice notice-success"><p>' . __('Feedback deleted successfully.', 'wp-feedback-form') . '</p></div>';
        }
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback_submissions';
    
    // Get feedback submissions with pagination
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $items_per_page = 20;
    $offset = ($page - 1) * $items_per_page;
    
    $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
    $total_pages = ceil($total_items / $items_per_page);
    
    $feedback_items = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $items_per_page,
            $offset
        )
    );
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Feedback Submissions', 'wp-feedback-form'); ?></h1>
        
        <?php if ($feedback_items): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Name', 'wp-feedback-form'); ?></th>
                        <th><?php echo esc_html__('Email', 'wp-feedback-form'); ?></th>
                        <th><?php echo esc_html__('Rating', 'wp-feedback-form'); ?></th>
                        <th><?php echo esc_html__('Message', 'wp-feedback-form'); ?></th>
                        <th><?php echo esc_html__('Date', 'wp-feedback-form'); ?></th>
                        <th><?php echo esc_html__('Actions', 'wp-feedback-form'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedback_items as $item): ?>
                        <tr>
                            <td><?php echo esc_html($item->name); ?></td>
                            <td><?php echo esc_html($item->email); ?></td>
                            <td><?php echo esc_html($item->rating); ?>/5</td>
                            <td><?php echo esc_html($item->message); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at))); ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('delete_feedback_' . $item->id); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="feedback_id" value="<?php echo esc_attr($item->id); ?>">
                                    <button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this feedback?', 'wp-feedback-form')); ?>')">
                                        <?php echo esc_html__('Delete', 'wp-feedback-form'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
            // Pagination
            echo '<div class="tablenav bottom">';
            echo '<div class="tablenav-pages">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $page
            ));
            echo '</div>';
            echo '</div>';
            ?>
            
        <?php else: ?>
            <p><?php echo esc_html__('No feedback submissions yet.', 'wp-feedback-form'); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

// Delete feedback submission
function wp_feedback_form_delete_submission($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback_submissions';
    
    return $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );
}
