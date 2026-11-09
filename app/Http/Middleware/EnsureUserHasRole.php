<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware `role:` — chỉ cho qua tài khoản có vai trò được khai báo ở route, ví dụ `role:admin`.
 *
 * BR01.01-3: người dùng chỉ truy cập được các chức năng của vai trò được cấp (Admin / Nhân viên kho); sai vai trò -> 403.
 */
class EnsureUserHasRole
{
    /**
     * Chỉ cho phép người dùng có một trong các vai trò truyền qua tham số middleware.
     * Ví dụ: ->middleware('role:admin') hoặc ->middleware('role:admin,staff')
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(...$roles)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
