{{--
    UC04.02 — Thẻ kho (view của App\Livewire\Inventory\StockCard). Bố cục theo bản mock "Thẻ kho": thẻ thông tin sản phẩm, thanh lọc
    (kho + khoảng ngày + nút Xem), bảng biến động có dòng "Tồn đầu kỳ" (BR04.02-3).

    Lưu ý: `Tồn sau` (balance_after) là tồn của CHÍNH KHO có dòng đó, nên khi xem "Tất cả kho" thì cột này nhảy giữa các kho —
    vì vậy chế độ này có thêm cột Kho và đổi tên tiêu đề cột. Muốn dãy tồn liên tục, chọn một kho cụ thể.
    Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
--}}
<div>
    @php
        $warehouseName = $activeWarehouseId !== null ? ($warehouses->firstWhere('id', $activeWarehouseId)?->name ?? 'kho không rõ') : 'mọi kho';
        $isLow = $totalStock < $product->min_stock_level;
        // Dòng "Tồn đầu kỳ" / "Tồn cuối kỳ" chiếm các cột đứng trước cột "Tồn sau": Ngày, Số phiếu, Loại, [Kho], SL thay đổi
        $labelColspan = $activeWarehouseId !== null ? 4 : 5;
    @endphp

    <div class="card mb-4 flex flex-wrap items-center gap-x-10 gap-y-3">
        <div>
            <div class="text-xs text-slate-500">Sản phẩm</div>
            <div class="text-base font-semibold text-slate-900">{{ $product->sku }} – {{ $product->name }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-500">Tồn hiện tại ({{ $warehouseName }})</div>
            <div @class(['text-base font-semibold', 'text-red-700' => $isLow])>
                {{ \App\Support\Format::number($currentStock) }} {{ $product->unit }}
            </div>
        </div>
        <div>
            <div class="text-xs text-slate-500">Giá vốn BQ</div>
            <div class="text-base font-semibold">{{ \App\Support\Format::money($product->avg_cost) }}</div>
        </div>
        <div>
            <div class="text-xs text-slate-500">Ngưỡng cảnh báo</div>
            <div class="text-base font-semibold">{{ \App\Support\Format::number($product->min_stock_level) }}</div>
        </div>
        @if ($isLow)
            <span class="badge badge-danger" title="BR04.04-3: so với tổng tồn của mọi kho">
                Tồn thấp (tổng mọi kho: {{ \App\Support\Format::number($totalStock) }})
            </span>
        @endif
    </div>

    <form wire:submit="apply" class="mb-4 flex flex-wrap items-end gap-3" novalidate>
        <div>
            <label for="stock-card-warehouse" class="label">Kho</label>
            <select id="stock-card-warehouse" wire:model="warehouseId" class="input w-auto">
                <option value="">Tất cả kho</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="stock-card-from" class="label">Từ ngày</label>
            <input id="stock-card-from" type="date" wire:model="from" class="input w-auto">
        </div>
        <div>
            <label for="stock-card-to" class="label">Đến ngày</label>
            <input id="stock-card-to" type="date" wire:model="to" class="input w-auto">
        </div>
        <button type="submit" class="btn btn-primary">Xem</button>
        <a href="{{ route('inventory.index') }}" class="btn btn-secondary ml-auto">‹ Về tồn kho</a>
    </form>

    @error('from') <p class="field-error mb-2">{{ $message }}</p> @enderror
    @error('to') <p class="field-error mb-2">{{ $message }}</p> @enderror

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Ngày</th>
                    <th scope="col">Số phiếu</th>
                    <th scope="col">Loại</th>
                    @if ($activeWarehouseId === null)
                        <th scope="col">Kho</th>
                    @endif
                    <th scope="col" class="text-right">SL thay đổi</th>
                    <th scope="col" class="text-right">{{ $activeWarehouseId === null ? 'Tồn sau (của kho đó)' : 'Tồn sau' }}</th>
                    <th scope="col" class="text-right">Giá vốn BQ sau (₫)</th>
                </tr>
            </thead>
            <tbody>
                @if ($fromDate !== null)
                    <tr class="bg-slate-50 font-semibold">
                        <td colspan="{{ $labelColspan }}">Tồn đầu kỳ {{ \Illuminate\Support\Carbon::parse($fromDate)->format('d/m/Y') }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($card['opening_balance']) }}</td>
                        <td></td>
                    </tr>
                @endif

                @forelse ($card['movements'] as $movement)
                    <tr wire:key="movement-{{ $movement->id }}">
                        <td class="whitespace-nowrap" title="{{ $movement->created_at->format('d/m/Y H:i') }}">{{ $movement->created_at->format('d/m/Y') }}</td>
                        {{-- reference_code do InventoryService::getStockCard() gắn thêm; chưa có màn hình chi tiết phiếu nên tạm hiện chữ (BR04.02-7: sau này đổi thành liên kết) --}}
                        <td class="whitespace-nowrap font-mono text-xs">{{ $movement->getAttributes()['reference_code'] ?? '—' }}</td>
                        <td><span class="badge badge-{{ $movement->type->badge() }}">{{ $movement->type->label() }}</span></td>
                        @if ($activeWarehouseId === null)
                            <td>{{ $movement->warehouse->name }}</td>
                        @endif
                        <td @class(['text-right font-medium tabular-nums', 'text-green-700' => $movement->qty_change > 0, 'text-red-700' => $movement->qty_change < 0])>{{ $movement->qty_change > 0 ? '+' : '' }}{{ \App\Support\Format::number($movement->qty_change) }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($movement->balance_after) }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($movement->avg_cost_after) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $labelColspan + 2 }}" class="py-10 text-center text-slate-500">
                            Không có biến động nào trong khoảng đã chọn.
                        </td>
                    </tr>
                @endforelse

                @if ($card['movements']->isNotEmpty())
                    <tr class="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                        <td colspan="{{ $labelColspan }}">Tồn cuối kỳ</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($card['closing_balance']) }}</td>
                        <td></td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <p class="mt-2 text-xs text-slate-500">
        Thẻ kho là sổ chỉ ghi thêm (append-only): mỗi giao dịch xác nhận sinh ra đúng một dòng, không sửa hay xóa.
    </p>
</div>
