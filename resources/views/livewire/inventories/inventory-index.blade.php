<div>
    {{-- Tổng giá trị tồn kho (InventoryService::totalValue, BR05.01-3) --}}
    <div class="card mb-4 flex items-center justify-between">
        <span class="text-sm text-slate-600">Tổng giá trị tồn kho tất cả kho (theo giá vốn bình quân)</span>
        <span class="text-lg font-semibold text-brand">{{ \App\Support\Format::money($totalValue, 2) }}</span>
    </div>

    {{-- Thanh công cụ: tìm kiếm + lọc kho + số dòng mỗi trang --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-3">
            <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm theo SKU hoặc tên sản phẩm…"
                   class="input max-w-xs" aria-label="Tìm sản phẩm trong tồn kho">

            <select wire:model.live="warehouseId" class="input w-auto" aria-label="Lọc theo kho">
                <option value="">Tất cả kho</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                @endforeach
            </select>

            @if ($productId !== '')
                <a href="{{ route('inventory.index', $warehouseId !== '' ? ['warehouse' => $warehouseId] : []) }}"
                   class="btn btn-secondary">Bỏ lọc sản phẩm</a>
            @endif
        </div>

        <select wire:model.live="perPage" class="input w-auto" aria-label="Số dòng mỗi trang">
            @foreach (config('inventory.pagination.options') as $option)
                <option value="{{ $option }}">{{ $option }} dòng / trang</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">SKU</th>
                    <th scope="col">Sản phẩm</th>
                    <th scope="col">Kho</th>
                    <th scope="col" class="text-right">Tồn tại kho</th>
                    <th scope="col" class="text-right">Tổng tồn mọi kho</th>
                    <th scope="col" class="text-right">Ngưỡng cảnh báo</th>
                    <th scope="col" class="text-right">Giá vốn BQ</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col" class="text-right">Thẻ kho</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inventories as $inventory)
                    <tr wire:key="inventory-{{ $inventory->id }}">
                        <td class="font-mono text-slate-600">{{ $inventory->product?->sku ?? '—' }}</td>
                        <td class="font-medium text-slate-900">
                            @if ($inventory->product)
                                <a href="{{ route('inventory.index', ['product' => $inventory->product_id]) }}"
                                   class="hover:underline">{{ $inventory->product->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-slate-600">
                            @if ($inventory->warehouse)
                                <a href="{{ route('inventory.index', ['warehouse' => $inventory->warehouse_id]) }}"
                                   class="hover:underline">{{ $inventory->warehouse->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right font-medium">
                            {{ \App\Support\Format::number($inventory->quantity) }} {{ $inventory->product?->unit }}
                        </td>
                        <td class="text-right tabular-nums">
                            {{ \App\Support\Format::number($inventory->product?->total_stock ?? 0) }}
                            {{ $inventory->product?->unit }}
                        </td>
                        <td class="text-right tabular-nums">
                            {{ \App\Support\Format::number($inventory->product?->min_stock_level ?? 0) }}
                            {{ $inventory->product?->unit }}
                        </td>
                        <td class="text-right text-slate-600">
                            {{ $inventory->product ? \App\Support\Format::money($inventory->product->avg_cost, 4) : '—' }}
                        </td>
                        <td>
                            @if ($inventory->product && ! $inventory->product->is_active)
                                <span class="text-slate-500">Ngừng kinh doanh</span>
                            @elseif ($inventory->product && (int) ($inventory->product->total_stock ?? 0) < $inventory->product->min_stock_level)
                                <span class="font-medium text-amber-700">Dưới ngưỡng</span>
                            @else
                                <span class="text-green-700">Đạt ngưỡng</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-right">
                            @if ($inventory->product)
                                <a href="{{ route('inventory.stock-card', $inventory->product_id) }}"
                                   class="btn btn-secondary">Xem</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $warehouseId !== '' || $productId !== '')
                                Không có dòng tồn kho nào khớp bộ lọc.
                            @else
                                Chưa có dữ liệu tồn kho. Tồn kho được tạo khi xác nhận phiếu nhập kho.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $inventories->links('partials.pagination') }}
    </div>
</div>