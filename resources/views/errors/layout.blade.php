{{--
    Khung chung cho các trang lỗi 403 / 404 / 419 / 429 / 500 / 503 (Laravel tự tìm resources/views/errors/{mã}.blade.php).
    Trang con khai báo các section: title, code, heading, message. Dùng bố cục khách để vẫn hiển thị được khi chưa có phiên đăng nhập.
--}}
@extends('layouts.guest')

@section('content')
    <div class="card text-center">
        <p class="text-5xl font-bold text-accent">@yield('code')</p>
        <h1 class="mt-3 text-xl font-semibold text-brand">@yield('heading')</h1>
        <p class="mt-2 text-sm text-slate-600">@yield('message')</p>

        <div class="mt-6 flex justify-center gap-2">
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Về trang chủ</a>
            <button type="button" onclick="history.back()" class="btn btn-secondary">Quay lại</button>
        </div>
    </div>
@endsection
