<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'accepted' => 'يجب قبول حقل :attribute.',
    'accepted_if' => 'يجب قبول حقل :attribute عندما تكون قيمة :other هي :value.',
    'active_url' => 'حقل :attribute ليس رابطاً صالحاً.',
    'after' => 'يجب أن يكون تاريخ :attribute بعد :date.',
    'after_or_equal' => 'يجب أن يكون تاريخ :attribute بعد أو مساوياً لـ :date.',
    'alpha' => 'يجب أن يحتوي حقل :attribute على أحرف فقط.',
    'alpha_dash' => 'يجب أن يحتوي حقل :attribute على أحرف وأرقام وشرطات فقط.',
    'alpha_num' => 'يجب أن يحتوي حقل :attribute على أرقام وأحرف فقط.',
    'array' => 'يجب أن يكون حقل :attribute مصفوفة.',
    'before' => 'يجب أن يكون تاريخ :attribute قبل :date.',
    'before_or_equal' => 'يجب أن يكون تاريخ :attribute قبل أو مساوياً لـ :date.',
    'between' => [
        'array' => 'يجب أن تحتوي :attribute على ما بين :min و :max عناصر.',
        'file' => 'يجب أن يتراوح حجم ملف :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يتراوح عدد أحرف :attribute بين :min و :max.',
    ],
    'boolean' => 'يجب أن تكون قيمة حقل :attribute صحيحة أو خاطئة.',
    'confirmed' => 'حقل تأكيد :attribute غير متطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'حقل :attribute ليس تاريخاً صالحاً.',
    'date_equals' => 'يجب أن يكون تاريخ :attribute مساوياً لـ :date.',
    'date_format' => 'لا يتوافق حقل :attribute مع الصيغة :format.',
    'declined' => 'يجب رفض حقل :attribute.',
    'different' => 'يجب أن يختلف حقل :attribute عن :other.',
    'digits' => 'يجب أن يتكون :attribute من :digits رقماً.',
    'digits_between' => 'يجب أن يحتوي :attribute على ما بين :min و :max رقماً.',
    'dimensions' => 'أبعاد صورة :attribute غير صالحة.',
    'distinct' => 'يحتوي حقل :attribute على قيمة مكررة.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صالحاً.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values.',
    'enum' => 'قيمة :attribute المحددة غير صالحة.',
    'exists' => 'قيمة :attribute المحددة غير صالحة.',
    'file' => 'يجب أن يكون حقل :attribute ملفاً.',
    'filled' => 'يجب أن يحتوي حقل :attribute على قيمة.',
    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يكون عدد أحرف :attribute أكبر من :value.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عناصر أو أكثر.',
        'file' => 'يجب أن يكون حجم ملف :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من أو تساوي :value.',
        'string' => 'يجب أن يكون عدد أحرف :attribute أكبر من أو يساوي :value.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'قيمة :attribute المحددة غير صالحة.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صالحاً.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صالحاً.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صالحاً.',
    'json' => 'يجب أن يكون :attribute نصاً JSON صالحاً.',
    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من :value.',
        'string' => 'يجب أن يكون عدد أحرف :attribute أقل من :value.',
    ],
    'lte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عناصر أو أقل.',
        'file' => 'يجب أن يكون حجم ملف :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من أو تساوي :value.',
        'string' => 'يجب أن يكون عدد أحرف :attribute أقل من أو يساوي :value.',
    ],
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يتجاوز حجم ملف :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألا تكون قيمة :attribute أكبر من :max.',
        'string' => 'يجب ألا يتجاوز عدد أحرف :attribute :max حرفاً.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفاً من النوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفاً من النوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
        'file' => 'يجب أن يكون حجم ملف :attribute أكبر من :min كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من أو تساوي :min.',
        'string' => 'يجب أن يحتوي :attribute على :min أحرف على الأقل.',
    ],
    'multiple_of' => 'يجب أن تكون قيمة :attribute من مضاعفات :value.',
    'not_in' => 'قيمة :attribute المحددة غير صالحة.',
    'not_regex' => 'صيغة :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'password' => [
        'letters' => 'يجب أن يحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن يحتوي :attribute على حرف كبير وصغير واحد على الأقل.',
        'numbers' => 'يجب أن يحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن يحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت قيمة :attribute في تسريب بيانات. يرجى اختيار قيمة مختلفة.',
    ],
    'present' => 'يجب أن يكون حقل :attribute حاضراً.',
    'prohibited' => 'حقل :attribute محظور.',
    'regex' => 'صيغة :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما تكون قيمة :other هي :value.',
    'required_unless' => 'حقل :attribute مطلوب ما لم تكن :other ضمن :values.',
    'required_with' => 'حقل :attribute مطلوب عندما يكون :values موجوداً.',
    'required_with_all' => 'حقل :attribute مطلوب عندما تكون :values موجودة.',
    'required_without' => 'حقل :attribute مطلوب عندما لا يكون :values موجوداً.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا تكون أي من :values موجودة.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يتكون :attribute من :size أحرف.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'timezone' => 'يجب أن يكون :attribute منطقة زمنية صالحة.',
    'unique' => ':attribute مستخدم بالفعل.',
    'uploaded' => 'تعذّر رفع ملف :attribute.',
    'url' => 'صيغة رابط :attribute غير صالحة.',
    'uuid' => 'يجب أن يكون :attribute معرّف UUID صالحاً.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'email' => 'البريد الإلكتروني',
        'name' => 'الاسم',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'phone' => 'الهاتف',
        'address' => 'العنوان',
        'city' => 'المدينة',
        'country' => 'البلد',
    ],

];
