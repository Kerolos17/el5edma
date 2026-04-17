<?php

return [
    'title'               => 'المستخدمون',
    'singular'            => 'مستخدم',
    'add'                 => 'إضافة مستخدم',
    'name'                => 'الاسم',
    'email'               => 'البريد الإلكتروني',
    'phone'               => 'الهاتف',
    'password'            => 'كلمة المرور',
    'personal_code'       => 'الكود الشخصي',
    'role'                => 'الدور',
    'service_group'       => 'الأسرة',
    'locale'              => 'اللغة',
    'is_active'           => 'نشط',
    'last_login_at'       => 'آخر دخول',

    // Roles
    'super_admin'         => 'مدير النظام',
    'service_leader'      => 'أمين الخدمة',
    'family_leader'       => 'أمين الأسرة',
    'servant'             => 'خادم',

    // Nested roles (used by UserRole enum)
    'roles' => [
        'super_admin'    => 'مدير النظام',
        'service_leader' => 'أمين الخدمة',
        'family_leader'  => 'أمين الأسرة',
        'servant'        => 'خادم',
    ],

    // Locale
    'arabic'              => 'العربية',
    'english'             => 'الإنجليزية',

    'code_hint'           => 'الكود الشخصي مرئي لمدير النظام فقط',
    'code_auto_generated' => 'يتم التوليد تلقائياً عند الإنشاء',
    'generate_code'       => 'توليد كود جديد',
    'my_profile'              => 'ملفي الشخصي',
    'my_info'                 => 'بياناتي',
    'save_locale'             => 'حفظ اللغة',
    'profile_photo'           => 'الصورة الشخصية',
    'profile_photo_helper'    => 'الحد الأقصى 1 ميجابايت. الصيغ المدعومة: JPEG, PNG, GIF, WebP',
    'profile_photo_too_large' => 'حجم الصورة أكبر من 1 ميجابايت',
    'profile_updated'         => 'تم تحديث الملف الشخصي بنجاح',
    'upload_photo'            => 'رفع صورة',
    'remove_photo'            => 'حذف الصورة',
    'remove_photo_confirm'    => 'هل أنت متأكد من حذف الصورة الشخصية؟',
    'uploading'               => 'جاري الرفع',
    'cannot_delete_self'  => 'لا يمكنك حذف حسابك الخاص',

    // Servant approval
    'approve_servant'              => 'الموافقة على الخادم',
    'approve_servant_confirmation' => 'هل أنت متأكد من الموافقة على هذا الخادم؟',
    'approve_servant_description'  => 'سيتمكن الخادم من تسجيل الدخول والوصول إلى النظام بعد الموافقة.',
    'servant_approved'             => 'تم الموافقة على الخادم بنجاح',
    'pending_approval'             => 'قيد الموافقة',
    'all_servants'                 => 'جميع الخدام',
    'pending_only'                 => 'قيد الموافقة فقط',
    'approved_only'                => 'تمت الموافقة عليهم فقط',
    'no_records'                   => 'لا يوجد مستخدمون',
    'unauthorized_role'            => 'غير مصرح لك بتعيين هذا الدور',
];
