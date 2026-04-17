<?php

return [
    // Titles
    'title'   => 'New Servant Registration',
    'welcome' => 'Welcome to the Ministry of Anba Samuel the Confessor',

    // Side panel
    'panel_headline' => 'Welcome to the Ministry',
    'panel_sub'      => 'Register to join your service group and be part of the Ministry of Anba Samuel the Confessor.',
    'feature_1'      => 'Connect with your service group',
    'feature_2'      => 'Track beneficiaries and visits',
    'feature_3'      => 'Stay updated on everything',

    // Password strength
    'pw_weak'                  => 'Weak',
    'pw_fair'                  => 'Fair',
    'pw_good'                  => 'Good',
    'pw_strong'                => 'Strong',
    'pw_great'                 => 'Great',
    'service_group'            => 'Service Group',
    'success_title'            => 'Registration Successful!',
    'success_message'          => 'Your account has been created successfully. Your request will be reviewed by the service leader.',
    'success'                  => 'Your account has been created successfully! Your request will be reviewed by the service leader and you will be notified upon approval.',
    'pending_approval'         => 'Your account is pending approval',
    'pending_approval_message' => 'Your account has been created successfully and is now pending review by the service leader. You will be notified when your account is approved.',

    // Fields
    'name'                              => 'Full Name',
    'name_placeholder'                  => 'Enter your full name',
    'email'                             => 'Email Address',
    'email_placeholder'                 => 'example@domain.com',
    'phone'                             => 'Phone Number',
    'phone_placeholder'                 => '01234567890',
    'password'                          => 'Password',
    'password_placeholder'              => 'Enter a strong password',
    'password_confirmation'             => 'Confirm Password',
    'password_confirmation_placeholder' => 'Re-enter your password',
    'select_service_group'              => 'Select Service Group',

    // Buttons
    'submit'               => 'Register',
    'already_have_account' => 'Already have an account? Log in',
    'back_to_login'        => 'Back to Login',

    // Errors
    'errors' => [
        'invalid_token'          => 'The registration link is invalid or expired.',
        'name_required'          => 'Full name is required.',
        'email_required'         => 'Email address is required.',
        'email_format'           => 'Email format is invalid.',
        'email_exists'           => 'This email is already in use. Please log in or use a different email.',
        'phone_required'         => 'Phone number is required.',
        'phone_exists'           => 'This phone number is already in use. Please log in or use a different number.',
        'password_required'      => 'Password is required.',
        'password_min'           => 'Password must be at least 8 characters.',
        'password_confirmation'  => 'Password and confirmation do not match.',
        'password_mismatch'      => 'Password and confirmation do not match.',
        'rate_limit_exceeded'    => 'Too many registration attempts. Please try again in :retry_after minutes.',
        'system_error'           => 'A system error occurred. Please try again later.',
        'validation_failed'      => 'Please check the entered data and fix the errors.',
        'service_group_required' => 'Service group selection is required.',
        'service_group_invalid'  => 'The selected service group is invalid.',
        'service_group_inactive' => 'The selected service group is inactive.',
    ],

    // Popup modal (shown after successful registration)
    'popup_awaiting'   => 'Request under review',
    'popup_await_note' => 'Your family leader or service leader will review your details and contact you to confirm your account activation.',

    // Messages
    'messages' => [
        'account_created' => 'Your account has been created successfully!',
        'login_now'       => 'You can now log in using your email and password.',
        'contact_leader'  => 'If you encounter any issues, please contact your group leader.',
    ],

    // Instructions
    'instructions' => [
        'fill_form'             => 'Please fill in all fields below to register for the service group.',
        'password_requirements' => 'Password must be at least 8 characters.',
        'unique_email'          => 'Make sure to use an email that has not been used before.',
        'unique_phone'          => 'Make sure to use a phone number that has not been used before.',
    ],
];
