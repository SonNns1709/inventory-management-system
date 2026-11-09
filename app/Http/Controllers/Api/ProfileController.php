<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Hồ sơ cá nhân — UC01.04 (bảng API, nhóm 1: #5, #6). */
class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth) {}

    /** GET /api/profile (#5) */
    public function show(Request $request): JsonResponse
    {
        return $this->success(UserResource::make($request->user()));
    }

    /**
     * PUT /api/profile (#6) — chỉ họ tên và số điện thoại; email và vai trò không bao giờ được sửa ở đây
     * (BR01.04-1, BR01.04-2: UpdateProfileRequest không nhận hai trường đó, AuthService cũng loại bỏ lần nữa).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        return $this->success(UserResource::make($this->auth->updateProfile($request->user(), $request->validated())));
    }
}
