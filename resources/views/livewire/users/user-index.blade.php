{{--
    UC06.01 — Quản lý tài khoản (view của App\Livewire\Users\UserIndex). Bố cục theo bản mock "Người dùng": thanh công cụ
    (tìm kiếm, lọc vai trò, lọc trạng thái, nút "+ Tạo tài khoản"), bảng Họ tên / Email / Vai trò / Trạng thái / Đăng nhập gần nhất /
    thao tác (Sửa, Khóa hoặc Mở khóa, Đặt lại MK), ghi chú quy tắc bên dưới. Khuôn hộp thoại lấy từ màn hình Danh mục.
    Livewire cần MỘT phần tử gốc duy nhất.
--}}
<div>
    {{-- Thông báo kết quả thao tác --}}
    @if ($notice)
        <div role="alert" class="mb-4 flex items-start justify-between gap-3 rounded-md border px-4 py-3 text-sm {{ $noticeType === 'success' ? 'border-green-300 bg-green-50 text-green-800' : 'border-red-300 bg-red-50 text-red-800' }}">
            <span>{{ $notice }}</span>
            <button type="button" wire:click="dismissNotice" class="text-lg leading-none" aria-label="Đóng thông báo">×</button>
        </div>
    @endif

    {{-- Thanh công cụ --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm theo tên, email hoặc SĐT…"
               class="input w-full sm:w-64" aria-label="Tìm tài khoản">

        <select wire:model.live="roleFilter" class="input w-auto" aria-label="Lọc theo vai trò">
            <option value="">Vai trò: Tất cả</option>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}">{{ $role->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="statusFilter" class="input w-auto" aria-label="Lọc theo trạng thái">
            <option value="">Trạng thái: Tất cả</option>
            @foreach (\App\Enums\UserStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>

        @if ($keyword !== '' || $roleFilter !== '' || $statusFilter !== '')
            <button type="button" wire:click="clearFilters" class="btn btn-secondary">Xóa lọc</button>
        @endif

        <div class="ml-auto">
            <button type="button" wire:click="create" class="btn btn-primary">+ Tạo tài khoản</button>
        </div>
    </div>

    {{-- Bảng tài khoản: bấm tiêu đề để sắp xếp (cột nằm trong whitelist UserFilter::SORTABLE) --}}
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach (['name' => 'Họ tên', 'email' => 'Email'] as $column => $label)
                        <th scope="col" aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                                {{ $label }}
                                @if ($sort === $column)
                                    <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                    @endforeach
                    <th scope="col">Vai trò</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col" aria-sort="{{ $sort === 'last_login_at' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button type="button" wire:click="sortBy('last_login_at')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                            Đăng nhập gần nhất
                            @if ($sort === 'last_login_at')
                                <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}" class="{{ $user->isActive() ? '' : 'bg-slate-50 text-slate-500' }}">
                        <td class="whitespace-nowrap font-medium text-slate-900">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="ml-1 text-xs font-normal text-slate-500">(bạn)</span>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge {{ $user->isAdmin() ? 'badge-info' : 'badge-neutral' }}">{{ $user->role->label() }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $user->isActive() ? 'badge-success' : 'badge-danger' }}">{{ $user->status->label() }}</span>
                        </td>
                        <td class="whitespace-nowrap tabular-nums">
                            {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Chưa đăng nhập' }}
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <button type="button" wire:click="edit({{ $user->id }})" class="btn btn-secondary">Sửa</button>
                            @if ($user->isActive())
                                <button type="button" wire:click="confirmAction({{ $user->id }}, 'lock')" class="btn btn-danger-outline"
                                        @disabled($user->id === auth()->id())
                                        title="{{ $user->id === auth()->id() ? 'Không thể tự khóa tài khoản đang dùng' : 'Khóa tài khoản' }}">Khóa</button>
                            @else
                                <button type="button" wire:click="unlock({{ $user->id }})" class="btn btn-secondary">Mở khóa</button>
                            @endif
                            <button type="button" wire:click="confirmAction({{ $user->id }}, 'reset')" class="btn btn-secondary"
                                    @disabled($user->id === auth()->id())
                                    title="{{ $user->id === auth()->id() ? 'Đổi mật khẩu của bạn trong Hồ sơ cá nhân' : 'Đặt lại mật khẩu' }}">Đặt lại MK</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $roleFilter !== '' || $statusFilter !== '')
                                Không có tài khoản nào khớp bộ lọc.
                            @else
                                Chưa có tài khoản nào.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links('partials.pagination') }}
    </div>

    <p class="mt-3 text-xs text-slate-500">
        Không thể tự khóa tài khoản đang dùng hoặc hạ quyền / khóa Admin cuối cùng. Đặt lại mật khẩu sẽ buộc người dùng đổi mật khẩu
        ở lần đăng nhập sau và đăng xuất họ khỏi mọi thiết bị.
    </p>

    {{-- Hộp thoại tạo / sửa (BR06.01-3: mật khẩu tạm; BR06.01-4: email duy nhất; sửa thì không đổi email, không đặt mật khẩu) --}}
    @if ($showForm)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="dialog" aria-modal="true" aria-labelledby="user-form-title"
             wire:keydown.escape="closeForm"
             x-data x-init="$nextTick(() => $refs.name && $refs.name.focus())">
            <form wire:submit="save" class="card w-full max-w-lg space-y-4" novalidate autocomplete="off">
                <h2 id="user-form-title" class="text-lg font-semibold text-brand">
                    {{ $editingId === null ? 'Tạo tài khoản' : 'Sửa tài khoản' }}
                </h2>

                <div>
                    <label for="user-name" class="label">Họ tên <span class="text-red-600">*</span></label>
                    <input id="user-name" type="text" wire:model="name" x-ref="name" maxlength="100"
                           class="input {{ $errors->has('name') ? 'input-invalid' : '' }}">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="user-email" class="label">Email <span class="text-red-600">*</span></label>
                    <input id="user-email" type="email" wire:model="email" maxlength="150"
                           @disabled($editingId !== null)
                           class="input {{ $errors->has('email') ? 'input-invalid' : '' }}">
                    @if ($editingId !== null)
                        <p class="mt-1 text-xs text-slate-500">Email không đổi được sau khi tạo.</p>
                    @endif
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="user-role" class="label">Vai trò <span class="text-red-600">*</span></label>
                        <select id="user-role" wire:model="formRole" class="input {{ $errors->has('formRole') ? 'input-invalid' : '' }}">
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}">{{ $role->label() }}</option>
                            @endforeach
                        </select>
                        @error('formRole') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="user-phone" class="label">Số điện thoại</label>
                        <input id="user-phone" type="text" wire:model="phone" maxlength="20"
                               class="input {{ $errors->has('phone') ? 'input-invalid' : '' }}">
                        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if ($editingId === null)
                    <div>
                        <label for="user-password" class="label">Mật khẩu tạm <span class="text-red-600">*</span></label>
                        <input id="user-password" type="text" wire:model="password" autocomplete="off"
                               class="input {{ $errors->has('password') ? 'input-invalid' : '' }}">
                        <p class="mt-1 text-xs text-slate-500">
                            Ít nhất {{ config('inventory.auth.password_min_length', 8) }} ký tự, có chữ và số. Người dùng bị buộc đổi mật khẩu ở lần đăng nhập đầu tiên.
                        </p>
                        @error('password') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="btn btn-secondary">Hủy</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Lưu</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Hộp thoại xác nhận khóa / đặt lại mật khẩu --}}
    @if ($confirmingId !== null)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="user-confirm-title"
             wire:keydown.escape="cancelConfirm">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="user-confirm-title" class="text-lg font-semibold {{ $confirmingType === 'lock' ? 'text-red-700' : 'text-brand' }}">
                    {{ $confirmingType === 'lock' ? 'Khóa tài khoản' : 'Đặt lại mật khẩu' }}
                </h2>
                <p class="text-sm">
                    @if ($confirmingType === 'lock')
                        Khóa tài khoản <strong>{{ $confirmingName }}</strong>? Mọi phiên đăng nhập của họ sẽ bị thu hồi ngay; mở khóa lại được bất cứ lúc nào.
                    @else
                        Đặt lại mật khẩu của <strong>{{ $confirmingName }}</strong>? Hệ thống tạo mật khẩu tạm (chỉ hiển thị một lần), buộc họ đổi mật khẩu ở lần đăng nhập sau và đăng xuất họ khỏi mọi thiết bị.
                    @endif
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelConfirm" class="btn btn-secondary">Hủy</button>
                    <button type="button" wire:click="runConfirmed" wire:loading.attr="disabled" wire:target="runConfirmed"
                            class="btn {{ $confirmingType === 'lock' ? 'btn-danger' : 'btn-primary' }}">
                        {{ $confirmingType === 'lock' ? 'Khóa' : 'Đặt lại' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Mật khẩu tạm: hiển thị MỘT LẦN (BR06.01-9). Đóng hộp thoại là xóa khỏi trạng thái component, không lưu ở đâu khác. --}}
    @if ($revealedPassword !== '')
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true" aria-labelledby="user-reveal-title">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="user-reveal-title" class="text-lg font-semibold text-brand">Mật khẩu tạm</h2>
                <p class="text-sm">Mật khẩu tạm của <strong>{{ $revealedEmail }}</strong>:</p>
                <p class="select-all rounded bg-slate-100 px-3 py-2 text-center font-mono text-lg tracking-wide">{{ $revealedPassword }}</p>
                <p class="text-xs text-red-700">Mật khẩu chỉ hiển thị một lần này và không xem lại được. Hãy gửi cho người dùng ngay; họ sẽ phải đổi mật khẩu khi đăng nhập.</p>
                <div class="flex justify-end">
                    <button type="button" wire:click="closeReveal" class="btn btn-primary">Đã chép, đóng</button>
                </div>
            </div>
        </div>
    @endif
</div>
