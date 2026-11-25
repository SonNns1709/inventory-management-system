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

    @php
        $status = $export->status instanceof \BackedEnum ? $export->status->value : (string) $export->status;
        $reason = $export->reason instanceof \BackedEnum ? $export->reason->value : (string) $export->reason;
        $isDraft = $status === 'draft';
    @endphp

    <div class="card space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-brand">Phiếu xuất {{ $export->code }}</h2>
            @include('livewire.exports.status-badge', ['status' => $export->status])
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><span class="label">Kho xuất</span><p>{{ $export->warehouse?->name }}</p></div>
            <div><span class="label">Kho nhận</span><p>{{ $reason === 'transfer' ? $export->destWarehouse?->name : 'Không áp dụng' }}</p></div>
            <div><span class="label">Ngày xuất</span><p>{{ $export->export_date->format('d/m/Y') }}</p></div>
            <div><span class="label">Lý do</span><p>{{ $reason === 'transfer' ? 'Chuyển kho' : 'Bán hàng' }}</p></div>
        </div>

        @if ($export->note)
            <p class="text-sm text-slate-600">Ghi chú: {{ $export->note }}</p>
        @endif

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead><tr><th>SKU</th><th>Sản phẩm</th><th class="text-right">Số lượng</th><th class="text-right">Đơn giá vốn</th><th class="text-right">Thành tiền</th></tr></thead>
                <tbody>
                    @forelse ($export->items as $item)
                        <tr>
                            <td>{{ $item->product?->sku ?? '—' }}</td>
                            <td>{{ $item->product?->name ?? '—' }}</td>
                            <td class="text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td class="text-right">{{ $item->unit_cost !== null ? number_format((float) $item->unit_cost, 0, ',', '.') : '—' }}</td>
                            <td class="text-right">{{ $item->line_total !== null ? number_format((float) $item->line_total, 0, ',', '.') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-500">Phiếu chưa có dòng hàng.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-right">Tổng giá vốn: <strong>{{ number_format((float) $export->total_cost, 0, ',', '.') }} đ</strong></p>

        <div class="flex gap-2">
            @if ($isDraft)
                @can('update', $export)
                    <a href="{{ route('exports.edit', $export) }}" class="btn btn-secondary">Sửa</a>
                @endcan
                @can('confirm', $export)
                    <button type="button" wire:click="confirm" class="btn btn-primary" wire:confirm="Xác nhận xuất kho phiếu này?">Xác nhận</button>
                @endcan
                @can('cancel', $export)
                    <button type="button" wire:click="cancel" class="btn btn-danger-outline" wire:confirm="Hủy phiếu xuất nháp này?">Hủy phiếu</button>
                @endcan
            @endif
            <a href="{{ route('exports.index') }}" class="btn btn-secondary ml-auto">← Danh sách</a>
        </div>
    </div>
</div>
