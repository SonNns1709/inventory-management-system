@extends('layouts.app')

@section('title', 'Nhật ký hoạt động')

@section('content')
    <h1 class="page-title">Quản trị / Nhật ký hoạt động</h1>
    <p class="page-subtitle">
        Sổ ghi tự động mọi thao tác quan trọng trong hệ thống (UC06.02). Chỉ xem, không sửa hay xóa được.
    </p>

    {{-- Toàn bộ thao tác lọc / phân trang nằm trong Livewire component App\Livewire\ActivityLogs\ActivityLogIndex --}}
    <livewire:activity-logs.activity-log-index />
@endsection
