{{--
    UC05.03 — Báo cáo kiểm kê (view của App\Livewire\Reports\AdjustmentReport). Bố cục: thanh lọc (từ ngày, đến ngày, kho, lý do,
    "hiện cả dòng khớp", "Xem báo cáo", "Xuất Excel"), 4 thẻ tổng hợp (BR05.03-5), bảng chi tiết (BR05.03-4), ghi chú cách tính.
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
            Lý do chênh lệch
            <select wire:model="reason" class="input w-auto" aria-label="Lọc theo lý do chênh lệch">
                <option value="">Tất cả</option>
                @foreach ($reasons as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex items-center gap-2 pb-2 text-sm text-slate-700">
            <input type="checkbox" wire:model="showMatching" class="rounded border-slate-300">
            Hiện cả dòng khớp
        </label>

        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="apply">Xem báo cáo</button>

        <div class="ml-auto">
            {{-- BR05.04-3: chỉ xuất khi báo cáo có dữ liệu. Liên kết tải trực tiếp, theo bộ lọc ĐÃ ÁP DỤNG. --}}
            @if (count($report['rows']) > 0)
                <a href="{{ $exportUrl }}" class="btn btn-primary" download>Xuất Excel</a>
            @else
                <span class="btn btn-secondary cursor-not-allowed opacity-50" title="Không có dữ liệu để xuất" aria-disabled="true">Xuất Excel</span>
            @endif
        </div>
    </form>

    @error('from') <p class="field-error mb-2">{{ $message }}</p> @enderror
    @error('to') <p class="field-error mb-2">{{ $message }}</p> @enderror

    {{-- Thẻ tổng hợp (BR05.03-5): tính trên TOÀN BỘ dòng khớp bộ lọc, không phụ thuộc "Hiện cả dòng khớp" --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card border-t-4 border-t-red-500">
            <p class="text-xs text-slate-500">Tổng hao hụt</p>
            <p class="mt-1 text-2xl font-bold text-red-700 tabular-nums">{{ \App\Support\Format::number($report['summary']['shortage_qty']) }}</p>
            <p class="text-xs text-slate-500">Giá trị: {{ \App\Support\Format::money($report['summary']['shortage_value']) }}</p>
        </div>
        <div class="card border-t-4 border-t-green-500">
            <p class="text-xs text-slate-500">Tổng dôi thừa</p>
            <p class="mt-1 text-2xl font-bold text-green-700 tabular-nums">{{ \App\Support\Format::number($report['summary']['surplus_qty']) }}</p>
            <p class="text-xs text-slate-500">Giá trị: {{ \App\Support\Format::money($report['summary']['surplus_value']) }}</p>
        </div>
        <div class="card border-t-4 border-t-amber-500">
            <p class="text-xs text-slate-500">Tỷ lệ sai lệch</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 tabular-nums">{{ \App\Support\Format::number($report['summary']['error_rate'], 2) }}%</p>
            <p class="text-xs text-slate-500">{{ \App\Support\Format::number($report['summary']['lines_diff']) }} / {{ \App\Support\Format::number($report['summary']['lines_total']) }} dòng kiểm kê lệch</p>
        </div>
        <div class="card border-t-4 border-t-slate-400">
            <p class="text-xs text-slate-500">Phiếu kiểm kê đã xác nhận</p>
            <p class="mt-1 text-2xl font-bold text-slate-900 tabular-nums">{{ \App\Support\Format::number($report['summary']['adjustments']) }}</p>
            <p class="text-xs text-slate-500">Chênh lệch ròng: {{ \App\Support\Format::money($report['summary']['net_value']) }}</p>
        </div>
    </div>

    <div class="card">
        <h2 class="mb-3 text-base font-semibold text-slate-900">
            Lịch sử kiểm kê
            ({{ \Illuminate\Support\Carbon::parse($report['filters']['from'])->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($report['filters']['to'])->format('d/m/Y') }},
            {{ $warehouseName ?? 'tất cả các kho' }})
        </h2>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Ngày kiểm kê</th>
                        <th scope="col">Số phiếu</th>
                        <th scope="col">Kho</th>
                        <th scope="col">Sản phẩm</th>
                        <th scope="col" class="text-right">SL hệ thống</th>
                        <th scope="col" class="text-right">SL thực đếm</th>
                        <th scope="col" class="text-right">Chênh lệch</th>
                        <th scope="col" class="text-right">Giá trị (đ)</th>
                        <th scope="col">Lý do</th>
                        <th scope="col">Người lập</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="adj-{{ $row['adjustment_id'] }}-{{ $row['product_id'] }}" class="{{ $row['diff_qty'] < 0 ? 'bg-red-50' : '' }}">
                            <td class="whitespace-nowrap tabular-nums">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap font-mono text-xs">{{ $row['code'] }}</td>
                            <td class="whitespace-nowrap">{{ $row['warehouse'] }}</td>
                            <td class="font-medium text-slate-900">
                                <span class="font-mono text-xs font-normal text-slate-500">{{ $row['sku'] }}</span>
                                {{ $row['product_name'] }}
                            </td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($row['system_qty']) }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($row['counted_qty']) }}</td>
                            <td class="text-right font-medium tabular-nums {{ $row['diff_qty'] < 0 ? 'text-red-700' : ($row['diff_qty'] > 0 ? 'text-green-700' : 'text-slate-500') }}">
                                {{ $row['diff_qty'] > 0 ? '+' : '' }}{{ \App\Support\Format::number($row['diff_qty']) }}
                            </td>
                            <td class="text-right tabular-nums {{ $row['diff_value'] < 0 ? 'text-red-700' : ($row['diff_value'] > 0 ? 'text-green-700' : 'text-slate-500') }}">
                                {{ $row['diff_value'] > 0 ? '+' : '' }}{{ \App\Support\Format::number($row['diff_value']) }}
                            </td>
                            <td>
                                @if ($row['reason_label'])
                                    <span class="badge badge-warning">{{ $row['reason_label'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $row['creator'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-slate-500">
                                Không có phiếu kiểm kê đã xác nhận nào {{ $report['filters']['only_diff'] ? 'có chênh lệch ' : '' }}trong kỳ và bộ lọc đã chọn.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($truncated)
            <p class="mt-2 text-xs text-amber-700">
                Chỉ hiển thị {{ \App\Livewire\Reports\AdjustmentReport::MAX_ROWS }} dòng đầu trong tổng {{ \App\Support\Format::number(count($report['rows'])) }} dòng.
                Các số tổng hợp ở trên vẫn tính trên toàn bộ; bấm "Xuất Excel" để xem đủ.
            </p>
        @endif

        <p class="mt-3 text-xs text-slate-500">
            Chỉ tính các phiếu đã xác nhận có ngày kiểm kê trong kỳ. Hao hụt = các dòng thực đếm ít hơn hệ thống; dôi thừa = các dòng thực đếm nhiều hơn.
            Tỷ lệ sai lệch = số dòng có chênh lệch / tổng số dòng kiểm kê (khi lọc theo lý do, chỉ đếm dòng lệch có lý do đó).
        </p>
    </div>
</div>
