<?php
/**
 * Class FeedbackFormTest
 *
 * @package WP_Feedback_Form
 */

class FeedbackFormTest extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        // Setup test environment
    }

    public function tearDown(): void {
        parent::tearDown();
        // Clean up test environment
    }

    /**
     * Test that the plugin is installed and activated
     */
    public function test_plugin_activated() {
        $this->assertTrue(is_plugin_active('wp-feedback-form/wp-feedback-form.php'));
    }

    /**
     * Test shortcode exists
     */
    public function test_shortcode_exists() {
        global $shortcode_tags;
        $this->assertArrayHasKey('feedback_form', $shortcode_tags);
    }

    /**
     * Test form submission
     */
    public function test_form_submission() {
        global $wpdb;
        
        // Simulate form submission
        $_POST['wp_feedback_nonce'] = wp_create_nonce('wp_feedback_form_submit');
        $_POST['wp_feedback_name'] = 'Test User';
        $_POST['wp_feedback_email'] = 'test@example.com';
        $_POST['wp_feedback_rating'] = '5';
        $_POST['wp_feedback_message'] = 'Test message';
        $_POST['wp_feedback_submit'] = '1';
        
        // Process the form
        $result = wp_feedback_form_handle_submission();
        
        // Assert submission was successful
        $this->assertTrue($result['success']);
        
        // Check database entry
        $table_name = $wpdb->prefix . 'feedback_submissions';
        $entry = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");
        
        $this->assertEquals('Test User', $entry->name);
        $this->assertEquals('test@example.com', $entry->email);
        $this->assertEquals(5, $entry->rating);
        $this->assertEquals('Test message', $entry->message);
    }
}
