{{--
    UC03.01 / UC03.04 — Lập, sửa, xem phiếu nhập kho (view của App\Livewire\Imports\ImportForm). Mock: mock_import_form.html.
    $editable = false (phiếu đã xác nhận / hủy, hoặc không có quyền sửa) -> mọi ô khóa, ẩn nút ghi.
    Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
--}}
<div>
    {{-- Thông báo kết quả thao tác (thành công / lỗi nghiệp vụ như 409) --}}
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

    <form wire:submit="save" class="card space-y-4" novalidate>
        <h2 class="flex items-center gap-2 text-lg font-semibold text-brand">
            {{ $importId === null ? 'Lập phiếu nhập kho' : 'Phiếu nhập kho' }}
            @include('livewire.imports.status-badge', ['status' => $status])
        </h2>

        {{-- Đầu phiếu (BR03.01-2: NCC và kho nhập phải còn hoạt động) --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <span class="label">Mã phiếu</span>
                <div class="input bg-slate-50 text-slate-600">{{ $code !== '' ? $code : '(tự sinh khi lưu)' }}</div>
            </div>

            <div>
                <label for="import-supplier" class="label">Nhà cung cấp <span class="text-red-600">*</span></label>
                <select id="import-supplier" wire:model="supplier_id" @disabled(! $editable)
                        @class(['input', 'input-invalid' => $errors->has('supplier_id')])>
                    <option value="">— Chọn nhà cung cấp —</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="import-warehouse" class="label">Kho nhập <span class="text-red-600">*</span></label>
                <select id="import-warehouse" wire:model="warehouse_id" @disabled(! $editable)
                        @class(['input', 'input-invalid' => $errors->has('warehouse_id')])>
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
                <label for="import-date" class="label">Ngày nhập <span class="text-red-600">*</span></label>
                <input id="import-date" type="date" wire:model="import_date" @disabled(! $editable)
                       @class(['input', 'input-invalid' => $errors->has('import_date')])>
                @error('import_date')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="import-note" class="label">Ghi chú</label>
            <input id="import-note" type="text" wire:model="note" maxlength="255" @disabled(! $editable)
                   @class(['input', 'input-invalid' => $errors->has('note')])>
            @error('note')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        @error('items')
            <p class="field-error">{{ $message }}</p>
        @enderror

        {{-- Bảng dòng hàng: số lượng nguyên dương, đơn giá >= 0, mỗi sản phẩm một dòng (rule distinct) --}}
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Sản phẩm <span class="text-red-600">*</span></th>
                        <th scope="col" class="text-right">Tồn hiện tại</th>
                        <th scope="col" class="text-right">Số lượng <span class="text-red-600">*</span></th>
                        <th scope="col" class="text-right">Đơn giá nhập (đ) <span class="text-red-600">*</span></th>
                        <th scope="col" class="text-right">Thành tiền (đ)</th>
                        @if ($isDraft)
                            <th scope="col" class="text-right">Giá vốn BQ sau nhập (dự kiến)</th>
                        @endif
                        @if ($editable)
                            <th scope="col"><span class="sr-only">Xóa dòng</span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $i => $item)
                        <tr wire:key="item-{{ $i }}">
                            <td class="whitespace-nowrap text-slate-500">{{ $lines[$i]['product']?->sku ?? '—' }}</td>

                            <td class="min-w-[14rem]">
                                @if ($editable)
                                    <select wire:model.live="items.{{ $i }}.product_id" aria-label="Sản phẩm dòng {{ $i + 1 }}"
                                            @class(['input', 'input-invalid' => $errors->has("items.$i.product_id")])>
                                        <option value="">— Chọn sản phẩm —</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{ $lines[$i]['product']?->name ?? '—' }}
                                @endif
                                @error("items.$i.product_id")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </td>

                            <td class="text-right text-slate-600">
                                {{ $lines[$i]['stock'] !== null ? \App\Support\Format::number($lines[$i]['stock']) : '—' }}
                            </td>

                            <td class="text-right">
                                @if ($editable)
                                    <input type="number" min="1" step="1" inputmode="numeric" wire:model.live.debounce.400ms="items.{{ $i }}.quantity"
                                           aria-label="Số lượng dòng {{ $i + 1 }}"
                                           @class(['input w-24 text-right', 'input-invalid' => $errors->has("items.$i.quantity")])>
                                @else
                                    {{ \App\Support\Format::number((int) $item['quantity']) }}
                                @endif
                                @error("items.$i.quantity")
                                    <p class="field-error text-left">{{ $message }}</p>
                                @enderror
                            </td>

                            <td class="text-right">
                                @if ($editable)
                                    <input type="number" min="0" step="any" inputmode="decimal" wire:model.live.debounce.400ms="items.{{ $i }}.unit_cost"
                                           aria-label="Đơn giá nhập dòng {{ $i + 1 }}"
                                           @class(['input w-32 text-right', 'input-invalid' => $errors->has("items.$i.unit_cost")])>
                                @else
                                    {{ \App\Support\Format::number((float) $item['unit_cost']) }}
                                @endif
                                @error("items.$i.unit_cost")
                                    <p class="field-error text-left">{{ $message }}</p>
                                @enderror
                            </td>

                            <td class="text-right">
                                {{ $lines[$i]['lineTotal'] !== null ? \App\Support\Format::number($lines[$i]['lineTotal']) : '—' }}
                            </td>

                            @if ($isDraft)
                                <td class="text-right text-slate-600">
                                    {{ $lines[$i]['newAvg'] !== null ? \App\Support\Format::number((float) $lines[$i]['newAvg']) : '—' }}
                                </td>
                            @endif

                            @if ($editable)
                                <td class="whitespace-nowrap text-right">
                                    @if (count($items) > 1)
                                        <button type="button" wire:click="removeItem({{ $i }})" class="btn btn-danger-outline">Xóa</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($editable)
                <button type="button" wire:click="addItem" class="btn btn-secondary">+ Thêm dòng hàng</button>
            @else
                <span></span>
            @endif

            <div class="text-base">
                Tổng tiền: <strong>{{ \App\Support\Format::number($total) }} đ</strong>
            </div>
        </div>

        @if ($isDraft)
            <div class="rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                Giá vốn bình quân = (Tồn cũ × Giá vốn cũ + SL nhập × Đơn giá nhập) / (Tồn cũ + SL nhập). Giá trị dự kiến chỉ để xem trước,
                chỉ được ghi khi bấm <strong>Xác nhận nhập kho</strong>. Lưu nháp không làm thay đổi tồn kho.
            </div>
        @endif

        {{-- Nút thao tác --}}
        <div class="flex flex-wrap items-center gap-2">
            @if ($editable)
                <button type="submit" class="btn btn-secondary" wire:loading.attr="disabled" wire:target="save">Lưu nháp</button>
                <button type="button" wire:click="askConfirm" class="btn btn-primary" wire:loading.attr="disabled">Xác nhận nhập kho</button>
            @endif

            @if ($canCancel)
                <button type="button" wire:click="askCancel" class="btn btn-danger-outline">Hủy phiếu</button>
            @endif

            <a href="{{ route('imports.index') }}" class="btn btn-secondary ml-auto">← Danh sách phiếu nhập</a>
        </div>
    </form>

    {{-- Hộp thoại xác nhận nhập kho: sau bước này cộng tồn và tính lại giá vốn, không thể sửa --}}
    @if ($dialog === 'confirm')
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="import-confirm-title"
             wire:keydown.escape="closeDialog">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="import-confirm-title" class="text-lg font-semibold text-brand">Xác nhận nhập kho</h2>
                <p class="text-sm">
                    Phiếu sẽ được lưu rồi xác nhận: tồn kho được cộng, giá vốn bình quân được tính lại và ghi vào thẻ kho.
                    Sau khi xác nhận <strong>không thể sửa hay hủy</strong> phiếu.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeDialog" class="btn btn-secondary">Quay lại</button>
                    <button type="button" wire:click="confirmImport" class="btn btn-primary" wire:loading.attr="disabled" wire:target="confirmImport">Xác nhận</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Hộp thoại hủy phiếu (UC03.04: chỉ phiếu nháp, không đụng tới tồn kho) --}}
    @if ($dialog === 'cancel')
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="import-cancel-title"
             wire:keydown.escape="closeDialog">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="import-cancel-title" class="text-lg font-semibold text-red-700">Hủy phiếu nhập</h2>
                <p class="text-sm">
                    Bạn có chắc muốn hủy phiếu <strong>{{ $code }}</strong>? Thao tác này không thể hoàn tác.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeDialog" class="btn btn-secondary">Quay lại</button>
                    <button type="button" wire:click="cancelImport" class="btn btn-danger" wire:loading.attr="disabled" wire:target="cancelImport">Hủy phiếu</button>
                </div>
            </div>
        </div>
    @endif
</div>