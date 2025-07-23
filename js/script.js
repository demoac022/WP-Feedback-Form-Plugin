jQuery(document).ready(function($) {
    // Form submission handling
    $('.wp-feedback-form').on('submit', function(e) {
        // Basic client-side validation
        var $form = $(this);
        var $required = $form.find('[required]');
        var valid = true;
        
        // Remove any existing error messages
        $('.wp-feedback-form-error').remove();
        
        // Check each required field
        $required.each(function() {
            if (!$(this).val()) {
                valid = false;
                $(this).addClass('error');
                $('<div class="wp-feedback-form-error">' + wpFeedbackForm.requiredFieldMessage + '</div>')
                    .insertAfter($(this));
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Validate email format
        var $email = $form.find('input[type="email"]');
        if ($email.val() && !isValidEmail($email.val())) {
            valid = false;
            $email.addClass('error');
            $('<div class="wp-feedback-form-error">' + wpFeedbackForm.invalidEmailMessage + '</div>')
                .insertAfter($email);
        }
        
        if (!valid) {
            e.preventDefault();
        }
    });
    
    // Remove error styling on input
    $('.wp-feedback-form input, .wp-feedback-form textarea').on('input', function() {
        $(this).removeClass('error');
        $(this).next('.wp-feedback-form-error').remove();
    });
    
    // Helper function to validate email
    function isValidEmail(email) {
        var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return pattern.test(email);
    }
});
