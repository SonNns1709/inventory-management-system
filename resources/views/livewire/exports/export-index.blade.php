{{--
    UC03.03 — Danh sách phiếu xuất kho (view của App\Livewire\Exports\ExportIndex), bố cục theo mock_imports.html.
    Livewire yêu cầu MỘT phần tử gốc duy nhất.
--}}
<div>
    @php
        $canCreate = auth()->user()->can('create', \App\Models\Export::class);

        // key = cột trong ExportFilter::SORTABLE (null = không sắp xếp được)
        $columns = [
            ['sort' => 'code', 'label' => 'Mã phiếu', 'class' => ''],
            ['sort' => 'export_date', 'label' => 'Ngày xuất', 'class' => ''],
            ['sort' => null, 'label' => 'Kho xuất', 'class' => ''],
            ['sort' => null, 'label' => 'Lý do xuất', 'class' => ''],
            ['sort' => 'total_cost', 'label' => 'Tổng giá vốn (đ)', 'class' => 'text-right'],
            ['sort' => 'status', 'label' => 'Trạng thái', 'class' => ''],
            ['sort' => null, 'label' => 'Người lập', 'class' => ''],
        ];
    @endphp

    {{-- Thanh công cụ: bộ lọc (trái) — nút lập phiếu (phải) --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm theo mã phiếu…"
               class="input max-w-48" aria-label="Tìm phiếu xuất">

        <label class="flex items-center gap-2 text-sm text-slate-600">
            Từ <input type="date" wire:model.live="from" class="input w-auto" aria-label="Từ ngày">
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            Đến <input type="date" wire:model.live="to" class="input w-auto" aria-label="Đến ngày">
        </label>

        <select wire:model.live="warehouseId" class="input w-auto" aria-label="Lọc theo kho xuất">
            <option value="">Kho: Tất cả</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="reason" class="input w-auto" aria-label="Lọc theo lý do xuất">
            <option value="">Lý do: Tất cả</option>
            <option value="sale">Bán hàng</option>
            <option value="transfer">Chuyển kho</option>
        </select>

        <select wire:model.live="status" class="input w-auto" aria-label="Lọc theo trạng thái">
            <option value="">Trạng thái: Tất cả</option>
            <option value="draft">Nháp</option>
            <option value="confirmed">Đã xác nhận</option>
            <option value="cancelled">Đã hủy</option>
        </select>

        @if ($keyword !== '' || $from !== '' || $to !== '' || $warehouseId !== '' || $reason !== '' || $status !== '')
            <button type="button" wire:click="clearFilters" class="btn btn-secondary">Xóa lọc</button>
        @endif

        <div class="ml-auto flex items-center gap-3">
            <select wire:model.live="perPage" class="input w-auto" aria-label="Số dòng mỗi trang">
                @foreach (config('inventory.pagination.options') as $option)
                    <option value="{{ $option }}">{{ $option }} dòng / trang</option>
                @endforeach
            </select>

            @if ($canCreate)
                <a href="{{ route('exports.create') }}" class="btn btn-primary">+ Lập phiếu xuất</a>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        @if ($column['sort'])
                            <th scope="col" class="{{ $column['class'] }}"
                                aria-sort="{{ $sort === $column['sort'] ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <button type="button" wire:click="sortBy('{{ $column['sort'] }}')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                                    {{ $column['label'] }}
                                    @if ($sort === $column['sort'])
                                        <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </button>
                            </th>
                        @else
                            <th scope="col" class="{{ $column['class'] }}">{{ $column['label'] }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($exports as $export)
                    @php
                        $reasonValue = $export->reason instanceof \BackedEnum ? $export->reason->value : (string) $export->reason;
                    @endphp
                    <tr wire:key="export-{{ $export->id }}">
                        <td class="whitespace-nowrap">
                            <a href="{{ route('exports.show', $export->id) }}" class="font-medium text-brand hover:underline">{{ $export->code }}</a>
                        </td>
                        <td class="whitespace-nowrap text-slate-600">{{ \Illuminate\Support\Carbon::parse($export->export_date)->format('d/m/Y') }}</td>
                        <td class="text-slate-600">{{ $export->warehouse?->name ?? '—' }}</td>
                        <td class="text-slate-600">
                            @if ($reasonValue === 'transfer')
                                Chuyển kho → {{ $export->destWarehouse?->name ?? '—' }}
                            @else
                                Bán hàng
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-right">{{ number_format((float) $export->total_cost, 0, ',', '.') }}</td>
                        <td>@include('livewire.exports.status-badge', ['status' => $export->status])</td>
                        <td class="text-slate-600">{{ $export->creator?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $from !== '' || $to !== '' || $warehouseId !== '' || $reason !== '' || $status !== '')
                                Không tìm thấy phiếu xuất phù hợp.
                            @else
                                Chưa có phiếu xuất nào.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-2 text-xs text-slate-500">
        Hiển thị {{ $exports->firstItem() ?? 0 }} - {{ $exports->lastItem() ?? 0 }} / {{ $exports->total() }} phiếu
    </p>

    <div class="mt-2">
        {{ $exports->links('partials.pagination') }}
    </div>
</div>