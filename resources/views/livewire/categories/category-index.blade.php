{{--
    UC02.01 — Danh mục sản phẩm (view của App\Livewire\Categories\CategoryIndex). MÀN HÌNH THAM CHIẾU: các màn hình danh mục khác
    (Nhà cung cấp, Kho, Sản phẩm...) copy khuôn này — thanh công cụ, bảng có tiêu đề bấm để sắp xếp, hộp thoại thêm / sửa, hộp thoại
    xác nhận xóa, phân trang. Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
--}}
<div>
    @php($canManage = auth()->user()->can('create', \App\Models\Category::class))

    {{-- Thông báo kết quả thao tác (thành công / lỗi nghiệp vụ như BR02.01-3) --}}
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

    {{-- Thanh công cụ: tìm kiếm + số dòng mỗi trang + thêm mới --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm theo tên hoặc mô tả…"
               class="input max-w-xs" aria-label="Tìm danh mục">

        <div class="flex items-center gap-3">
            <select wire:model.live="perPage" class="input w-auto" aria-label="Số dòng mỗi trang">
                @foreach (config('inventory.pagination.options') as $option)
                    <option value="{{ $option }}">{{ $option }} dòng / trang</option>
                @endforeach
            </select>

            @if ($canManage)
                <button type="button" wire:click="create" class="btn btn-primary">+ Thêm danh mục</button>
            @endif
        </div>
    </div>

    {{-- Bảng danh sách: bấm tiêu đề cột để sắp xếp (cột nằm trong whitelist CategoryFilter::SORTABLE) --}}
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach (['id' => '#', 'name' => 'Tên danh mục'] as $column => $label)
                        <th scope="col" aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                                {{ $label }}
                                @if ($sort === $column)
                                    <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                    @endforeach
                    <th scope="col">Mô tả</th>
                    <th scope="col" class="text-right">Số sản phẩm</th>
                    @if ($canManage)
                        <th scope="col" class="text-right">Thao tác</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td class="text-slate-500">{{ $category->id }}</td>
                        <td class="font-medium text-slate-900">{{ $category->name }}</td>
                        <td class="text-slate-600">{{ $category->description ?? '—' }}</td>
                        <td class="text-right">{{ \App\Support\Format::number($category->products_count) }}</td>
                        @if ($canManage)
                            <td class="whitespace-nowrap text-right">
                                <button type="button" wire:click="edit({{ $category->id }})" class="btn btn-secondary">Sửa</button>
                                <button type="button" wire:click="confirmDelete({{ $category->id }})" class="btn btn-danger-outline">Xóa</button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 5 : 4 }}" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '')
                                Không có danh mục nào khớp "{{ $keyword }}".
                            @else
                                Chưa có danh mục nào.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links('partials.pagination') }}
    </div>

    {{-- Hộp thoại thêm / sửa (BR02.01-1: tên không trống; BR02.01-2: tên duy nhất) --}}
    @if ($showForm)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="dialog" aria-modal="true" aria-labelledby="category-form-title"
             wire:keydown.escape="closeForm"
             x-data x-init="$nextTick(() => $refs.name && $refs.name.focus())">
            <form wire:submit="save" class="card w-full max-w-lg space-y-4" novalidate>
                <h2 id="category-form-title" class="text-lg font-semibold text-brand">
                    {{ $editingId === null ? 'Thêm danh mục' : 'Sửa danh mục' }}
                </h2>

                <div>
                    <label for="category-name" class="label">Tên danh mục <span class="text-red-600">*</span></label>
                    <input id="category-name" type="text" wire:model="name" x-ref="name" maxlength="100"
                           @class(['input', 'input-invalid' => $errors->has('name')])>
                    @error('name')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category-description" class="label">Mô tả</label>
                    <textarea id="category-description" wire:model="description" rows="3" maxlength="255"
                              @class(['input', 'input-invalid' => $errors->has('description')])></textarea>
                    @error('description')
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

    {{-- Hộp thoại xác nhận xóa (BR02.01-3: còn sản phẩm thì Service từ chối -> thông báo lỗi phía trên) --}}
    @if ($deletingId !== null)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="category-delete-title"
             wire:keydown.escape="cancelDelete">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="category-delete-title" class="text-lg font-semibold text-red-700">Xóa danh mục</h2>
                <p class="text-sm">
                    Bạn có chắc muốn xóa danh mục <strong>{{ $deletingName }}</strong>? Thao tác này không thể hoàn tác.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="btn btn-secondary">Hủy</button>
                    <button type="button" wire:click="delete" class="btn btn-danger" wire:loading.attr="disabled" wire:target="delete">Xóa</button>
                </div>
            </div>
        </div>
    @endif
</div>
