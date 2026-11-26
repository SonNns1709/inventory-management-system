<div>
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
        <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm theo SKU hoặc tên…"
               class="input max-w-xs" aria-label="Tìm sản phẩm">

        <select wire:model.live="categoryId" class="input w-auto" aria-label="Lọc theo danh mục">
            <option value="">Tất cả danh mục</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="status" class="input w-auto" aria-label="Lọc theo trạng thái kinh doanh">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang kinh doanh</option>
            <option value="inactive">Ngừng kinh doanh</option>
        </select>

        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model.live="lowStock" class="rounded border-slate-300">
            Tồn thấp
        </label>

        <div class="ml-auto flex items-center gap-3">
            <select wire:model.live="perPage" class="input w-auto" aria-label="Số dòng mỗi trang">
                @foreach (config('inventory.pagination.options') as $option)
                    <option value="{{ $option }}">{{ $option }} dòng / trang</option>
                @endforeach
            </select>

            @if ($canManage)
                <button type="button" wire:click="create" class="btn btn-primary">+ Thêm sản phẩm</button>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach (['sku' => 'SKU', 'name' => 'Tên sản phẩm'] as $column => $label)
                        <th scope="col" aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                                {{ $label }}
                                @if ($sort === $column)
                                    <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                    @endforeach
                    <th scope="col">Danh mục</th>
                    <th scope="col">ĐVT</th>
                    <th scope="col" class="text-right">Giá bán</th>
                    <th scope="col" class="text-right">Tồn</th>
                    <th scope="col" class="text-right">Ngưỡng</th>
                    <th scope="col">Trạng thái</th>
                    @if ($canManage)
                        <th scope="col" class="text-right">Thao tác</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php($stock = (int) ($product->total_stock ?? 0))
                    @php($isLowStock = $stock < (int) $product->min_stock_level)
                    <tr wire:key="product-{{ $product->id }}" @class(['bg-amber-50/70' => $isLowStock])>
                        <td class="whitespace-nowrap font-medium text-slate-900">
                            <a href="{{ route('products.show', $product) }}" class="text-brand underline-offset-2 hover:underline">{{ $product->sku }}</a>
                        </td>
                        <td class="font-medium text-slate-900">
                            <a href="{{ route('products.show', $product) }}" class="underline-offset-2 hover:underline">{{ $product->name }}</a>
                            @if ($isLowStock)
                                <span class="ml-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Tồn thấp</span>
                            @endif
                        </td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td>{{ $product->unit }}</td>
                        <td class="whitespace-nowrap text-right">{{ \App\Support\Format::money($product->price) }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($stock) }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($product->min_stock_level) }}</td>
                        <td>
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800' => $product->is_active,
                                'bg-slate-200 text-slate-700' => ! $product->is_active,
                            ])>{{ $product->is_active ? 'Đang kinh doanh' : 'Ngừng kinh doanh' }}</span>
                        </td>
                        @if ($canManage)
                            <td class="whitespace-nowrap text-right">
                                <button type="button" wire:click="edit({{ $product->id }})" class="btn btn-secondary">Sửa</button>
                                <button type="button" wire:click="confirmToggle({{ $product->id }})" class="btn btn-secondary">
                                    {{ $product->is_active ? 'Ngừng' : 'Kinh doanh lại' }}
                                </button>
                                <button type="button" wire:click="confirmDelete({{ $product->id }})" class="btn btn-danger-outline">Xóa</button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 9 : 8 }}" class="py-10 text-center text-slate-500">
                            Không tìm thấy sản phẩm
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links('partials.pagination') }}
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="dialog" aria-modal="true" aria-labelledby="product-form-title"
             wire:keydown.escape="closeForm" x-data x-init="$nextTick(() => $refs.sku && $refs.sku.focus())">
            <form wire:submit="save" class="card max-h-full w-full max-w-3xl space-y-4 overflow-y-auto" novalidate>
                <h2 id="product-form-title" class="text-lg font-semibold text-brand">
                    {{ $editingId === null ? 'Thêm sản phẩm' : 'Sửa sản phẩm' }}
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="product-sku" class="label">SKU <span class="text-red-600">*</span></label>
                        <input id="product-sku" type="text" wire:model="sku" x-ref="sku" maxlength="30"
                               class="input uppercase @error('sku') input-invalid @enderror">
                        @error('sku')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="product-name" class="label">Tên sản phẩm <span class="text-red-600">*</span></label>
                        <input id="product-name" type="text" wire:model="name" maxlength="200"
                               class="input @error('name') input-invalid @enderror">
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="product-category" class="label">Danh mục <span class="text-red-600">*</span></label>
                        <select id="product-category" wire:model="category_id" class="input @error('category_id') input-invalid @enderror">
                            <option value="">Chọn danh mục</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="product-unit" class="label">Đơn vị tính <span class="text-red-600">*</span></label>
                        <input id="product-unit" type="text" wire:model="unit" maxlength="20"
                               class="input @error('unit') input-invalid @enderror">
                        @error('unit')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="product-price" class="label">Giá bán</label>
                        <input id="product-price" type="number" wire:model="price" min="0" step="0.01"
                               class="input @error('price') input-invalid @enderror">
                        @error('price')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="product-min-stock" class="label">Ngưỡng tồn thấp <span class="text-red-600">*</span></label>
                        <input id="product-min-stock" type="number" wire:model="min_stock_level" min="0" step="1"
                               class="input @error('min_stock_level') input-invalid @enderror">
                        @error('min_stock_level')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="product-description" class="label">Mô tả</label>
                        <textarea id="product-description" wire:model="description" rows="3" maxlength="5000"
                                  class="input @error('description') input-invalid @enderror"></textarea>
                        @error('description')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if ($editingId !== null)
                    <p class="text-xs text-slate-500">Chỉ đổi được SKU khi sản phẩm chưa có giao dịch/thẻ kho.</p>
                @else
                    <p class="text-xs text-slate-500">Tồn ban đầu bằng 0; hàng được ghi nhận khi xác nhận phiếu nhập.</p>
                @endif

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="btn btn-secondary">Hủy</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Lưu</button>
                </div>
            </form>
        </div>
    @endif

    @if ($togglingId !== null)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="product-toggle-title" wire:keydown.escape="cancelToggle">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="product-toggle-title" class="text-lg font-semibold text-brand">
                    {{ $toggleWillDeactivate ? 'Ngừng kinh doanh sản phẩm' : 'Kinh doanh lại sản phẩm' }}
                </h2>
                <p class="text-sm">
                    {{ $toggleWillDeactivate ? 'Bạn có chắc muốn ngừng kinh doanh' : 'Bạn có chắc muốn kinh doanh lại' }}
                    <strong>{{ $togglingName }}</strong>?
                    Lịch sử tồn kho và thẻ kho vẫn được giữ nguyên.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelToggle" class="btn btn-secondary">Hủy</button>
                    <button type="button" wire:click="toggle" class="btn btn-primary" wire:loading.attr="disabled" wire:target="toggle">Xác nhận</button>
                </div>
            </div>
        </div>
    @endif

    @if ($deletingId !== null)
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="product-delete-title" wire:keydown.escape="cancelDelete">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="product-delete-title" class="text-lg font-semibold text-red-700">Xóa sản phẩm</h2>
                <p class="text-sm">
                    Bạn có chắc muốn xóa <strong>{{ $deletingName }}</strong>? Sản phẩm đã có giao dịch hoặc còn tồn không thể xóa; hãy ngừng kinh doanh thay thế.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="btn btn-secondary">Hủy</button>
                    <button type="button" wire:click="delete" class="btn btn-danger" wire:loading.attr="disabled" wire:target="delete">Xóa</button>
                </div>
            </div>
        </div>
    @endif
</div>
