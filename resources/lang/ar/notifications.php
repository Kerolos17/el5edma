<?php

return [
    'title'            => 'الإشعارات',
    'mark_all_read'    => 'تحديد الكل كمقروء',
    'mark_read'        => 'تعليم كمقروء',
    'unread_count'     => ':count غير مقروء',
    'no_notifications' => 'لا توجد إشعارات',

    // Types
    'birthday_title'        => 'عيد ميلاد قادم 🎂',
    'birthday_body'         => ':name يكمل :age عاماً بعد :days أيام',
    'critical_case_title'   => 'حالة حرجة 🔴',
    'critical_case_body'    => 'تم تسجيل حالة حرجة للمخدوم :name',
    'visit_reminder_title'  => 'تذكير بزيارة 📅',
    'visit_reminder_body'   => 'لديك زيارة مجدولة غداً للمخدوم :name',
    'unvisited_alert_title' => 'مخدوم لم يُزَر ⏰',
    'unvisited_alert_body'  => 'مر :days يوماً على آخر زيارة للمخدوم :name',
    'new_beneficiary_title' => 'مخدوم جديد ✨',
    'new_beneficiary_body'  => 'تم إضافة المخدوم :name بواسطة :adder',
    'visit_created_title'   => 'زيارة جديدة ✅',
    'visit_created_body'    => 'سجّل :servant زيارة جديدة للمخدوم :name',

    'system'      => 'النظام',
    'read'        => 'مقروء',
    'unread'      => 'غير مقروء',
    'view_all'    => 'عرض كل الإشعارات',
    'type'        => 'النوع',
    'title_field' => 'العنوان',
    'body_field'  => 'النص',
    'data_field'  => 'البيانات',
    'data_helper' => 'بيانات تقنية بصيغة JSON — لا تعدلها إلا إذا كنت متأكداً',
    'read_at'     => 'وقت القراءة',

    'servant_registered' => [
        'title' => 'خادم جديد انضم للخدمة',
        'body'  => 'انضم :name إلى :service_group',
    ],

    'welcome_servant' => [
        'title' => 'أهلاً وسهلاً بك في الخدمة',
        'body'  => 'مرحباً :name، تم تسجيلك بنجاح في :service_group. سيتم مراجعة طلبك من قبل أمين الخدمة.',
    ],

    'types' => [
        'birthday'               => 'عيد ميلاد',
        'critical_case'          => 'حالة حرجة',
        'visit_reminder'         => 'تذكير بزيارة',
        'unvisited_alert'        => 'تنبيه عدم زيارة',
        'new_beneficiary'        => 'مخدوم جديد',
        'visit_created'          => 'زيارة جديدة',
        'servant_registered'     => 'خادم جديد',
        'welcome_servant'        => 'ترحيب',
        'join_request_submitted' => 'طلب انضمام جديد',
        'join_request_decision'  => 'قرار على طلب انضمام',
    ],

    'join_request_submitted' => [
        'title' => 'طلب انضمام جديد',
        'body'  => ':name تقدّم بطلب انضمام إلى :service_group وينتظر المراجعة.',
    ],

    'join_request_submitted_title' => 'طلب انضمام جديد',
    'join_request_decision_title'  => 'قرار على طلب الانضمام',

    'join_request_decision' => [
        'approved_title'    => 'تم قبول طلب انضمامك',
        'approved_body'     => 'مرحباً :name، تم قبول طلبك وتفعيل حسابك. يمكنك الآن تسجيل الدخول والبدء في الخدمة.',
        'rejected_title'    => 'تم رفض طلب الانضمام',
        'rejected_body'     => 'مرحباً :name، تم رفض طلب انضمامك. السبب: :reason. يمكنك التواصل مع المسؤول للاستفسار.',
        'changes_title'     => 'مطلوب تعديل بيانات طلبك',
        'changes_body'      => 'مرحباً :name، نحتاج منك: :note. بعد التعديل سيراجع المسؤول طلبك.',
        'suspended_title'   => 'تم إيقاف حسابك',
        'suspended_body'    => 'تم إيقاف حسابك مؤقتاً من قبل المسؤول. تواصل مع المسؤول للاستفسار.',
        'reactivated_title' => 'تم إعادة تفعيل حسابك',
        'reactivated_body'  => 'مرحباً :name، تم إعادة تفعيل حسابك ويمكنك تسجيل الدخول الآن.',
    ],

    'push' => [
        'device_title'   => 'إشعارات هذا الجهاز',
        'enable'         => 'تفعيل إشعارات الجهاز',
        'enabled'        => 'إشعارات الجهاز مفعلة',
        'disable'        => 'إيقاف إشعارات الجهاز',
        'denied'         => 'الإشعارات محظورة من المتصفح',
        'unsupported'    => 'إشعارات الجهاز غير متاحة',
        'unavailable'    => 'خدمة الإشعارات غير متاحة على شبكتك الحالية — جرّب شبكة أخرى',
        'enabled_toast'  => 'تم تشغيل الإشعارات على هذا الجهاز',
        'disabled_toast' => 'تم إيقاف الإشعارات على هذا الجهاز',
    ],

    'sound_mute'   => 'كتم صوت الإشعارات',
    'sound_unmute' => 'تشغيل صوت الإشعارات',
];
