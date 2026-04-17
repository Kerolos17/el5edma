<?php

return [
    'title'               => 'Users',
    'singular'            => 'User',
    'add'                 => 'Add User',
    'name'                => 'Name',
    'email'               => 'Email',
    'phone'               => 'Phone',
    'password'            => 'Password',
    'personal_code'       => 'Personal Code',
    'role'                => 'Role',
    'service_group'       => 'Service Group',
    'locale'              => 'Language',
    'is_active'           => 'Active',
    'last_login_at'       => 'Last Login',

    'super_admin'         => 'System Admin',
    'service_leader'      => 'Service Leader',
    'family_leader'       => 'Group Leader',
    'servant'             => 'Servant',

    // Nested roles (used by UserRole enum)
    'roles' => [
        'super_admin'    => 'System Admin',
        'service_leader' => 'Service Leader',
        'family_leader'  => 'Group Leader',
        'servant'        => 'Servant',
    ],

    'arabic'              => 'Arabic',
    'english'             => 'English',

    'code_hint'           => 'Personal code is visible to system admin only',
    'code_auto_generated' => 'Auto-generated upon creation',
    'generate_code'       => 'Generate New Code',
    'my_profile'              => 'My Profile',
    'my_info'                 => 'My Information',
    'save_locale'             => 'Save Language',
    'profile_photo'           => 'Profile Photo',
    'profile_photo_helper'    => 'Max 1 MB. Supported formats: JPEG, PNG, GIF, WebP',
    'profile_photo_too_large' => 'Image size exceeds 1 MB',
    'profile_updated'         => 'Profile updated successfully',
    'upload_photo'            => 'Upload Photo',
    'remove_photo'            => 'Remove Photo',
    'remove_photo_confirm'    => 'Are you sure you want to remove the profile photo?',
    'uploading'               => 'Uploading',
    'cannot_delete_self'  => 'You cannot delete your own account',

    // Servant approval
    'approve_servant'              => 'Approve Servant',
    'approve_servant_confirmation' => 'Are you sure you want to approve this servant?',
    'approve_servant_description'  => 'The servant will be able to log in and access the system after approval.',
    'servant_approved'             => 'Servant approved successfully',
    'pending_approval'             => 'Pending Approval',
    'all_servants'                 => 'All Servants',
    'pending_only'                 => 'Pending Only',
    'approved_only'                => 'Approved Only',
    'no_records'                   => 'No users found',
    'unauthorized_role'            => 'You are not authorized to assign this role',
];
