@props([
    'sidebar' => false,
])

@php
    $logo = '<img src="'.e(asset('images/michelin-logo.png')).'" alt="'.e(config('app.name')).' — Michelin" class="h-7 w-auto dark:brightness-0 dark:invert" />';
@endphp

@if($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo">{!! $logo !!}</x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo">{!! $logo !!}</x-slot>
    </flux:brand>
@endif
