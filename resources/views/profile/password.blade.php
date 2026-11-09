@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')
    <h1 class="page-title">Đổi mật khẩu</h1>
    <p class="page-subtitle">
        Mật khẩu mới ít nhất {{ config('inventory.auth.password_min_length') }} ký tự, có cả chữ và số, và phải khác mật khẩu hiện tại
        (UC01.03). Sau khi đổi, các thiết bị khác sẽ bị đăng xuất.
    </p>

    {{-- Hiện khi người dùng bị buộc đổi mật khẩu (UC01.01 — A1) mà không đi qua redirect có sẵn thông báo --}}
    @if ($mustChange && ! session('warning'))
        <div role="alert" class="mb-4 max-w-xl rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Bạn cần đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.
        </div>
    @endif

    <form method="POST" action="{{ route('profile.password.update') }}" class="card max-w-xl space-y-4" novalidate>
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="label">Mật khẩu hiện tại <span class="text-red-600">*</span></label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                   @class(['input', 'input-invalid' => $errors->has('current_password')])>
            @error('current_password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="label">Mật khẩu mới <span class="text-red-600">*</span></label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                   @class(['input', 'input-invalid' => $errors->has('password')])>
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Nhập lại mật khẩu mới <span class="text-red-600">*</span></label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input">
        </div>

        <div class="flex justify-end gap-2">
            @unless ($mustChange)
                <a href="{{ route('profile.show') }}" class="btn btn-secondary">Hủy</a>
            @endunless
            <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
        </div>
    </form>
@endsection
