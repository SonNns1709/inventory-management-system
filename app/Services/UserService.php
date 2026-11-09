<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\UserFilter;
use App\Models\User;
use App\Support\SessionRevoker;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * UC06.01 — Quản lý tài khoản người dùng (chỉ Admin).
 *
 *  - BR06.01-3/4: tạo tài khoản với mật khẩu tạm, email duy nhất; tài khoản mới buộc đổi mật khẩu ở lần đăng nhập đầu;
 *  - BR06.01-5/6: luôn còn ít nhất một Admin đang hoạt động — không khóa / hạ quyền Admin cuối cùng;
 *  - BR06.01-7: Admin không tự khóa mình; BR06.01-8: khóa thì thu hồi ngay mọi phiên;
 *  - BR06.01-9: đặt lại mật khẩu -> mật khẩu tạm hiển thị một lần, must_change_password = true;
 *  - BR06.01-10: mọi thao tác ghi Activity Log (không bao giờ ghi mật khẩu vào nhật ký).
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR06.01-2: Admin tạo tài khoản (createUser), cập nhật vai trò (update), khóa / mở khóa (toggleStatus).
 *  - BR01.04-3: chỉ Admin được đổi email và vai trò của tài khoản — thông qua update() này (UC06.01).
 */
class UserService
{
    public function __construct(private readonly ActivityLogService $logs) {}

    /** @param  array<string, mixed>  $params  keyword, role, status, sort, direction, per_page */
    public function list(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = UserFilter::valid($params);

        return UserFilter::apply(User::query(), $filters)->paginate($perPage ?? UserFilter::perPage($filters));
    }

    public function find(int|string $id): User
    {
        return User::query()->findOrFail($id);
    }

    /** @param  array{name: string, email: string, role: string, phone?: string|null, password: string}  $data */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'], // cast "hashed" tự băm bcrypt (BR01.01-1)
            'status' => UserStatus::Active,
            'must_change_password' => true,
        ]);

        $this->logs->record(
            ActivityAction::UserCreated,
            $user,
            "Tạo tài khoản {$user->email} ({$user->role->label()})",
            ['new' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value, 'phone' => $user->phone]],
        );

        return $user;
    }

    /**
     * @param  array{name: string, role: string, phone?: string|null}  $data
     *
     * @throws BusinessConflictException hạ quyền Admin duy nhất còn lại (BR06.01-6)
     */
    public function update(User $user, array $data): User
    {
        $newRole = Role::from($data['role']);

        if ($user->isAdmin() && $newRole !== Role::Admin && $user->isActive() && $this->isLastActiveAdmin($user)) {
            throw new BusinessConflictException('Không thể hạ quyền Admin duy nhất còn lại');
        }

        $old = ['name' => $user->name, 'role' => $user->role->value, 'phone' => $user->phone];

        $user->fill(['name' => $data['name'], 'role' => $newRole, 'phone' => $data['phone'] ?? null])->save();

        $this->logs->record(
            ActivityAction::UserUpdated,
            $user,
            "Cập nhật tài khoản {$user->email}",
            ['old' => $old, 'new' => ['name' => $user->name, 'role' => $user->role->value, 'phone' => $user->phone]],
        );

        return $user;
    }

    /**
     * Khóa <-> mở khóa tài khoản.
     *
     * @throws BusinessConflictException tự khóa mình (BR06.01-7) hoặc khóa Admin duy nhất còn lại (BR06.01-6)
     */
    public function toggleStatus(User $user, User $actor): User
    {
        if (! $user->isActive()) {
            $user->forceFill(['status' => UserStatus::Active])->save();
            $this->logs->record(ActivityAction::UserUnlocked, $user, "Mở khóa tài khoản {$user->email}");

            return $user;
        }

        if ($user->is($actor)) {
            throw new BusinessConflictException('Bạn không thể tự khóa tài khoản đang đăng nhập');
        }

        if ($user->isAdmin() && $this->isLastActiveAdmin($user)) {
            throw new BusinessConflictException('Không thể khóa Admin duy nhất còn lại');
        }

        $user->forceFill(['status' => UserStatus::Locked])->save();
        SessionRevoker::revoke($user); // BR06.01-8: thu hồi ngay mọi phiên web và token API

        $this->logs->record(ActivityAction::UserLocked, $user, "Khóa tài khoản {$user->email}");

        return $user;
    }

    /**
     * BR06.01-9: tạo mật khẩu tạm (chữ + số, độ dài theo config/inventory.php), đặt must_change_password = true,
     * thu hồi các phiên cũ. Trả về mật khẩu dạng rõ để Controller hiển thị MỘT LẦN; không lưu / ghi log ở dạng rõ.
     */
    public function resetPassword(User $user): string
    {
        $temporary = Str::password((int) config('inventory.auth.temp_password_length', 10), true, true, false, false);

        $user->forceFill(['password' => $temporary, 'must_change_password' => true])->save();
        SessionRevoker::revoke($user);

        $this->logs->record(ActivityAction::UserPasswordReset, $user, "Đặt lại mật khẩu tài khoản {$user->email}");

        return $temporary;
    }

    /** Ngoài $user ra có còn Admin nào đang hoạt động không? (BR06.01-5, BR06.01-6) */
    private function isLastActiveAdmin(User $user): bool
    {
        return ! User::query()
            ->where('role', Role::Admin->value)
            ->where('status', UserStatus::Active->value)
            ->where('id', '!=', $user->getKey())
            ->exists();
    }
}
