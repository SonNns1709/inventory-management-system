<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Trang chủ') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- data-role đổi bảng màu theo vai trò (xem resources/css/app.css) --}}
<body data-role="{{ auth()->user()?->role->value ?? 'guest' }}" class="bg-page text-slate-700 antialiased">
{{--
    Bố cục lưới như demo1: header trên cùng, sidebar bên trái, nội dung, footer.
    Màn hình hẹp: một cột; menu ẩn và mở bằng nút ☰ ở header (Alpine do Livewire nạp sẵn).
--}}
<div x-data="{ menu: false }"
     class="grid min-h-screen grid-cols-1 grid-rows-[auto_auto_1fr_auto] md:grid-cols-[15rem_1fr] md:grid-rows-[auto_1fr_auto]">

    @include('partials.header')

    @include('partials.sidebar')

    <main class="min-w-0 bg-white p-5 md:p-8">
        @include('partials.flash')

        @yield('content')
    </main>

    @include('partials.footer')
</div>
@livewireScripts
</body>
</html>
