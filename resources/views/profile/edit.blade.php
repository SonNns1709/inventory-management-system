@extends('layouts.app')

@section('title', 'Sửa hồ sơ')

@section('content')
    <h1 class="page-title">Sửa hồ sơ cá nhân</h1>
    <p class="page-subtitle">Chỉ họ tên và số điện thoại được sửa; email và vai trò do Admin quản lý (BR01.04-1, BR01.04-2).</p>

    <form method="POST" action="{{ route('profile.update') }}" class="card max-w-xl space-y-4" novalidate>
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="label">Họ tên <span class="text-red-600">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="100"
                   @class(['input', 'input-invalid' => $errors->has('name')])>
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="phone" class="label">Số điện thoại</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" maxlength="20"
                   @class(['input', 'input-invalid' => $errors->has('phone')])>
            @error('phone')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label">Email đăng nhập</label>
            <input type="text" value="{{ $user->email }}" class="input bg-slate-100" disabled>
        </div>

        <div>
            <label class="label">Vai trò</label>
            <input type="text" value="{{ $user->role->label() }}" class="input bg-slate-100" disabled>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('profile.show') }}" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
    </form>
@endsection
