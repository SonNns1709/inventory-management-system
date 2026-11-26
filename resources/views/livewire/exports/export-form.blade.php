{{--
    UC03.02 — Form lập / sửa phiếu xuất kho (view của App\Livewire\Exports\ExportForm), bố cục theo mock_export_form.html.
    Livewire yêu cầu MỘT phần tử gốc duy nhất.
--}}
<div>
    {{-- Thông báo kết quả thao tác --}}
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

    <div class="card space-y-4">
        <h2 class="flex items-center gap-2 text-lg font-semibold text-brand">
            {{ $editingId === null ? 'Lập phiếu xuất kho' : 'Sửa phiếu xuất kho' }}
            @include('livewire.exports.status-badge', ['status' => 'draft'])
        </h2>

        {{-- Thông tin phiếu --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="export-code" class="label">Mã phiếu</label>
                <input id="export-code" type="text" class="input" value="{{ $code !== '' ? $code : 'Tự sinh khi lưu phiếu' }}" disabled>
            </div>

            <div>
                <label for="export-warehouse" class="label">Kho xuất <span class="text-red-600">*</span></label>
                <select id="export-warehouse" wire:model.live="warehouse_id" @class(['input', 'input-invalid' => $errors->has('warehouse_id')])>
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
                <label for="export-reason" class="label">Lý do xuất <span class="text-red-600">*</span></label>
                <select id="export-reason" wire:model.live="reason" @class(['input', 'input-invalid' => $errors->has('reason')])>
                    <option value="sale">Bán hàng</option>
                    <option value="transfer">Chuyển kho</option>
                </select>
                @error('reason')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="export-dest" class="label">Kho đích (khi chuyển kho)</label>
                <select id="export-dest" wire:model="dest_warehouse_id" @disabled($reason !== 'transfer')
                        @class(['input', 'input-invalid' => $errors->has('dest_warehouse_id')])>
                    <option value="">{{ $reason === 'transfer' ? '— Chọn kho nhận —' : 'Không áp dụng' }}</option>
                    @foreach ($warehouses as $warehouse)
                        @if ((string) $warehouse->id !== $warehouse_id)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endif
                    @endforeach
                </select>
                @error('dest_warehouse_id')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="export-date" class="label">Ngày xuất <span class="text-red-600">*</span></label>
                <input id="export-date" type="date" wire:model="export_date" @class(['input', 'input-invalid' => $errors->has('export_date')])>
                @error('export_date')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="export-note" class="label">Ghi chú</label>
            <input id="export-note" type="text" wire:model="note" maxlength="255" @class(['input', 'input-invalid' => $errors->has('note')])>
            @error('note')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Cảnh báo không đủ tồn (BR03.02-3/4) --}}
        @if ($hasShortage)
            <div role="alert" class="rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                Không đủ tồn để xác nhận{{ $warehouseName ? ' tại '.$warehouseName : '' }}:
                {{ implode('; ', $shortages) }}.
                Vui lòng giảm số lượng hoặc lập phiếu nhập trước.
            </div>
        @endif

        @error('items')
            <p class="field-error">{{ $message }}</p>
        @enderror

        {{-- Dòng hàng xuất --}}
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Sản phẩm</th>
                        <th scope="col" class="text-right">Tồn khả dụng</th>
                        <th scope="col" class="text-right">Số lượng xuất <span class="text-red-600">*</span></th>
                        <th scope="col" class="text-right">Giá vốn BQ (đ)</th>
                        <th scope="col" class="text-right">Giá trị (đ)</th>
                        <th scope="col">Kiểm tra</th>
                        <th scope="col"><span class="sr-only">Xóa dòng</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        <tr wire:key="export-line-{{ $i }}" @class(['bg-red-50' => $row['short']])>
                            <td class="whitespace-nowrap font-medium">{{ $row['product']?->sku ?? '—' }}</td>
                            <td>
                                <select wire:model.live="items.{{ $i }}.product_id" aria-label="Sản phẩm dòng {{ $i + 1 }}"
                                        @class(['input min-w-64', 'input-invalid' => $errors->has("items.$i.product_id")])>
                                    <option value="">— Chọn sản phẩm —</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                                    @endforeach
                                </select>
                                @error("items.$i.product_id")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="text-right">
                                {{ $row['available'] !== null ? number_format($row['available'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="text-right">
                                <input type="number" min="1" step="1" wire:model.live.debounce.400ms="items.{{ $i }}.quantity"
                                       aria-label="Số lượng dòng {{ $i + 1 }}"
                                       @class(['input ml-auto w-24 text-right', 'input-invalid' => $errors->has("items.$i.quantity") || $row['short']])>
                                @error("items.$i.quantity")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="whitespace-nowrap text-right">{{ $row['product'] ? number_format($row['cost'], 0, ',', '.') : '—' }}</td>
                            <td class="whitespace-nowrap text-right">{{ $row['product'] ? number_format($row['value'], 0, ',', '.') : '—' }}</td>
                            <td class="whitespace-nowrap">
                                @if ($row['short'])
                                    <span class="text-red-700">Vượt tồn {{ number_format($row['shortage'], 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <button type="button" wire:click="removeLine({{ $i }})" class="btn btn-danger-outline">Xóa</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" wire:click="addLine" class="btn btn-secondary">+ Thêm dòng hàng</button>
            <p class="text-base">Tổng giá vốn (dự kiến): <strong>{{ number_format($total, 0, ',', '.') }} đ</strong></p>
        </div>

        <p class="text-xs text-slate-500">
            Giá vốn trên chỉ là ước tính theo giá vốn bình quân hiện tại. Giá vốn chính thức được chốt khi xác nhận phiếu xuất.
        </p>

        {{-- Hành động --}}
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="save(false)" class="btn btn-secondary"
                    wire:loading.attr="disabled" wire:target="save">Lưu nháp</button>

            <button type="button" wire:click="save(true)" class="btn btn-primary disabled:opacity-50"
                    @disabled($hasShortage) wire:loading.attr="disabled" wire:target="save"
                    title="{{ $hasShortage ? 'Có dòng vượt tồn khả dụng' : 'Lưu và xác nhận xuất kho' }}">Xác nhận xuất kho</button>

            <button type="button" wire:click="cancelDocument" class="btn btn-danger-outline"
                    @if ($editingId !== null) wire:confirm="Hủy phiếu xuất nháp này?" @endif>
                {{ $editingId === null ? 'Hủy' : 'Hủy phiếu' }}
            </button>
        </div>
    </div>
</div>