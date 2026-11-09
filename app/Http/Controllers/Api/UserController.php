<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Quản lý tài khoản — UC06.01 (bảng API, nhóm 7: #56 – #60). Chỉ Admin (route đã có middleware role:admin, Policy là lớp thứ hai). */
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly UserService $users) {}

    /** GET /api/users (#56). Tham số: keyword, role, status, sort, direction, per_page, page. */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->successPaginated(UserResource::class, $this->users->list($request->query()));
    }

    /** GET /api/users/{user} (+) */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->success(UserResource::make($this->users->find($user->getKey())));
    }

    /** POST /api/users (#57) — mật khẩu tạm do Admin nhập; tài khoản mới buộc đổi mật khẩu ở lần đăng nhập đầu. */
    public function store(StoreUserRequest $request): JsonResponse
    {
        return $this->created(UserResource::make($this->users->createUser($request->validated())));
    }

    /** PUT /api/users/{user} (#58) — name, role, phone; không hạ quyền Admin cuối cùng (BR06.01-6). */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        return $this->success(UserResource::make($this->users->update($user, $request->validated())));
    }

    /** PATCH /api/users/{user}/toggle (#59) — active <-> locked; khóa thì thu hồi ngay mọi phiên (BR06.01-8). */
    public function toggle(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        return $this->success(UserResource::make($this->users->toggleStatus($user, $request->user())));
    }

    /**
     * POST /api/users/{user}/reset-password (#60) — cấp mật khẩu tạm (hiển thị MỘT LẦN trong phản hồi này),
     * đặt must_change_password = true và thu hồi các phiên cũ (BR06.01-9).
     */
    public function resetPassword(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $temporaryPassword = $this->users->resetPassword($user);

        return $this->success([
            'user' => UserResource::make($user->refresh()),
            'temporary_password' => $temporaryPassword,
        ]);
    }
}
