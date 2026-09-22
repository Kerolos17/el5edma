<?php

return [
    'title'            => 'Notifications',
    'mark_all_read'    => 'Mark all as read',
    'no_notifications' => 'No notifications',

    'birthday_title'        => 'Upcoming Birthday 🎂',
    'birthday_body'         => ':name is turning :age in :days days',
    'critical_case_title'   => 'Critical Case 🔴',
    'critical_case_body'    => 'A critical case was recorded for :name',
    'visit_reminder_title'  => 'Visit Reminder 📅',
    'visit_reminder_body'   => 'You have a scheduled visit tomorrow for :name',
    'unvisited_alert_title' => 'Unvisited Beneficiary ⏰',
    'unvisited_alert_body'  => ':days days have passed since the last visit to :name',
    'new_beneficiary_title' => 'New Beneficiary ✨',
    'new_beneficiary_body'  => ':name has been added by :adder',

    'system'      => 'System',
    'read'        => 'Read',
    'unread'      => 'Unread',
    'view_all'    => 'View all notifications',
    'type'        => 'Type',
    'title_field' => 'Title',
    'body_field'  => 'Body',
    'data_field'  => 'Data',
    'data_helper' => 'Technical JSON payload — only edit if you know what you are doing',
    'read_at'     => 'Read at',

    'types' => [
        'birthday'           => 'Birthday',
        'critical_case'      => 'Critical case',
        'visit_reminder'     => 'Visit reminder',
        'unvisited_alert'    => 'Unvisited alert',
        'new_beneficiary'    => 'New beneficiary',
        'servant_registered' => 'New servant',
        'welcome_servant'    => 'Welcome',
    ],

    'servant_registered' => [
        'title' => 'New Servant Registered',
        'body'  => ':name joined :service_group',
    ],

    'welcome_servant' => [
        'title' => 'Welcome to the Ministry',
        'body'  => 'Welcome :name, you have been successfully registered in :service_group. Your request will be reviewed by the service leader.',
    ],

    'push' => [
        'enable'      => 'Enable push notifications',
        'enabled'     => 'Push notifications enabled',
        'denied'      => 'Notifications blocked in browser',
        'unsupported' => 'Push notifications unavailable',
    ],

    'sound_mute'   => 'Mute notification sound',
    'sound_unmute' => 'Unmute notification sound',
];
