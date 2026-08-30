<?php

return [
    'title' => 'Checkout',
    'meta_description' => 'Complete your order.',
    'intro' => 'Your details, then confirm your order.',
    'form_errors_title' => 'Please fix the errors below.',

    'contact_title' => 'Contact information',
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'email_label' => 'Email address',
    'phone' => 'Phone',

    'shipping_title' => 'Shipping address',
    'address' => 'Address',
    'city' => 'City',
    'postal_code' => 'Postal code',
    'country' => 'Country',
    'notes' => 'Order note (optional)',

    'account_title' => 'Create an account',
    'account_hint' => 'Enable this to create an account and track your orders later.',
    'account_create_label' => 'I would like to create an account with my email address.',
    'password' => 'Password',
    'password_confirmation' => 'Confirm password',

    'summary' => 'Order summary',
    'qty_label' => '{0} quantity|{1} :count item|[2,*] :count items',
    'subtotal' => 'Subtotal',
    'total' => 'Total',
    'shipping_note' => 'Shipping and tax, if applicable, are handled later according to the seller\'s terms.',
    'place_order' => 'Confirm order',
    'privacy_hint' => 'Your order is recorded without online payment.',

    'confirmation_title' => 'Order recorded',
    'confirmation_status' => 'Order status:',
    'confirmation_email_hint' => 'A confirmation email has been sent to :email.',
    'order_number_label' => 'Order number',
    'totals_title' => 'Amounts',
    'items_label' => 'Items',
    'discount' => 'Discount',
      'shipping' => 'Shipping',
      'tax' => 'Tax',
      'shipping_free' => 'Free',
    'sku' => 'Ref.',
    'continue_shopping' => 'Continue shopping',

    'status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'on_hold' => 'On hold',
    ],

    'validation' => [
        'first_name_required' => 'The first name is required.',
        'last_name_required' => 'The last name is required.',
        'phone_required' => 'The phone number is required.',
        'email_required' => 'The email address is required.',
        'email_invalid' => 'The email address is invalid.',
        'address_required' => 'The address is required.',
        'password_required' => 'A password is required to create an account.',
        'password_confirmed' => 'The password confirmation does not match.',
        'password_min' => 'The password must be at least :min characters.',
    ],

    'errors' => [
        'cart_empty' => 'Your cart is empty. Add items before placing your order.',
        'login_required' => 'Please log in to place an order.',
        'product_unavailable' => 'One of the products in your cart is no longer available.',
        'variant_unavailable' => 'One of the variants in your cart is no longer available.',
        'stock_changed' => 'The stock of ":name" has changed. Only :available unit(s) available.',
    ],

    'stock_reason' => 'Order :order',

    'notification' => [
        'new_order' => 'A new order has been created.',
    ],

    'email' => [
        'subject' => 'Order confirmation :order',
        'heading' => 'Order :order',
        'body' => 'Thank you! Your order has been recorded. Here is the summary:',
        'column_product' => 'Product',
        'column_qty' => 'Qty',
        'column_price' => 'Price',
        'discount' => 'Discount',
        'shipping' => 'Shipping',
        'total' => 'Total',
        'footer' => 'We are processing your order as soon as possible.',
    ],
];
