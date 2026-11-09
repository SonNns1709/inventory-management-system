<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Các route vẫn được dùng khi must_change_password = true (UC01.01 — Alternative Flow A1):
     * người dùng phải được vào màn hình đổi mật khẩu, đăng xuất, hoặc (API) xem thông tin/đổi mật khẩu.
     */
    private const ALLOWED_ROUTES = [
        'profile.password.edit',
        'profile.password.update',
        'logout',
        'api.auth.password',
        'api.auth.logout',
        'api.auth.me',
    ];

    /**
     * Tài khoản vừa được Admin tạo hoặc vừa được đặt lại mật khẩu (users.must_change_password = 1)
     * không được vào Dashboard hay chức năng nghiệp vụ trước khi đổi mật khẩu.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'Password change required'], 403);
        }

        return redirect()->route('profile.password.edit')
            ->with('warning', 'Bạn cần đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.');
    }
}
