<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Exceptions\TooManyLoginAttemptsException;
use App\Models\User;
use App\Support\SessionRevoker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Xác thực và hồ sơ cá nhân — nhóm UC01 của báo cáo (UC01.01 – UC01.04).
 *
 * Dùng chung cho cả trang web (phiên đăng nhập) và REST API (token Sanctum): Controller chỉ lo phần
 * "cách giữ đăng nhập" (session hay token), mọi quy tắc nghiệp vụ nằm ở đây.
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR01.02-2: đăng xuất thu hồi token / phiên hiện tại (logout()); muốn truy cập tiếp phải đăng nhập lại.
 */
class AuthService
{
    public function __construct(private readonly ActivityLogService $logs) {}

    /**
     * UC01.01 — kiểm tra email + mật khẩu và trả về tài khoản hợp lệ.
     *
     * Thứ tự kiểm tra:
     *  1. E3 / BR01.01-2: quá 5 lần sai trong 15 phút (mỗi cặp email + IP) -> TooManyLoginAttemptsException (HTTP 429);
     *  2. E1: sai email hoặc mật khẩu -> ghi login_failed, tăng bộ đếm, báo "Email hoặc mật khẩu không đúng";
     *  3. E2: đúng mật khẩu nhưng tài khoản bị khóa -> báo "Tài khoản đã bị khóa, vui lòng liên hệ quản trị viên";
     *  4. thành công: xóa bộ đếm, cập nhật last_login_at, ghi login_success.
     *
     * Việc tạo phiên (web) hoặc cấp token (API) và rẽ nhánh "buộc đổi mật khẩu" (A1) do Controller đảm nhiệm.
     *
     * @throws ValidationException sai thông tin đăng nhập hoặc tài khoản bị khóa (HTTP 422 / quay lại form)
     * @throws TooManyLoginAttemptsException
     */
    public function attempt(string $email, string $password, ?string $ip = null): User
    {
        $ip ??= request()->ip();
        $key = $this->throttleKey($email, (string) $ip);
        $maxAttempts = (int) config('inventory.auth.login_max_attempts', 5);
        $decaySeconds = (int) config('inventory.auth.login_decay_minutes', 15) * 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->logs->record(
                ActivityAction::LoginFailed,
                'users',
                "Đăng nhập bị chặn do sai quá {$maxAttempts} lần: {$email}",
                ['email' => $email, 'reason' => 'throttled'],
                null,
                $ip,
            );

            throw new TooManyLoginAttemptsException(RateLimiter::availableIn($key));
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($key, $decaySeconds);

            $this->logs->record(
                ActivityAction::LoginFailed,
                $user,
                "Đăng nhập sai: {$email}",
                ['email' => $email, 'reason' => 'invalid_credentials'],
                $user,
                $ip,
            );

            throw ValidationException::withMessages(['email' => ['Email hoặc mật khẩu không đúng']]);
        }

        if (! $user->isActive()) {
            $this->logs->record(
                ActivityAction::LoginFailed,
                $user,
                "Tài khoản bị khóa cố đăng nhập: {$email}",
                ['email' => $email, 'reason' => 'locked'],
                $user,
                $ip,
            );

            throw ValidationException::withMessages(['email' => ['Tài khoản đã bị khóa, vui lòng liên hệ quản trị viên']]);
        }

        RateLimiter::clear($key);

        $user->forceFill(['last_login_at' => now()])->save();

        $this->logs->record(ActivityAction::LoginSuccess, $user, 'Đăng nhập thành công', null, $user, $ip);

        return $user;
    }

    /**
     * UC01.02 — đăng xuất: chỉ hủy phiên/token HIỆN TẠI, không ảnh hưởng thiết bị khác (BR01.02-1).
     *
     * @param  PersonalAccessToken|null  $token  token API đang dùng (web: null — Controller tự hủy phiên)
     */
    public function logout(User $user, ?PersonalAccessToken $token = null): void
    {
        $token?->delete();

        $this->logs->record(ActivityAction::Logout, $user, 'Đăng xuất', null, $user);
    }

    /**
     * UC01.03 — đổi mật khẩu.
     *
     * E1: mật khẩu hiện tại sai -> lỗi ở trường current_password, không đổi gì.
     * BR01.03-1: mật khẩu mới không được trùng mật khẩu hiện tại.
     * BR01.03-2: độ mạnh (>= 8 ký tự, có chữ và số) do Form Request kiểm tra bằng Password::defaults().
     * Basic Flow 4: lưu mật khẩu mới (cast "hashed" tự băm bcrypt) và đặt must_change_password = false.
     * BR01.03-3: thu hồi các phiên khác của tài khoản (giữ lại phiên/token đang dùng).
     *
     * @throws ValidationException
     */
    public function changePassword(
        User $user,
        string $currentPassword,
        string $newPassword,
        ?string $keepSessionId = null,
        ?int $keepTokenId = null,
    ): void {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Mật khẩu hiện tại không đúng']]);
        }

        if (Hash::check($newPassword, $user->password)) {
            throw ValidationException::withMessages(['password' => ['Mật khẩu mới không được trùng với mật khẩu hiện tại']]);
        }

        $user->forceFill(['password' => $newPassword, 'must_change_password' => false])->save();

        SessionRevoker::revoke($user, $keepSessionId, $keepTokenId);

        $this->logs->record(ActivityAction::PasswordChanged, $user, 'Đổi mật khẩu', null, $user);
    }

    /**
     * UC01.04 — cập nhật hồ sơ cá nhân. Chỉ họ tên và số điện thoại được sửa;
     * email và vai trò bị bỏ qua ngay cả khi client cố gửi lên (BR01.04-1, BR01.04-2).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $allowed = array_intersect_key($data, array_flip(['name', 'phone']));
        $old = $user->only(['name', 'phone']);

        $user->fill($allowed)->save();

        $this->logs->record(
            ActivityAction::ProfileUpdated,
            $user,
            'Cập nhật hồ sơ cá nhân',
            ['old' => $old, 'new' => $user->only(['name', 'phone'])],
            $user,
        );

        return $user;
    }

    /** Khóa đếm đăng nhập sai: theo từng cặp email + IP (BR01.01-2). */
    private function throttleKey(string $email, string $ip): string
    {
        return 'login|'.Str::lower(Str::transliterate($email)).'|'.$ip;
    }
}
