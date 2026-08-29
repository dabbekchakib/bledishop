@php
    $status = '500';
    $title = __('shop.error_500_title');
    $message = __('shop.error_500_message');
    $homeLabel = __('shop.go_home');
@endphp

@include('errors._layout', ['homeLabel' => $homeLabel])
