{{--
    UC04.03 — Lập phiếu kiểm kê nháp (view của App\Livewire\Adjustments\AdjustmentCreate).
    Chọn kho + ngày + phạm vi; bấm "Lập phiếu" thì Service sinh các dòng kèm snapshot tồn hệ thống rồi chuyển sang trang nhập số đếm.
    Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
--}}
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

    <form wire:submit="save" class="card max-w-3xl space-y-4" novalidate>
        <h2 class="text-lg font-semibold text-brand">Lập phiếu kiểm kê và điều chỉnh tồn kho</h2>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="adj-warehouse" class="label">Kho kiểm kê <span class="text-red-600">*</span></label>
                <select id="adj-warehouse" wire:model="warehouse_id" @class(['input', 'input-invalid' => $errors->has('warehouse_id')])>
                    <option value="">— Chọn kho —</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
                @error('warehouse_id')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="adj-date" class="label">Ngày kiểm kê</label>
                <input id="adj-date" type="date" wire:model="adjustment_date" @class(['input', 'input-invalid' => $errors->has('adjustment_date')])>
                @error('adjustment_date')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Phạm vi kiểm kê: all | category | products --}}
        <fieldset class="space-y-3">
            <legend class="label">Phạm vi kiểm kê</legend>

            <label class="flex items-center gap-2 text-sm">
                <input type="radio" wire:model.live="scope" value="all"> Tất cả sản phẩm có tồn ở kho
            </label>

            <label class="flex items-center gap-2 text-sm">
                <input type="radio" wire:model.live="scope" value="category"> Theo danh mục sản phẩm
            </label>
            @if ($scope === 'category')
                <div class="pl-6">
                    <select wire:model="category_id" aria-label="Danh mục" @class(['input max-w-sm', 'input-invalid' => $errors->has('category_id')])>
                        <option value="">— Chọn danh mục —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <label class="flex items-center gap-2 text-sm">
                <input type="radio" wire:model.live="scope" value="products"> Chọn từng sản phẩm
            </label>
            @if ($scope === 'products')
                <div class="space-y-2 pl-6">
                    <input type="search" wire:model.live.debounce.300ms="productSearch" placeholder="Tìm theo SKU hoặc tên sản phẩm…"
                           class="input max-w-sm" aria-label="Tìm sản phẩm">

                    <div class="max-h-64 overflow-y-auto rounded-md border border-slate-200 p-2">
                        @forelse ($products as $product)
                            <label wire:key="product-{{ $product->id }}" class="flex items-center gap-2 px-1 py-1 text-sm hover:bg-slate-50">
                                <input type="checkbox" wire:model.live="product_ids" value="{{ $product->id }}">
                                <span class="text-slate-500">{{ $product->sku }}</span>
                                <span>{{ $product->name }}</span>
                            </label>
                        @empty
                            <p class="py-3 text-center text-sm text-slate-500">Không có sản phẩm nào khớp.</p>
                        @endforelse
                    </div>

                    <p class="text-xs text-slate-500">
                        Đã chọn {{ count($product_ids) }} sản phẩm. Danh sách hiển thị tối đa 50 dòng — dùng ô tìm kiếm để thu hẹp; các sản phẩm đã tick được giữ nguyên.
                    </p>

                    @error('product_ids')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                    @error('product_ids.*')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </fieldset>

        <div>
            <label for="adj-note" class="label">Ghi chú</label>
            <input id="adj-note" type="text" wire:model="note" maxlength="255" @class(['input', 'input-invalid' => $errors->has('note')])>
            @error('note')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            Khi lập phiếu, hệ thống chụp lại <strong>số lượng tồn tại thời điểm này</strong> (snapshot) làm số liệu hệ thống.
            Lập phiếu chưa làm thay đổi tồn kho — tồn chỉ được điều chỉnh khi xác nhận ở bước sau.
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Lập phiếu kiểm kê</button>
            <a href="{{ route('adjustments.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>