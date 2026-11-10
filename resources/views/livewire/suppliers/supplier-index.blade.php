{{--
    UC02.02 — Nhà cung cấp (view của App\Livewire\Suppliers\SupplierIndex), bố cục theo mockup supplier.html.
    Danh sách hiển thị đủ các trường của form thêm / sửa. Livewire yêu cầu MỘT phần tử gốc duy nhất.
--}}
<div>
    @php
        $canManage = auth()->user()->can('create', \App\Models\Supplier::class);

        // Hộp thoại thêm / sửa: 3 hàng x 2 cột như mockup. Thứ tự khai báo = thứ tự hiển thị.
        $formFields = [
            'name' => ['label' => 'Tên nhà cung cấp', 'type' => 'text', 'max' => 150],
            'contact_person' => ['label' => 'Người liên hệ', 'type' => 'text', 'max' => 100],
            'phone' => ['label' => 'Điện thoại', 'type' => 'tel', 'max' => 20],
            'email' => ['label' => 'Email', 'type' => 'email', 'max' => 150],
            'address' => ['label' => 'Địa chỉ', 'type' => 'text', 'max' => 255],
            'tax_code' => ['label' => 'Mã số thuế', 'type' => 'text', 'max' => 20],
        ];
    @endphp

    {{-- Thông báo kết quả thao tác (thành công / lỗi nghiệp vụ như BR02.02-3) --}}
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

    {{-- Thanh công cụ: tìm kiếm + lọc trạng thái (trái) — số dòng / trang + thêm mới (phải) --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm theo tên hoặc SĐT…"
               class="input max-w-xs" aria-label="Tìm nhà cung cấp">

        <select wire:model.live="status" class="input w-auto" aria-label="Lọc theo trạng thái hợp tác">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang hợp tác</option>
            <option value="inactive">Ngừng hợp tác</option>
        </select>

        <div class="ml-auto flex items-center gap-3">
            <select wire:model.live="perPage" class="input w-auto" aria-label="Số dòng mỗi trang">
                @foreach (config('inventory.pagination.options') as $option)
                    <option value="{{ $option }}">{{ $option }} dòng / trang</option>
                @endforeach
            </select>

            @if ($canManage)
                <button type="button" wire:click="create" class="btn btn-primary">+ Thêm nhà cung cấp</button>
            @endif
        </div>
    </div>

    {{-- Bảng danh sách: chỉ cột "Nhà cung cấp" bấm để sắp xếp (nằm trong whitelist SupplierFilter::SORTABLE) --}}
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col" aria-sort="{{ $sort === 'name' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button type="button" wire:click="sortBy('name')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                            Nhà cung cấp
                            @if ($sort === 'name')
                                <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th scope="col">Người liên hệ</th>
                    <th scope="col">Điện thoại</th>
                    <th scope="col">Email</th>
                    <th scope="col">Địa chỉ</th>
                    <th scope="col">Mã số thuế</th>
                    <th scope="col">Trạng thái</th>
                    @if ($canManage)
                        <th scope="col" class="text-right">Thao tác</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr wire:key="supplier-{{ $supplier->id }}">
                        <td class="font-medium text-slate-900">{{ $supplier->name }}</td>
                        <td class="text-slate-600">{{ $supplier->contact_person ?: '—' }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ $supplier->phone ?: '—' }}</td>
                        <td class="text-slate-600">{{ $supplier->email ?: '—' }}</td>
                        <td class="min-w-48 text-slate-600">{{ $supplier->address ?: '—' }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ $supplier->tax_code ?: '—' }}</td>
                        <td>
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800' => $supplier->is_active,
                                'bg-slate-200 text-slate-700' => ! $supplier->is_active,
                            ])>{{ $supplier->is_active ? 'Hợp tác' : 'Ngừng' }}</span>
                        </td>
                        @if ($canManage)
                            <td class="whitespace-nowrap text-right">
                                <button type="button" wire:click="edit({{ $supplier->id }})" class="btn btn-secondary">Sửa</button>
                                <button type="button" wire:click="toggle({{ $supplier->id }})" class="btn btn-secondary"
                                        title="{{ $supplier->is_active ? 'Ngừng hợp tác' : 'Kích hoạt hợp tác' }}"
                                        wire:loading.attr="disabled" wire:target="toggle({{ $supplier->id }})">
                                    {{ $supplier->is_active ? 'Ngừng' : 'Kích hoạt' }}
                                </button>
                                <button type="button" wire:click="confirmDelete({{ $supplier->id }})" class="btn btn-danger-outline">Xóa</button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 8 : 7 }}" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $status !== '')
                                Không tìm thấy nhà cung cấp phù hợp.
                            @else
                                Chưa có nhà cung cấp nào.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $suppliers->links('partials.pagination') }}
    </div>

    {{-- Hộp thoại thêm / sửa (BR02.02-1: tên duy nhất; BR02.02-2: thông tin hợp lệ) --}}
    @if ($showForm)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="dialog" aria-modal="true" aria-labelledby="supplier-form-title"
             wire:keydown.escape="closeForm"
             x-data x-init="$nextTick(() => $refs.name && $refs.name.focus())">
            <form wire:submit="save" class="card max-h-full w-full max-w-xl space-y-4 overflow-y-auto" novalidate>
                <h2 id="supplier-form-title" class="text-lg font-semibold text-brand">
                    {{ $editingId === null ? 'Thêm nhà cung cấp' : 'Sửa nhà cung cấp' }}
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($formFields as $field => $spec)
                        <div wire:key="supplier-field-{{ $field }}">
                            <label for="supplier-{{ $field }}" class="label">
                                {{ $spec['label'] }}
                                @if ($field === 'name')<span class="text-red-600">*</span>@endif
                            </label>
                            <input id="supplier-{{ $field }}" type="{{ $spec['type'] }}" wire:model="{{ $field }}"
                                   maxlength="{{ $spec['max'] }}"
                                   @if ($field === 'name') x-ref="name" @endif
                                   @class(['input', 'input-invalid' => $errors->has($field)])>
                            @error($field)
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="btn btn-secondary">Hủy</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Lưu</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Hộp thoại xác nhận xóa (BR02.02-3/4: đã có phiếu nhập thì Service từ chối -> thông báo lỗi phía trên) --}}
    @if ($deletingId !== null)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="supplier-delete-title"
             wire:keydown.escape="cancelDelete">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="supplier-delete-title" class="text-lg font-semibold text-red-700">Xóa nhà cung cấp</h2>
                <p class="text-sm">
                    Bạn có chắc muốn xóa <strong>{{ $deletingName }}</strong>? Nếu đã có phiếu nhập, hãy dùng "Ngừng" thay vì xóa.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="btn btn-secondary">Hủy</button>
                    <button type="button" wire:click="delete" class="btn btn-danger" wire:loading.attr="disabled" wire:target="delete">Xóa</button>
                </div>
            </div>
        </div>
    @endif
</div>