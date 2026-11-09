<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Chặn tài khoản đã bị khóa (users.status = 'locked') NGAY TRONG LÚC đang đăng nhập.
     *
     * Việc kiểm tra lúc đăng nhập (AuthService) chỉ chặn được người chưa đăng nhập; nếu Admin khóa một
     * tài khoản đang có phiên làm việc (hoặc đang giữ token API) thì không có middleware này người đó
     * vẫn dùng tiếp được cho tới khi tự đăng xuất — trái BR06.01-8 ("khóa tài khoản phải thu hồi ngay
     * các phiên đăng nhập hiện có").
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isActive()) {
            return $next($request);
        }

        // API (token): từ chối bằng JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'Account is locked'], 403);
        }

        // Web: đăng xuất ngay rồi báo lý do ở trang đăng nhập
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => 'Tài khoản đã bị khóa, vui lòng liên hệ quản trị viên.']);
    }
}
