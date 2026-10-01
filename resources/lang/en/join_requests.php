<?php

return [
    'title'    => 'Join requests',
    'singular' => 'Join request',

    'statuses' => [
        'incomplete' => 'Incomplete information',
        'pending'    => 'Under review',
        'approved'   => 'Approved',
        'rejected'   => 'Rejected',
    ],

    'actions' => [
        'approved'          => 'Request approved',
        'rejected'          => 'Request rejected',
        'changes_requested' => 'Changes requested',
        'suspended'         => 'Account suspended',
        'reactivated'       => 'Account reactivated',
    ],

    'desired_role'        => 'Requested role',
    'service_group'       => 'Requested service group',
    'submitted_at'        => 'Submitted at',
    'decision_note'       => 'Reviewer note',
    'reviewed_by'         => 'Reviewed by',
    'reviewed_at'         => 'Decision at',
    'final_role'          => 'Assigned role',
    'final_service_group' => 'Assigned group',
    'review_history'      => 'Review history',

    // Waiting page
    'waiting_title' => 'Join request status',
    'waiting_hello' => 'Hello :name,',
    'waiting_intro' => 'Your request is under review. You will receive in-app notifications when a decision is made, and you can check its status here at any time.',
    'logout'        => 'Log out',
];
