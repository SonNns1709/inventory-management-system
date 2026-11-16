@extends('layouts.app')

@section('title', 'Người dùng')

@section('content')
    <h1 class="page-title">Quản trị / Người dùng</h1>
    <p class="page-subtitle">
        Tạo, sửa, khóa tài khoản và cấp lại mật khẩu cho nhân viên (UC06.01). Chỉ Admin truy cập được.
    </p>

    {{-- Toàn bộ thao tác nằm trong Livewire component App\Livewire\Users\UserIndex --}}
    <livewire:users.user-index />
@endsection
