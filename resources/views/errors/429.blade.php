@php
    $status = '429';
    $title = __('shop.error_429_title');
    $message = __('shop.error_429_message');
    $homeLabel = __('shop.go_home');
@endphp

@include('errors._layout', ['homeLabel' => $homeLabel])
