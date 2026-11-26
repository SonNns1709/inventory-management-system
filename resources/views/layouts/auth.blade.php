<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Đăng nhập') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Bố cục hai cột cho màn hình đăng nhập (và sau này: quên / đặt lại mật khẩu nếu có): cột trái giới thiệu hệ thống (@section('aside')),
    cột phải là biểu mẫu (@section('content')). Dưới màn hình lớn (lg) cột trái ẩn đi, chỉ còn biểu mẫu kèm tên hệ thống.
    Các trang lỗi 403 / 404 vẫn dùng layouts/guest.blade.php (thẻ nhỏ ở giữa), không bị ảnh hưởng.
--}}
<body data-role="guest" class="min-h-screen bg-white text-slate-700 antialiased">
<div class="flex min-h-screen">
    <aside class="auth-aside hidden flex-[1.1] flex-col justify-center px-12 py-16 text-white lg:flex">
        @yield('aside')
    </aside>

    <main class="flex flex-1 flex-col items-center justify-center px-6 py-10">
        <div class="w-full max-w-sm">
            {{-- Chỉ hiện khi cột trái bị ẩn (điện thoại, máy tính bảng) --}}
            <p class="mb-6 text-center text-2xl font-bold tracking-wide text-brand lg:hidden">IMS<span class="text-accent">.Kho</span></p>

            @yield('content')
        </div>

        <p class="mt-10 text-center text-xs text-slate-500">© {{ date('Y') }} Nhóm 11 — Chuyên đề tổng hợp · Quản lý hàng tồn kho</p>
    </main>
</div>
</body>
</html>
