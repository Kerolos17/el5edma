<?php

return [
    'title'            => 'الإشعارات',
    'mark_all_read'    => 'تحديد الكل كمقروء',
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

    'types' => [
        'birthday'           => 'عيد ميلاد',
        'critical_case'      => 'حالة حرجة',
        'visit_reminder'     => 'تذكير بزيارة',
        'unvisited_alert'    => 'تنبيه عدم زيارة',
        'new_beneficiary'    => 'مخدوم جديد',
        'servant_registered' => 'خادم جديد',
        'welcome_servant'    => 'ترحيب',
    ],

    'servant_registered' => [
        'title' => 'خادم جديد انضم للخدمة',
        'body'  => 'انضم :name إلى :service_group',
    ],

    'welcome_servant' => [
        'title' => 'أهلاً وسهلاً بك في الخدمة',
        'body'  => 'مرحباً :name، تم تسجيلك بنجاح في :service_group. سيتم مراجعة طلبك من قبل أمين الخدمة.',
    ],

    'push' => [
        'enable'      => 'تفعيل إشعارات الجهاز',
        'enabled'     => 'إشعارات الجهاز مفعلة',
        'denied'      => 'الإشعارات محظورة من المتصفح',
        'unsupported' => 'إشعارات الجهاز غير متاحة',
    ],

    'sound_mute'   => 'كتم صوت الإشعارات',
    'sound_unmute' => 'تشغيل صوت الإشعارات',
];
