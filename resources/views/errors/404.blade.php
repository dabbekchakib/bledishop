@php
    $status = '404';
    $title = __('shop.error_404_title');
    $message = __('shop.error_404_message');
    $homeLabel = __('shop.go_home');
@endphp

@include('errors._layout', ['homeLabel' => $homeLabel])
