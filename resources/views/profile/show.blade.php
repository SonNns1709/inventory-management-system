@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@section('content')
    <h1 class="page-title">Hồ sơ cá nhân</h1>
    <p class="page-subtitle">Thông tin tài khoản của bạn (UC01.04).</p>

    <div class="card max-w-2xl">
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="label">Họ tên</dt>
                <dd>{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="label">Email đăng nhập</dt>
                <dd>{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="label">Vai trò</dt>
                <dd>{{ $user->role->label() }}</dd>
            </div>
            <div>
                <dt class="label">Số điện thoại</dt>
                <dd>{{ $user->phone ?? '—' }}</dd>
            </div>
            <div>
                <dt class="label">Trạng thái</dt>
                <dd>{{ $user->status->label() }}</dd>
            </div>
            <div>
                <dt class="label">Đăng nhập gần nhất</dt>
                <dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('profile.edit') }}" class="btn btn-primary">Sửa hồ sơ</a>
            <a href="{{ route('profile.password.edit') }}" class="btn btn-secondary">Đổi mật khẩu</a>
        </div>
    </div>
@endsection
