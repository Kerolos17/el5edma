<?php

return [
    'title'    => 'طلبات الانضمام',
    'singular' => 'طلب انضمام',

    'statuses' => [
        'incomplete' => 'قيد استكمال البيانات',
        'pending'    => 'قيد المراجعة',
        'approved'   => 'مقبول',
        'rejected'   => 'مرفوض',
    ],

    'actions' => [
        'approved'          => 'قبول الطلب',
        'rejected'          => 'رفض الطلب',
        'changes_requested' => 'طلب تعديل البيانات',
        'suspended'         => 'إيقاف الحساب',
        'reactivated'       => 'إعادة تفعيل الحساب',
    ],

    'desired_role'        => 'الدور المطلوب',
    'service_group'       => 'الأسرة المطلوب الانضمام إليها',
    'submitted_at'        => 'تاريخ الإرسال',
    'decision_note'       => 'ملاحظة المراجعة',
    'reviewed_by'         => 'راجع الطلب',
    'reviewed_at'         => 'وقت القرار',
    'final_role'          => 'الدور المعيّن',
    'final_service_group' => 'الأسرة المعيّنة',
    'review_history'      => 'سجل المراجعات',

    // Waiting page
    'waiting_title' => 'حالة طلب الانضمام',
    'waiting_hello' => 'مرحبًا :name،',
    'waiting_intro' => 'طلبك في مراجعة المسؤولين. ستصلك إشعارات داخل التطبيق عند اتخاذ القرار، ويمكنك مراجعة حالة طلبك من هذه الصفحة في أي وقت.',
    'logout'        => 'تسجيل الخروج',
];
