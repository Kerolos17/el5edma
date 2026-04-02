<?php

return [
    'required'  => 'حقل :attribute مطلوب.',
    'string'    => 'حقل :attribute يجب أن يكون نصاً.',
    'max'       => ['string' => 'حقل :attribute يجب ألا يتجاوز :max حرف.'],
    'min'       => ['string' => 'حقل :attribute يجب أن يكون :min أحرف على الأقل.'],
    'email'     => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صحيحاً.',
    'unique'    => 'قيمة :attribute مستخدمة من قبل.',
    'exists'    => 'قيمة :attribute غير موجودة.',
    'numeric'   => 'حقل :attribute يجب أن يكون رقماً.',
    'date'      => 'حقل :attribute يجب أن يكون تاريخاً صحيحاً.',
    'boolean'   => 'حقل :attribute يجب أن يكون صحيح أو خاطئ.',
    'in'        => 'القيمة المختارة في :attribute غير صحيحة.',
    'url'       => 'حقل :attribute يجب أن يكون رابطاً صحيحاً.',
    'image'     => 'حقل :attribute يجب أن يكون صورة.',
    'mimes'     => 'حقل :attribute يجب أن يكون ملفاً من نوع: :values.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',

    'attributes' => [
        'name'             => 'الاسم',
        'email'            => 'البريد الإلكتروني',
        'password'         => 'كلمة المرور',
        'phone'            => 'رقم الهاتف',
        'full_name'        => 'الاسم الكامل',
        'birth_date'       => 'تاريخ الميلاد',
        'service_group_id' => 'الأسرة',
        'personal_code'    => 'الكود الشخصي',
        'code'             => 'الكود',
    ],
];
