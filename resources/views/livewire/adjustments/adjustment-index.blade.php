{{--
    UC04.03 — Danh sách phiếu kiểm kê (view của App\Livewire\Adjustments\AdjustmentIndex). Copy từ khuôn import-index:
    thanh công cụ lọc, bảng có tiêu đề bấm để sắp xếp, phân trang. Livewire yêu cầu MỘT phần tử gốc duy nhất.
    Badge trạng thái dùng chung với phiếu nhập (draft / confirmed / cancelled).
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

    {{-- Thanh công cụ: bộ lọc + lập phiếu mới --}}
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="adj-from" class="label">Từ ngày</label>
            <input id="adj-from" type="date" wire:model.live="from" class="input w-auto">
        </div>
        <div>
            <label for="adj-to" class="label">Đến ngày</label>
            <input id="adj-to" type="date" wire:model.live="to" class="input w-auto">
        </div>
        <div>
            <label for="adj-warehouse" class="label">Kho</label>
            <select id="adj-warehouse" wire:model.live="warehouse_id" class="input w-auto">
                <option value="">Tất cả</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="adj-status" class="label">Trạng thái</label>
            <select id="adj-status" wire:model.live="status" class="input w-auto">
                <option value="">Tất cả</option>
                <option value="draft">Nháp</option>
                <option value="confirmed">Đã xác nhận</option>
                <option value="cancelled">Đã hủy</option>
            </select>
        </div>
        <div>
            <label for="adj-keyword" class="label">Từ khóa</label>
            <input id="adj-keyword" type="search" wire:model.live.debounce.300ms="keyword" placeholder="Mã phiếu hoặc ghi chú…"
                   class="input max-w-xs">
        </div>

        <button type="button" wire:click="clearFilters" class="btn btn-secondary">Xóa bộ lọc</button>

        <div class="ml-auto flex items-center gap-3">
            <select wire:model.live="perPage" class="input w-auto" aria-label="Số dòng mỗi trang">
                @foreach (config('inventory.pagination.options') as $option)
                    <option value="{{ $option }}">{{ $option }} dòng / trang</option>
                @endforeach
            </select>

            @if ($canCreate)
                <a href="{{ route('adjustments.create') }}" class="btn btn-primary">+ Lập phiếu kiểm kê</a>
            @endif
        </div>
    </div>

    {{-- Bảng danh sách: bấm tiêu đề cột để sắp xếp (cột nằm trong whitelist StockAdjustmentFilter::SORTABLE) --}}
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach (['code' => 'Mã phiếu', 'adjustment_date' => 'Ngày kiểm kê'] as $column => $label)
                        <th scope="col" aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                                {{ $label }}
                                @if ($sort === $column)
                                    <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                    @endforeach
                    <th scope="col">Kho</th>
                    <th scope="col" aria-sort="{{ $sort === 'status' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button type="button" wire:click="sortBy('status')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                            Trạng thái
                            @if ($sort === 'status')
                                <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th scope="col">Người lập</th>
                    <th scope="col">Ghi chú</th>
                    <th scope="col" class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($adjustments as $adjustment)
                    <tr wire:key="adjustment-{{ $adjustment->id }}">
                        <td class="whitespace-nowrap font-medium text-slate-900">{{ $adjustment->code }}</td>
                        <td class="whitespace-nowrap">{{ $adjustment->adjustment_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-slate-600">{{ $adjustment->warehouse?->name ?? '—' }}</td>
                        <td>@include('livewire.adjustments.status-badge', ['status' => $adjustment->status])</td>
                        <td class="text-slate-600">{{ $adjustment->creator?->name ?? '—' }}</td>
                        <td class="text-slate-600">{{ $adjustment->note ?? '—' }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('adjustments.edit', $adjustment) }}" class="btn btn-secondary">
                                {{ ($adjustment->status instanceof \BackedEnum ? $adjustment->status->value : $adjustment->status) === 'draft' ? 'Nhập số đếm' : 'Xem' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $from !== '' || $to !== '' || $status !== '' || $warehouse_id !== '')
                                Không có phiếu kiểm kê nào khớp bộ lọc.
                            @else
                                Chưa có phiếu kiểm kê nào.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $adjustments->links('partials.pagination') }}
    </div>
</div>