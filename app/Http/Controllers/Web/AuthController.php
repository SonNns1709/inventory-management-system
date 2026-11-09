<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Đăng nhập / đăng xuất trên giao diện web (phiên đăng nhập bằng session) — UC01.01, UC01.02.
 * Giống bản API (Api\AuthController) ở chỗ Controller chỉ lo "cách giữ đăng nhập"; mọi quy tắc nghiệp vụ
 * (khóa tài khoản, chống dò mật khẩu 5 lần / 15 phút, ghi Activity Log) nằm ở AuthService.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /** GET /login */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * POST /login — sai thông tin / tài khoản khóa: AuthService ném ValidationException (Laravel tự quay lại form kèm lỗi);
     * sai quá 5 lần: TooManyLoginAttemptsException (429, xem bootstrap/app.php).
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->auth->attempt($request->validated('email'), $request->validated('password'), $request->ip());

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // UC01.01 Alternative Flow A1: tài khoản mới / vừa được đặt lại mật khẩu phải đổi mật khẩu trước khi làm việc
        if ($user->must_change_password) {
            return redirect()->route('profile.password.edit')
                ->with('warning', 'Bạn cần đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.');
        }

        return redirect()->intended(route('dashboard'));
    }

    /** POST /logout — chỉ hủy phiên hiện tại (BR01.02-1). */
    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout($request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
