@php
    $status = '403';
    $title = __('shop.error_403_title');
    $message = __('shop.error_403_message');
    $homeLabel = __('shop.go_home');
@endphp

@include('errors._layout', ['homeLabel' => $homeLabel])
