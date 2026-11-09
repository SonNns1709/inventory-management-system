<?php

use App\Exceptions\BusinessConflictException;
use App\Exceptions\TooManyLoginAttemptsException;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);

        // Chưa đăng nhập -> trang đăng nhập; đã đăng nhập mà vào trang dành cho khách -> Dashboard
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Báo cáo Chương 3 §III: mọi endpoint dưới /api luôn trả JSON, kể cả khi client không gửi "Accept: application/json".
        // Khuôn lỗi: 422 { "message", "errors" } là mặc định của Laravel; 401/403/404/409/429 trả { "message" }.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Resource not found'], 404);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
        });

        // 409 Xung đột nghiệp vụ (hết tồn khi xuất, xóa danh mục còn sản phẩm, sửa phiếu đã xác nhận...).
        // Tầng Service chỉ cần ném BusinessConflictException; Controller không phải try/catch.
        $exceptions->render(function (BusinessConflictException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 409);
            }

            return redirect()->back()->with('error', $e->getMessage());
        });

        // 429 — đăng nhập sai quá 5 lần trong 15 phút (UC01.01, Exception Flow E3).
        // API: JSON { "message" } kèm header Retry-After; web: quay lại form đăng nhập kèm thông báo.
        $exceptions->render(function (TooManyLoginAttemptsException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 429, $e->getHeaders());
            }

            return redirect()->route('login')
                ->withInput($request->only('email'))
                ->withErrors(['email' => $e->getMessage()]);
        });

        // Mọi HttpException còn lại của API (403 từ middleware/Policy, 405, 501 của chức năng chưa cài...)
        // trả cùng một khuôn { "message": ... }. 403 luôn là "Forbidden" để không lộ chi tiết phân quyền.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();
            $message = $status === 403 ? 'Forbidden' : ($e->getMessage() !== '' ? $e->getMessage() : 'Error');

            return response()->json(['message' => $message], $status, $e->getHeaders());
        });
    })->create();
