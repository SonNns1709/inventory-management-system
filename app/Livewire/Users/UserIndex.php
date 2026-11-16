<?php

namespace App\Livewire\Users;

use App\Enums\Role;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\UserFilter;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC06.01 — Quản lý tài khoản (view: resources/views/livewire/users/user-index.blade.php). Theo khuôn của CategoryIndex:
 *
 *   thao tác Livewire -> $this->authorize() (UserPolicy) -> luật kiểm tra dùng chung với API (StoreUserRequest / UpdateUserRequest)
 *                     -> UserService (nghiệp vụ BR06.01-3..10 + Activity Log) -> Eloquent
 *
 * Mọi thuộc tính public do trình duyệt gửi lên nên không tin được: các action đều nạp lại tài khoản từ CSDL và gọi lại Policy;
 * quy tắc "luôn còn một Admin hoạt động", "không tự khóa mình" do UserService kiểm tra (BusinessConflictException -> thông báo lỗi).
 */
class UserIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    // --- Tìm kiếm / lọc / sắp xếp (đồng bộ lên URL) ---
    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    #[Url(as: 'role', except: '')]
    public string $roleFilter = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public int $perPage = 20;

    // --- Form tạo / sửa (email và mật khẩu chỉ nhập khi tạo; sửa chỉ đổi họ tên, vai trò, SĐT — UpdateUserRequest) ---
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $formRole = 'staff';

    public string $phone = '';

    public string $password = '';

    // --- Hộp thoại xác nhận khóa / đặt lại mật khẩu ---
    public ?int $confirmingId = null;

    public string $confirmingType = '';

    public string $confirmingName = '';

    // --- Mật khẩu tạm sau khi đặt lại: hiển thị MỘT LẦN (BR06.01-9), xóa khỏi trạng thái khi đóng hộp thoại ---
    public string $revealedPassword = '';

    public string $revealedEmail = '';

    // --- Thông báo kết quả ---
    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);

        $this->perPage = (int) config('inventory.pagination.default', 20);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['keyword', 'roleFilter', 'statusFilter', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, UserFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'roleFilter', 'statusFilter']);
        $this->resetPage();
    }

    // ------------------------------------------------------------------ Tạo / sửa

    public function create(): void
    {
        $this->authorize('create', User::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);

        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->formRole = $user->role->value;
        $this->phone = (string) $user->phone;
        $this->showForm = true;
    }

    public function save(): void
    {
        $user = $this->editingId !== null ? User::query()->findOrFail($this->editingId) : null;

        if ($user === null) {
            $this->authorize('create', User::class);
        } else {
            $this->authorize('update', $user);
        }

        $this->name = trim($this->name);
        $this->email = mb_strtolower(trim($this->email));
        $this->phone = trim($this->phone);

        // Dùng lại luật của API; riêng "role" đổi tên thành "formRole" vì thuộc tính $role dễ nhầm với bộ lọc.
        $request = $user === null ? new StoreUserRequest : new UpdateUserRequest;
        $rules = $request->rules();
        $rules['formRole'] = $rules['role'];
        unset($rules['role']);
        $attributes = $request->attributes();
        $attributes['formRole'] = $attributes['role'];

        $validated = $this->validate($rules, $request->messages(), $attributes);

        $data = [
            'name' => $validated['name'],
            'role' => $validated['formRole'],
            'phone' => ($validated['phone'] ?? '') !== '' ? $validated['phone'] : null,
        ];

        $service = app(UserService::class);

        try {
            if ($user === null) {
                $service->createUser($data + ['email' => $validated['email'], 'password' => $validated['password']]);
                $this->flash('Đã tạo tài khoản. Người dùng phải đổi mật khẩu ở lần đăng nhập đầu tiên.');
            } else {
                $service->update($user, $data);
                $this->flash('Đã cập nhật tài khoản.');
            }
        } catch (BusinessConflictException $e) { // BR06.01-6: hạ quyền Admin duy nhất còn lại
            $this->flash($e->getMessage(), 'error');
        }

        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    // ------------------------------------------------------------------ Khóa / mở khóa / đặt lại mật khẩu

    /** Hỏi xác nhận trước khi khóa tài khoản hoặc đặt lại mật khẩu ($type: 'lock' | 'reset'). */
    public function confirmAction(int $id, string $type): void
    {
        abort_unless(in_array($type, ['lock', 'reset'], true), 422);

        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);

        $this->confirmingId = $user->id;
        $this->confirmingType = $type;
        $this->confirmingName = $user->name;
    }

    public function runConfirmed(): void
    {
        abort_if($this->confirmingId === null, 404);

        $user = User::query()->findOrFail($this->confirmingId);
        $this->authorize('update', $user);
        $type = $this->confirmingType;
        $this->cancelConfirm();

        $service = app(UserService::class);

        try {
            if ($type === 'lock') {
                $service->toggleStatus($user, auth()->user());
                $this->flash("Đã khóa tài khoản {$user->email}; mọi phiên đăng nhập của họ đã bị thu hồi.");
            } elseif ($user->is(auth()->user())) {
                // Đặt lại mật khẩu của chính mình sẽ thu hồi luôn phiên đang dùng: hướng dẫn dùng "Đổi mật khẩu" thay thế
                $this->flash('Hãy đổi mật khẩu của chính bạn trong trang Hồ sơ cá nhân.', 'error');
            } else {
                $this->revealedPassword = $service->resetPassword($user);
                $this->revealedEmail = $user->email;
            }
        } catch (BusinessConflictException $e) { // BR06.01-6, BR06.01-7
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function cancelConfirm(): void
    {
        $this->reset(['confirmingId', 'confirmingType', 'confirmingName']);
    }

    /** Mở khóa không cần hỏi lại (không mất dữ liệu, làm lại bằng "Khóa" được). */
    public function unlock(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);

        try {
            app(UserService::class)->toggleStatus($user, auth()->user());
            $this->flash("Đã mở khóa tài khoản {$user->email}.");
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function closeReveal(): void
    {
        $this->reset(['revealedPassword', 'revealedEmail']);
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        $users = app(UserService::class)->list([
            'keyword' => $this->keyword,
            'role' => $this->roleFilter !== '' ? $this->roleFilter : null,
            'status' => $this->statusFilter !== '' ? $this->statusFilter : null,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ]);

        return view('livewire.users.user-index', ['users' => $users, 'roles' => Role::cases()]);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'email', 'formRole', 'phone', 'password']);
        $this->resetValidation();
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}
