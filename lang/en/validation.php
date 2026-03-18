<?php

return [
    'required' => 'The :attribute field is required.',
    'string'   => 'The :attribute must be a string.',
    'max'      => ['string' => 'The :attribute may not be greater than :max characters.'],
    'min'      => ['string' => 'The :attribute must be at least :min characters.'],
    'email'    => 'The :attribute must be a valid email address.',
    'unique'   => 'The :attribute has already been taken.',
    'exists'   => 'The selected :attribute is invalid.',
    'numeric'  => 'The :attribute must be a number.',
    'date'     => 'The :attribute is not a valid date.',
    'boolean'  => 'The :attribute field must be true or false.',
    'in'       => 'The selected :attribute is invalid.',
    'url'      => 'The :attribute must be a valid URL.',
    'image'    => 'The :attribute must be an image.',
    'mimes'    => 'The :attribute must be a file of type: :values.',
    'confirmed'=> 'The :attribute confirmation does not match.',

    'attributes' => [
        'name'             => 'name',
        'email'            => 'email',
        'password'         => 'password',
        'phone'            => 'phone',
        'full_name'        => 'full name',
        'birth_date'       => 'birth date',
        'service_group_id' => 'service group',
        'personal_code'    => 'personal code',
        'code'             => 'code',
    ],
];
