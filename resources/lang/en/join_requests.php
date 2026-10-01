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
        'review'            => 'Review',
        'approved'          => 'Request approved',
        'rejected'          => 'Request rejected',
        'changes_requested' => 'Changes requested',
        'suspend'           => 'Suspend account',
        'reactivate'        => 'Reactivate',
        'suspended'         => 'Account suspended',
        'reactivated'       => 'Account reactivated',
    ],

    'filters' => [
        'open'      => 'Under review',
        'suspended' => 'Suspended',
        'all'       => 'All',
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

    // Review page
    'search_placeholder' => 'Search by name or email...',
    'review_queue'       => 'Review queue',
    'empty'              => 'No requests in this list.',
    'review_summary'     => 'Email: :email — Phone: :phone — Group: :group — Requested role: :role — Submitted: :date',

    'modal' => [
        'approve_tab'     => 'Approve',
        'reject_tab'      => 'Reject',
        'changes_tab'     => 'Request changes',
        'reject_reason'   => 'Rejection reason (required)',
        'changes_note'    => 'What must be completed or corrected?',
        'approve_confirm' => 'Approve & activate account',
    ],

    'confirms' => [
        'suspend' => 'Suspend this account? Its owner loses access until reactivated.',
    ],

    'toasts' => [
        'decision_saved'     => 'Decision saved and recorded.',
        'member_suspended'   => 'Account suspended.',
        'member_reactivated' => 'Account reactivated.',
    ],

    'errors' => [
        'not_allowed'         => 'You are not allowed to decide on this request.',
        'role_not_assignable' => 'You cannot grant this role.',
        'group_out_of_scope'  => 'This group is outside your span of control.',
        'already_decided'     => 'This request has already been decided.',
        'note_required'       => 'Please write the reason or note.',
    ],

    // Waiting page
    'waiting_title' => 'Join request status',
    'waiting_hello' => 'Hello :name,',
    'waiting_intro' => 'Your request is under review. You will receive in-app notifications when a decision is made, and you can check its status here at any time.',
    'refresh_now'   => 'Refresh status now',
    'refreshing'    => 'Refreshing...',
    'logout'        => 'Log out',
];
