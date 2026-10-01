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
    'password_hint'                     => 'At least 8 characters — letters, numbers and symbols recommended',
    'toggle_pw'                         => 'Show or hide password',
    'select_service_group'              => 'Select Service Group',
    'desired_role'                      => 'Requested role',
    'role_hint'                         => 'For information only: the actual role is decided by the reviewer upon approval.',
    'privacy_consent'                   => 'I agree to the privacy policy and to my data being used to administer the service.',
    'privacy_link'                      => 'Read the privacy policy',

    // Privacy policy page
    'privacy_title'            => 'Privacy & Data Use Policy',
    'privacy_what_title'       => 'What data do we collect?',
    'privacy_what_body'        => 'When you submit a join request we collect only the minimum data needed to run the service:',
    'privacy_item_name'        => 'Full name — to identify you within the service team.',
    'privacy_item_email'       => 'Email address — for sign-in and communication.',
    'privacy_item_phone'       => 'Phone number — for pastoral coordination.',
    'privacy_item_group'       => 'Service group and requested role — to organize membership.',
    'privacy_use_title'        => 'How do we use the data?',
    'privacy_use_body'         => 'The data is used exclusively to review the join request, organize the service families, and send service notifications (visits, birthdays, critical cases). Data is never sold or shared with external parties and never used for advertising.',
    'privacy_protection_title' => 'How do we protect the data?',
    'privacy_protection_body'  => 'Passwords are stored hashed and cannot be read by anyone — including administrators. Personal codes and medical/financial notes are encrypted at rest. Access to served-member data is limited by explicit roles and policies, and every administrative decision on requests is recorded in an immutable audit log.',
    'privacy_rights_title'     => 'Your rights',
    'privacy_rights_body'      => 'You may ask to correct your data or delete your account at any time by contacting your family leader or the system administrator, and the request will be honored within a reasonable period.',
    'privacy_back'             => 'Back',

    // Buttons
    'submit'               => 'Register',
    'already_have_account' => 'Already have an account? Log in',
    'back_to_login'        => 'Back to Login',

    // Errors
    'errors' => [
        'invalid_token'            => 'The registration link is invalid or expired.',
        'name_required'            => 'Full name is required.',
        'email_required'           => 'Email address is required.',
        'email_format'             => 'Email format is invalid.',
        'email_exists'             => 'This email is already in use. Please log in or use a different email.',
        'phone_required'           => 'Phone number is required.',
        'phone_exists'             => 'This phone number is already in use. Please log in or use a different number.',
        'password_required'        => 'Password is required.',
        'password_min'             => 'Password must be at least 8 characters.',
        'password_confirmation'    => 'Password and confirmation do not match.',
        'password_mismatch'        => 'Password and confirmation do not match.',
        'rate_limit_exceeded'      => 'Too many registration attempts. Please try again in :retry_after minutes.',
        'system_error'             => 'A system error occurred. Please try again later.',
        'validation_failed'        => 'Please check the entered data and fix the errors.',
        'service_group_required'   => 'Service group selection is required.',
        'service_group_invalid'    => 'The selected service group is invalid.',
        'service_group_inactive'   => 'The selected service group is inactive.',
        'desired_role_required'    => 'A requested role is required.',
        'desired_role_invalid'     => 'The selected requested role is invalid.',
        'privacy_consent_required' => 'You must accept the privacy policy before registering.',
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

    // Google completion form
    'google_title'         => 'Complete your join request',
    'google_lead'          => 'Your email was verified via Google. Complete the details below to submit your request for review.',
    'google_password_hint' => 'An account password — used as the second factor with your server code.',
];
