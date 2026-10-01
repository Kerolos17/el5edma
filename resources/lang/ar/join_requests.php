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
        'review'            => 'مراجعة',
        'approved'          => 'قبول الطلب',
        'rejected'          => 'رفض الطلب',
        'changes_requested' => 'طلب تعديل البيانات',
        'suspend'           => 'إيقاف الحساب',
        'reactivate'        => 'إعادة التفعيل',
        'suspended'         => 'إيقاف الحساب',
        'reactivated'       => 'إعادة تفعيل الحساب',
    ],

    'filters' => [
        'open'      => 'قيد المراجعة',
        'suspended' => 'موقوف',
        'all'       => 'الكل',
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

    // Review page
    'search_placeholder' => 'ابحث بالاسم أو البريد...',
    'review_queue'       => 'قائمة المراجعة',
    'empty'              => 'لا توجد طلبات في هذه القائمة.',
    'review_summary'     => 'البريد: :email — الهاتف: :phone — الأسرة: :group — الدور المطلوب: :role — أُرسل في :date',

    'modal' => [
        'approve_tab'     => 'قبول',
        'reject_tab'      => 'رفض',
        'changes_tab'     => 'طلب تعديل',
        'reject_reason'   => 'سبب الرفض (إلزامي)',
        'changes_note'    => 'ما الذي يجب استكماله أو تعديله؟',
        'approve_confirm' => 'قبول وتنشيط الحساب',
    ],

    'confirms' => [
        'suspend' => 'هل أنت متأكد من إيقاف هذا الحساب؟ سيُمنع صاحبه من الوصول حتى إعادة التفعيل.',
    ],

    'toasts' => [
        'decision_saved'     => 'تم حفظ القرار وتسجيله.',
        'member_suspended'   => 'تم إيقاف الحساب.',
        'member_reactivated' => 'تم إعادة تفعيل الحساب.',
    ],

    'errors' => [
        'not_allowed'         => 'غير مصرح لك باتخاذ قرار على هذا الطلب.',
        'role_not_assignable' => 'لا يمكنك منح هذا الدور.',
        'group_out_of_scope'  => 'هذه الأسرة خارج نطاق صلاحيتك.',
        'already_decided'     => 'هذا الطلب حُسم بالفعل.',
        'note_required'       => 'يرجى كتابة السبب أو الملاحظة.',
    ],

    // Waiting page
    'waiting_title' => 'حالة طلب الانضمام',
    'waiting_hello' => 'مرحبًا :name،',
    'waiting_intro' => 'طلبك في مراجعة المسؤولين. ستصلك إشعارات داخل التطبيق عند اتخاذ القرار، ويمكنك مراجعة حالة طلبك من هذه الصفحة في أي وقت.',
    'logout'        => 'تسجيل الخروج',
];
