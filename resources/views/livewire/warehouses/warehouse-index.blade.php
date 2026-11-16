{{-- UC02.03 — Quản lý kho. Tổng tồn lấy từ WarehouseService::list() qua withSum('inventories', 'quantity'). --}}
<div>
    @php($canManage = auth()->user()->can('create', \App\Models\Warehouse::class))

    @if ($notice)
        <div role="alert" @class([
            'mb-4 flex items-start justify-between gap-3 rounded-md border px-4 py-3 text-sm',
            'border-green-300 bg-green-50 text-green-800' => $noticeType === 'success',
            'border-red-300 bg-red-50 text-red-800' => $noticeType !== 'success',
        ])>
            <span>{{ $notice }}</span>
            <button type="button" wire:click="dismissNotice" class="text-lg leading-none" aria-label="Đóng thông báo">×</button>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm theo mã, tên hoặc địa chỉ…"
               class="input max-w-xs" aria-label="Tìm kho">

        <select wire:model.live="status" class="input w-auto" aria-label="Lọc theo trạng thái kho">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang hoạt động</option>
            <option value="inactive">Ngừng hoạt động</option>
        </select>

        <div class="ml-auto flex items-center gap-3">
            <select wire:model.live="perPage" class="input w-auto" aria-label="Số dòng mỗi trang">
                @foreach (config('inventory.pagination.options') as $option)
                    <option value="{{ $option }}">{{ $option }} dòng / trang</option>
                @endforeach
            </select>

            @if ($canManage)
                <button type="button" wire:click="create" class="btn btn-primary">+ Thêm kho</button>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach (['code' => 'Mã kho', 'name' => 'Tên kho'] as $column => $label)
                        <th scope="col" aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                                {{ $label }}
                                @if ($sort === $column)
                                    <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                    @endforeach
                    <th scope="col">Địa chỉ</th>
                    <th scope="col" class="text-right">Tổng tồn</th>
                    <th scope="col">Trạng thái</th>
                    @if ($canManage)
                        <th scope="col" class="text-right">Thao tác</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($warehouses as $warehouse)
                    <tr wire:key="warehouse-{{ $warehouse->id }}">
                        <td class="whitespace-nowrap font-medium text-slate-900">{{ $warehouse->code }}</td>
                        <td class="text-slate-900">{{ $warehouse->name }}</td>
                        <td class="text-slate-600">{{ $warehouse->address ?: '—' }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($warehouse->total_stock ?? 0) }}</td>
                        <td>
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800' => $warehouse->is_active,
                                'bg-slate-200 text-slate-700' => ! $warehouse->is_active,
                            ])>{{ $warehouse->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span>
                        </td>
                        @if ($canManage)
                            <td class="whitespace-nowrap text-right">
                                <button type="button" wire:click="edit({{ $warehouse->id }})" class="btn btn-secondary">Sửa</button>
                                <button type="button" wire:click="toggle({{ $warehouse->id }})" class="btn btn-secondary"
                                        title="{{ $warehouse->is_active ? 'Ngừng hoạt động (chỉ khi tổng tồn bằng 0)' : 'Kích hoạt kho' }}"
                                        wire:loading.attr="disabled" wire:target="toggle({{ $warehouse->id }})">
                                    {{ $warehouse->is_active ? 'Ngừng' : 'Kích hoạt' }}
                                </button>
                                <button type="button" wire:click="confirmDelete({{ $warehouse->id }})" class="btn btn-danger-outline">Xóa</button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 6 : 5 }}" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $status !== '')
                                Không tìm thấy kho phù hợp.
                            @else
                                Chưa có kho nào.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $warehouses->links('partials.pagination') }}
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="dialog" aria-modal="true" aria-labelledby="warehouse-form-title"
             wire:keydown.escape="closeForm"
             x-data x-init="$nextTick(() => $refs.code && $refs.code.focus())">
            <form wire:submit="save" class="card w-full max-w-lg space-y-4" novalidate>
                <h2 id="warehouse-form-title" class="text-lg font-semibold text-brand">
                    {{ $editingId === null ? 'Thêm kho' : 'Sửa kho' }}
                </h2>

                <div>
                    <label for="warehouse-code" class="label">Mã kho <span class="text-red-600">*</span></label>
                    <input id="warehouse-code" type="text" wire:model="code" x-ref="code" maxlength="20"
                           class="input uppercase @error('code') input-invalid @enderror">
                    @error('code')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="warehouse-name" class="label">Tên kho <span class="text-red-600">*</span></label>
                    <input id="warehouse-name" type="text" wire:model="name" maxlength="100"
                           class="input @error('name') input-invalid @enderror">
                    @error('name')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="warehouse-address" class="label">Địa chỉ</label>
                    <input id="warehouse-address" type="text" wire:model="address" maxlength="255"
                           class="input @error('address') input-invalid @enderror">
                    @error('address')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="btn btn-secondary">Hủy</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Lưu</button>
                </div>
            </form>
        </div>
    @endif

    @if ($deletingId !== null)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="warehouse-delete-title"
             wire:keydown.escape="cancelDelete">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="warehouse-delete-title" class="text-lg font-semibold text-red-700">Xóa kho</h2>
                <p class="text-sm">
                    Bạn có chắc muốn xóa <strong>{{ $deletingName }}</strong>? Chỉ xóa được kho chưa từng có tồn kho hoặc chứng từ.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="btn btn-secondary">Hủy</button>
                    <button type="button" wire:click="delete" class="btn btn-danger" wire:loading.attr="disabled" wire:target="delete">Xóa</button>
                </div>
            </div>
        </div>
    @endif
</div>
