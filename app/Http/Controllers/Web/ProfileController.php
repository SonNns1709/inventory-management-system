<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\AuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Hồ sơ cá nhân và đổi mật khẩu — UC01.03, UC01.04 (cả hai vai trò, chỉ thao tác trên CHÍNH tài khoản của mình). */
class ProfileController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /** GET /profile */
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    /** GET /profile/edit — chỉ họ tên và số điện thoại được sửa (BR01.04-1, BR01.04-2). */
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /** PUT /profile */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->auth->updateProfile($request->user(), $request->validated());

        return redirect()->route('profile.show')->with('success', 'Cập nhật hồ sơ thành công.');
    }

    /** GET /profile/password — cũng là trang duy nhất dùng được khi must_change_password = true (EnsurePasswordChanged). */
    public function editPassword(Request $request): View
    {
        return view('profile.password', ['mustChange' => (bool) $request->user()->must_change_password]);
    }

    /**
     * PUT /profile/password — sai mật khẩu hiện tại / mật khẩu mới trùng cũ: AuthService ném ValidationException;
     * thành công thì thu hồi các phiên khác nhưng giữ phiên đang dùng (BR01.03-3).
     */
    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $this->auth->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
            keepSessionId: $request->session()->getId(),
        );

        return redirect()->route('dashboard')->with('success', 'Đổi mật khẩu thành công.');
    }
}
