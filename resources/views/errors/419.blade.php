@php
    $status = '419';
    $title = __('shop.error_419_title');
    $message = __('shop.error_419_message');
    $homeLabel = __('shop.go_home');
@endphp

@include('errors._layout', ['homeLabel' => $homeLabel])
