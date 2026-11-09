<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiLoginRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Xác thực bằng token Sanctum — UC01.01 – UC01.03 (bảng API, nhóm 1: #1 – #4).
 * Controller chỉ lo phần "giữ đăng nhập bằng token"; quy tắc nghiệp vụ nằm ở AuthService.
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth) {}

    /**
     * POST /api/auth/login (#1) — đổi email + mật khẩu lấy access token.
     * Sai thông tin / tài khoản bị khóa: 422; sai quá 5 lần trong 15 phút (email + IP): 429 kèm Retry-After.
     */
    public function login(ApiLoginRequest $request): JsonResponse
    {
        $user = $this->auth->attempt($request->validated('email'), $request->validated('password'), $request->ip());

        return $this->success([
            'token' => $user->createToken($request->validated('device_name') ?? 'api')->plainTextToken,
            'token_type' => 'Bearer',
            // true: client phải đưa người dùng tới màn hình đổi mật khẩu (các API khác trả 403 cho tới khi đổi xong)
            'must_change_password' => (bool) $user->must_change_password,
            'user' => UserResource::make($user),
        ]);
    }

    /** POST /api/auth/logout (#2) — chỉ thu hồi token đang dùng, không ảnh hưởng thiết bị khác (BR01.02-1). */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user(), $this->currentToken($request));

        return $this->message('Đăng xuất thành công');
    }

    /** GET /api/auth/me (#3) — thông tin tài khoản đang đăng nhập. */
    public function me(Request $request): JsonResponse
    {
        return $this->success(UserResource::make($request->user()));
    }

    /** PUT /api/auth/password (#4) — đổi mật khẩu (UC01.03); thu hồi các token khác của tài khoản (BR01.03-3). */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->auth->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
            keepTokenId: $this->currentToken($request)?->getKey(),
        );

        return $this->message('Đổi mật khẩu thành công');
    }

    /** Token API đang dùng (null nếu request xác thực bằng phiên SPA của Sanctum, khi đó không có token để thu hồi). */
    private function currentToken(Request $request): ?PersonalAccessToken
    {
        $token = $request->user()->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }
}
