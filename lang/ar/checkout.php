<?php

return [
    'title' => 'إتمام الطلب',
    'meta_description' => 'أكمل طلبك.',
    'intro' => 'أدخل معلوماتك ثم قم بتأكيد طلبك.',
    'form_errors_title' => 'يرجى تصحيح الأخطاء أدناه.',

    'contact_title' => 'معلومات الاتصال',
    'first_name' => 'الاسم الأول',
    'last_name' => 'الاسم الأخير',
    'email_label' => 'البريد الإلكتروني',
    'phone' => 'الهاتف',

    'shipping_title' => 'عنوان التوصيل',
    'address' => 'العنوان',
    'city' => 'المدينة',
    'postal_code' => 'الرمز البريدي',
    'country' => 'البلد',
    'notes' => 'ملاحظة على الطلب (اختياري)',

    'account_title' => 'إنشاء حساب',
    'account_hint' => 'فعّل هذا الخيار لإنشاء حساب وتتبع طلباتك لاحقاً.',
    'account_create_label' => 'أرغب في إنشاء حساب باستخدام بريدي الإلكتروني.',
    'password' => 'كلمة المرور',
    'password_confirmation' => 'تأكيد كلمة المرور',

    'summary' => 'ملخص الطلب',
    'qty_label' => '{0} الكمية|{1} :count عنصر|[2,*] :count عناصر',
    'subtotal' => 'المجموع الفرعي',
    'total' => 'الإجمالي',
    'shipping_note' => 'التوصيل والضريبة، إن وُجدت، يُعالجان لاحقاً وفقاً لشروط البائع.',
    'place_order' => 'تأكيد الطلب',
    'privacy_hint' => 'يتم تسجيل طلبك دون دفع عبر الإنترنت.',

    'confirmation_title' => 'تم تسجيل الطلب',
    'confirmation_status' => 'حالة الطلب:',
    'confirmation_email_hint' => 'تم إرسال بريد إلكتروني للتأكيد إلى :email.',
    'order_number_label' => 'رقم الطلب',
    'totals_title' => 'المبالغ',
    'items_label' => 'العناصر',
    'discount' => 'الخصم',
    'shipping' => 'التوصيل',
    'tax' => 'الضريبة',
    'sku' => 'المرجع',
    'continue_shopping' => 'متابعة التسوق',

    'status' => [
        'pending' => 'قيد الانتظار',
        'confirmed' => 'مؤكد',
        'processing' => 'قيد المعالجة',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
        'on_hold' => 'قيد المراجعة',
    ],

    'validation' => [
        'first_name_required' => 'الاسم الأول مطلوب.',
        'last_name_required' => 'الاسم الأخير مطلوب.',
        'phone_required' => 'رقم الهاتف مطلوب.',
        'email_required' => 'البريد الإلكتروني مطلوب.',
        'email_invalid' => 'البريد الإلكتروني غير صالح.',
        'address_required' => 'العنوان مطلوب.',
        'password_required' => 'كلمة المرور مطلوبة لإنشاء حساب.',
        'password_confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        'password_min' => 'يجب أن تتكون كلمة المرور من :min أحرف على الأقل.',
    ],

    'errors' => [
        'cart_empty' => 'سلتك فارغة. أضف عناصر قبل إتمام طلبك.',
        'login_required' => 'يرجى تسجيل الدخول لإتمام الطلب.',
        'product_unavailable' => 'أحد منتجات سلتك لم يعد متوفراً.',
        'variant_unavailable' => 'أحد المتغيرات في سلتك لم يعد متوفراً.',
        'stock_changed' => 'تغير مخزون «:name». متوفر فقط :available وحدة.',
    ],

    'stock_reason' => 'الطلب :order',

    'notification' => [
        'new_order' => 'تم إنشاء طلب جديد.',
    ],

    'email' => [
        'subject' => 'تأكيد الطلب :order',
        'heading' => 'الطلب :order',
        'body' => 'شكراً لك! تم تسجيل طلبك. إليك الملخص:',
        'column_product' => 'المنتج',
        'column_qty' => 'الكمية',
        'column_price' => 'السعر',
        'discount' => 'الخصم',
        'shipping' => 'التوصيل',
        'total' => 'الإجمالي',
        'footer' => 'نقوم بمعالجة طلبك في أقرب وقت ممكن.',
    ],
];
