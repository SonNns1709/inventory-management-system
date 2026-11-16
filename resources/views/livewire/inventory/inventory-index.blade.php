{{--
    UC04.01 — Tồn kho (view của App\Livewire\Inventory\InventoryIndex). Bố cục theo bản mock "Tồn kho": thanh lọc, bảng tồn,
    dòng tổng cộng. Dòng "Tồn thấp" được tô đỏ theo BR04.04-3: so ngưỡng với TỔNG tồn của sản phẩm trên mọi kho
    (`product_total_stock` do InventoryService::getStock() gắn vào từng dòng), nên cùng một sản phẩm ở hai kho có cùng trạng thái.
    Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
--}}
<div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm theo SKU hoặc tên…"
               class="input w-full sm:w-64" aria-label="Tìm theo SKU hoặc tên sản phẩm">

        <select wire:model.live="warehouseId" class="input w-auto" aria-label="Lọc theo kho">
            <option value="">Kho: Tất cả</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="categoryId" class="input w-auto" aria-label="Lọc theo danh mục">
            <option value="">Danh mục: Tất cả</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" wire:model.live="lowOnly" class="rounded border-slate-300">
            Chỉ tồn thấp
        </label>

        @if ($keyword !== '' || $warehouseId !== '' || $categoryId !== '' || $lowOnly)
            <button type="button" wire:click="clearFilters" class="btn btn-secondary">Xóa lọc</button>
        @endif

        {{-- Livewire không đẩy dữ liệu theo thời gian thực: hiện giờ tải và cho bấm "Làm mới" (thêm wire:poll.30s vào <div> gốc nếu muốn tự cập nhật) --}}
        <div class="ml-auto flex items-center gap-3 text-xs text-slate-500">
            <span>Cập nhật lúc {{ now()->format('H:i:s') }}</span>
            <button type="button" wire:click="$refresh" class="btn btn-secondary">Làm mới</button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">SKU</th>
                    <th scope="col">Sản phẩm</th>
                    <th scope="col">Kho</th>
                    <th scope="col" class="text-right" aria-sort="{{ $sort === 'quantity' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button type="button" wire:click="sortBy('quantity')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                            Tồn
                            @if ($sort === 'quantity')
                                <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th scope="col" class="text-right">Giá vốn BQ (₫)</th>
                    <th scope="col" class="text-right">Giá trị tồn (₫)</th>
                    <th scope="col" class="text-right">Ngưỡng</th>
                    <th scope="col">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalQuantity = 0;
                    $totalValue = 0.0;
                @endphp
                @forelse ($rows as $row)
                    @php
                        $product = $row->product;
                        // Giá trị tồn của dòng = số lượng × giá vốn bình quân (chỉ để hiển thị; tổng giá trị toàn kho do CostingService tính bằng SQL)
                        $value = $row->quantity * (float) $product->avg_cost;
                        $low = $row->product_total_stock < $product->min_stock_level;
                        $totalQuantity += $row->quantity;
                        $totalValue += $value;
                    @endphp
                    <tr wire:key="inventory-{{ $row->id }}" @class(['bg-red-50' => $low])>
                        <td class="whitespace-nowrap font-mono text-xs">{{ $product->sku }}</td>
                        <td class="font-medium text-slate-900">
                            <a href="{{ route('inventory.stock-card', ['product' => $product->id, 'warehouse' => $row->warehouse_id]) }}"
                               class="text-brand hover:underline" title="Xem thẻ kho">{{ $product->name }}</a>
                        </td>
                        <td>{{ $row->warehouse->name }}</td>
                        <td @class(['text-right tabular-nums', 'font-bold text-red-700' => $low])>{{ \App\Support\Format::number($row->quantity) }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($product->avg_cost) }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($value) }}</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($product->min_stock_level) }}</td>
                        <td>
                            @if ($low)
                                <span class="badge badge-danger">Tồn thấp</span>
                            @else
                                <span class="badge badge-success">Đủ</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $warehouseId !== '' || $categoryId !== '' || $lowOnly)
                                Không có dòng tồn kho nào khớp bộ lọc.
                            @else
                                Chưa có dữ liệu tồn kho.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($rows->count() > 0)
                <tfoot class="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                    <tr>
                        <td colspan="3">Tổng cộng (các dòng hiển thị)</td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($totalQuantity) }}</td>
                        <td></td>
                        <td class="text-right tabular-nums">{{ \App\Support\Format::number($totalValue) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div class="mt-4">
        {{ $rows->links('partials.pagination') }}
    </div>
</div>
