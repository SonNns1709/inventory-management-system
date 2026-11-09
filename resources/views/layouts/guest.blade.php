<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Trang chủ') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Bố cục cho trang không cần đăng nhập: đăng nhập, trang lỗi 403 / 404 --}}
<body data-role="guest" class="flex min-h-screen items-center justify-center bg-page p-4 text-slate-700 antialiased">
<main class="w-full max-w-md">
    <p class="mb-5 text-center text-2xl font-bold tracking-wide text-brand">IMS<span class="text-accent">.Kho</span></p>

    @yield('content')

    <p class="mt-6 text-center text-xs text-slate-500">© {{ date('Y') }} Nhóm 11 — Chuyên đề tổng hợp · Quản lý hàng tồn kho</p>
</main>
</body>
</html>
