    @extends('layouts.auth')

    @section('title', 'Đăng nhập')

    {{-- Cột trái (chỉ hiện từ màn hình lớn): giới thiệu hệ thống. Nội dung nói đúng những gì hệ thống làm, không hứa "thời gian thực". --}}
    @section('aside')
        <p class="text-3xl font-bold tracking-wide">IMS<span class="text-accent">.Kho</span></p>
        <p class="mb-7 mt-3 text-lg">Hệ thống Quản lý hàng tồn kho</p>
        <ul class="space-y-2 text-sm leading-relaxed text-slate-200">
            <li>Theo dõi tồn kho theo từng kho và từng sản phẩm</li>
            <li>Cảnh báo tồn thấp theo ngưỡng của từng sản phẩm</li>
            <li>Phiếu nhập, xuất, kiểm kê có nhật ký truy vết</li>
            <li>Giá trị tồn kho theo bình quân gia quyền</li>
        </ul>
    @endsection

@section('content')
    <div class="card">
        <h1 class="text-center text-xl font-bold text-brand">Đăng nhập</h1>
        <p class="mb-5 mt-1 text-center text-sm text-slate-500">Hệ thống Quản lý hàng tồn kho</p>

        @include('partials.flash')

        {{-- novalidate: để thấy rõ thông báo lỗi tiếng Việt do server trả về (UC01.01 — E1, E2, E3) --}}
        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4" novalidate>
            @csrf

            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" autofocus
                       @class(['input', 'input-invalid' => $errors->has('email')])>
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="label">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="current-password"
                       @class(['input', 'input-invalid' => $errors->has('password')])>
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                Ghi nhớ đăng nhập
            </label>

            <button type="submit" class="btn btn-primary w-full">Đăng nhập</button>
        </form>

        {{-- Gợi ý tài khoản mẫu: chỉ hiện ở môi trường local (xem database/seeders/UserSeeder.php) --}}
        @if (app()->environment('local'))
            <div class="mt-5 rounded-md bg-slate-50 p-3 text-xs text-slate-600">
                <p class="mb-1 font-semibold">Tài khoản mẫu (môi trường local)</p>
                <p>Admin: <code>admin@inventory.test</code> / <code>Admin@123</code></p>
                <p>Nhân viên kho: <code>staff@inventory.test</code> / <code>Staff@123</code></p>
            </div>
        @endif
    </div>
@endsection
