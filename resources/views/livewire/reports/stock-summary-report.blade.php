{{--
    UC05.02 — Báo cáo Nhập – Xuất – Tồn (view của App\Livewire\Reports\StockSummaryReport). Bố cục theo bản mock "Báo cáo":
    thanh lọc (từ ngày, đến ngày, kho, danh mục, "Xem báo cáo", "Xuất Excel"), thẻ báo cáo với tiêu đề ghi rõ kỳ và kho,
    bảng 8 cột, dòng tổng cộng và ghi chú công thức. Bộ lọc chỉ có hiệu lực khi bấm "Xem báo cáo" (xem class PHP).
    Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
--}}
<div>
    <form wire:submit="apply" class="mb-4 flex flex-wrap items-end gap-3" novalidate>
        <label class="flex flex-col gap-1 text-xs text-slate-500">
            Từ ngày
            <input type="date" wire:model="from" class="input w-auto {{ $errors->has('from') ? 'input-invalid' : '' }}" aria-label="Từ ngày">
        </label>

        <label class="flex flex-col gap-1 text-xs text-slate-500">
            Đến ngày
            <input type="date" wire:model="to" class="input w-auto {{ $errors->has('to') ? 'input-invalid' : '' }}" aria-label="Đến ngày">
        </label>

        <label class="flex flex-col gap-1 text-xs text-slate-500">
            Kho
            <select wire:model="warehouseId" class="input w-auto" aria-label="Lọc theo kho">
                <option value="">Tất cả các kho</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1 text-xs text-slate-500">
            Danh mục
            <select wire:model="categoryId" class="input w-auto" aria-label="Lọc theo danh mục">
                <option value="">Tất cả</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="apply">Xem báo cáo</button>

        <div class="ml-auto">
            {{-- BR05.04-3: chỉ xuất khi báo cáo có dữ liệu. Liên kết tải trực tiếp (không qua Livewire), theo bộ lọc ĐÃ ÁP DỤNG. --}}
            @if (count($report['rows']) > 0)
                <a href="{{ $exportUrl }}" class="btn btn-primary" download>Xuất Excel</a>
            @else
                <span class="btn btn-secondary cursor-not-allowed opacity-50" title="Không có dữ liệu để xuất" aria-disabled="true">Xuất Excel</span>
            @endif
        </div>
    </form>

    @error('from') <p class="field-error mb-2">{{ $message }}</p> @enderror
    @error('to') <p class="field-error mb-2">{{ $message }}</p> @enderror

    <div class="card">
        <h2 class="mb-3 text-base font-semibold text-slate-900">
            Báo cáo Nhập – Xuất – Tồn
            ({{ \Illuminate\Support\Carbon::parse($report['filters']['from'])->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($report['filters']['to'])->format('d/m/Y') }},
            {{ $warehouseName ?? 'tất cả các kho' }})
        </h2>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Sản phẩm</th>
                        <th scope="col" class="text-right">Tồn đầu</th>
                        <th scope="col" class="text-right">Nhập</th>
                        <th scope="col" class="text-right">Xuất</th>
                        <th scope="col" class="text-right">Điều chỉnh</th>
                        <th scope="col" class="text-right">Tồn cuối</th>
                        <th scope="col" class="text-right">Giá trị tồn cuối (đ)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr wire:key="sum-{{ $row['product_id'] }}">
                            <td class="whitespace-nowrap font-mono text-xs">{{ $row['sku'] }}</td>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('inventory.stock-card', ['product' => $row['product_id'], 'warehouse' => $report['filters']['warehouse_id'], 'from' => $report['filters']['from'], 'to' => $report['filters']['to']]) }}"
                                   class="text-brand hover:underline" title="Xem thẻ kho trong kỳ này">{{ $row['name'] }}</a>
                            </td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($row['opening']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($row['imports']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($row['exports']) }}</td>
                            <td class="text-right tabular-nums {{ $row['adjustment'] < 0 ? 'text-red-700' : ($row['adjustment'] > 0 ? 'text-green-700' : '') }}">
                                {{ $row['adjustment'] > 0 ? '+' : '' }}{{ \App\Support\Format::number($row['adjustment']) }}
                            </td>
                            <td class="text-right font-medium tabular-nums">{{ \App\Support\Format::number($row['closing']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($row['closing_value']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-500">Không có biến động hay tồn kho nào trong kỳ và bộ lọc đã chọn.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($report['rows']) > 0)
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 font-semibold">
                        <tr>
                            <td colspan="2">Tổng cộng ({{ count($report['rows']) }} sản phẩm)</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($report['totals']['opening']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($report['totals']['imports']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($report['totals']['exports']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($report['totals']['adjustment']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($report['totals']['closing']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($report['totals']['closing_value']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <p class="mt-3 text-xs text-slate-500">
            Tồn cuối = Tồn đầu + Nhập − Xuất + Điều chỉnh. Giá trị tồn cuối = Tồn cuối × giá vốn bình quân tại cuối kỳ.
            @if ($warehouseName !== null)
                Đang xem một kho: chuyển kho được tính vào Nhập / Xuất của kho đó.
            @else
                Đang xem tất cả các kho: hai chiều chuyển kho triệt tiêu nhau nên không tính vào Nhập / Xuất.
            @endif
        </p>
    </div>
</div>
