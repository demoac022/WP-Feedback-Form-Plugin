<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Register shortcode
add_shortcode('feedback_form', 'wp_feedback_form_shortcode');

function wp_feedback_form_shortcode($atts) {
    // Initialize form status
    $form_status = '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wp_feedback_submit'])) {
        $form_status = wp_feedback_form_handle_submission();
    }
    
    // Start output buffering
    ob_start();
    
    // Show success/error message if exists
    if ($form_status) {
        echo '<div class="wp-feedback-form-message ' . ($form_status['success'] ? 'success' : 'error') . '">';
        echo esc_html($form_status['message']);
        echo '</div>';
    }
    
    // Only show form if no successful submission
    if (!$form_status || !$form_status['success']) {
        ?>
        <div class="wp-feedback-form-container">
            <form method="post" class="wp-feedback-form">
                <?php wp_nonce_field('wp_feedback_form_submit', 'wp_feedback_nonce'); ?>
                
                <div class="form-group">
                    <label for="wp_feedback_name"><?php echo esc_html__('Name', 'wp-feedback-form'); ?> <span class="required">*</span></label>
                    <input type="text" 
                           id="wp_feedback_name" 
                           name="wp_feedback_name" 
                           required 
                           placeholder="<?php echo esc_attr__('Your name', 'wp-feedback-form'); ?>"
                           value="<?php echo isset($_POST['wp_feedback_name']) ? esc_attr($_POST['wp_feedback_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="wp_feedback_email"><?php echo esc_html__('Email', 'wp-feedback-form'); ?> <span class="required">*</span></label>
                    <input type="email" 
                           id="wp_feedback_email" 
                           name="wp_feedback_email" 
                           required 
                           placeholder="<?php echo esc_attr__('Your email', 'wp-feedback-form'); ?>"
                           value="<?php echo isset($_POST['wp_feedback_email']) ? esc_attr($_POST['wp_feedback_email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="wp_feedback_rating"><?php echo esc_html__('Rating', 'wp-feedback-form'); ?> <span class="required">*</span></label>
                    <select id="wp_feedback_rating" name="wp_feedback_rating" required>
                        <option value=""><?php echo esc_html__('Select rating', 'wp-feedback-form'); ?></option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected(isset($_POST['wp_feedback_rating']) ? $_POST['wp_feedback_rating'] : '', $i); ?>>
                                <?php echo $i; ?> <?php echo _n('star', 'stars', $i, 'wp-feedback-form'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="wp_feedback_message"><?php echo esc_html__('Message', 'wp-feedback-form'); ?> <span class="required">*</span></label>
                    <textarea id="wp_feedback_message" 
                              name="wp_feedback_message" 
                              required 
                              placeholder="<?php echo esc_attr__('Your feedback message', 'wp-feedback-form'); ?>"><?php echo isset($_POST['wp_feedback_message']) ? esc_textarea($_POST['wp_feedback_message']) : ''; ?></textarea>
                </div>
                
                <!-- Honeypot field for spam protection -->
                <div class="wp-feedback-hp" style="display:none;">
                    <label>Leave this empty: <input type="text" name="wp_feedback_hp" /></label>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="wp_feedback_submit" class="wp-feedback-submit">
                        <?php echo esc_html__('Submit Feedback', 'wp-feedback-form'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    // Return the buffered content
    return ob_get_clean();
}

function wp_feedback_form_handle_submission() {
    // Verify nonce
    if (!isset($_POST['wp_feedback_nonce']) || !wp_verify_nonce($_POST['wp_feedback_nonce'], 'wp_feedback_form_submit')) {
        return array(
            'success' => false,
            'message' => __('Security verification failed. Please try again.', 'wp-feedback-form')
        );
    }
    
    // Check honeypot
    if (!empty($_POST['wp_feedback_hp'])) {
        return array(
            'success' => false,
            'message' => __('Invalid submission.', 'wp-feedback-form')
        );
    }
    
    // Validate required fields
    $required_fields = array('wp_feedback_name', 'wp_feedback_email', 'wp_feedback_rating', 'wp_feedback_message');
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            return array(
                'success' => false,
                'message' => __('Please fill in all required fields.', 'wp-feedback-form')
            );
        }
    }
    
    // Validate email
    if (!is_email($_POST['wp_feedback_email'])) {
        return array(
            'success' => false,
            'message' => __('Please enter a valid email address.', 'wp-feedback-form')
        );
    }
    
    // Validate rating
    $rating = intval($_POST['wp_feedback_rating']);
    if ($rating < 1 || $rating > 5) {
        return array(
            'success' => false,
            'message' => __('Please select a valid rating.', 'wp-feedback-form')
        );
    }
    
    // Sanitize input data
    $name = sanitize_text_field($_POST['wp_feedback_name']);
    $email = sanitize_email($_POST['wp_feedback_email']);
    $message = sanitize_textarea_field($_POST['wp_feedback_message']);
    
    // Save to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'feedback_submissions';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
            'rating' => $rating,
            'message' => $message,
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%d', '%s', '%s')
    );
    
    if ($result === false) {
        return array(
            'success' => false,
            'message' => __('Error saving feedback. Please try again.', 'wp-feedback-form')
        );
    }
    
    // Optional: Send email notification to admin
    $admin_email = get_option('admin_email');
    $subject = sprintf(__('New Feedback Submission from %s', 'wp-feedback-form'), $name);
    $email_message = sprintf(
        __("New feedback received:\n\nName: %s\nEmail: %s\nRating: %d/5\nMessage: %s", 'wp-feedback-form'),
        $name,
        $email,
        $rating,
        $message
    );
    
    wp_mail($admin_email, $subject, $email_message);
    
    return array(
        'success' => true,
        'message' => __('Thank you for your feedback!', 'wp-feedback-form')
    );
}
