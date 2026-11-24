{{--
    UC03.03 — Danh sách phiếu nhập kho (view của App\Livewire\Imports\ImportIndex). Copy từ khuôn category-index:
    thanh công cụ, bảng có tiêu đề bấm để sắp xếp, phân trang. Mock: mock_imports.html.
    Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
--}}
<div>
    {{-- Thông báo kết quả thao tác (ví dụ vừa hủy phiếu) --}}
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

    {{-- Thanh công cụ: bộ lọc (BR03.03-2) + lập phiếu mới --}}
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="import-from" class="label">Từ ngày</label>
            <input id="import-from" type="date" wire:model.live="from" class="input w-auto">
        </div>
        <div>
            <label for="import-to" class="label">Đến ngày</label>
            <input id="import-to" type="date" wire:model.live="to" class="input w-auto">
        </div>
        <div>
            <label for="import-warehouse" class="label">Kho</label>
            <select id="import-warehouse" wire:model.live="warehouse_id" class="input w-auto">
                <option value="">Tất cả</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="import-supplier" class="label">Nhà cung cấp</label>
            <select id="import-supplier" wire:model.live="supplier_id" class="input w-auto max-w-[14rem]">
                <option value="">Tất cả</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="import-status" class="label">Trạng thái</label>
            <select id="import-status" wire:model.live="status" class="input w-auto">
                <option value="">Tất cả</option>
                <option value="draft">Nháp</option>
                <option value="confirmed">Đã xác nhận</option>
                <option value="cancelled">Đã hủy</option>
            </select>
        </div>
        <div>
            <label for="import-keyword" class="label">Từ khóa</label>
            <input id="import-keyword" type="search" wire:model.live.debounce.300ms="keyword" placeholder="Mã phiếu hoặc ghi chú…"
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
                <a href="{{ route('imports.create') }}" class="btn btn-primary">+ Lập phiếu nhập</a>
            @endif
        </div>
    </div>

    {{-- Bảng danh sách: bấm tiêu đề cột để sắp xếp (cột nằm trong whitelist ImportFilter::SORTABLE) --}}
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach (['code' => 'Mã phiếu', 'import_date' => 'Ngày'] as $column => $label)
                        <th scope="col" aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                                {{ $label }}
                                @if ($sort === $column)
                                    <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                    @endforeach
                    <th scope="col">Nhà cung cấp</th>
                    <th scope="col">Kho</th>
                    <th scope="col" class="text-right" aria-sort="{{ $sort === 'total_amount' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button type="button" wire:click="sortBy('total_amount')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                            Tổng tiền (đ)
                            @if ($sort === 'total_amount')
                                <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th scope="col" aria-sort="{{ $sort === 'status' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                        <button type="button" wire:click="sortBy('status')" class="inline-flex items-center gap-1 uppercase tracking-wide">
                            Trạng thái
                            @if ($sort === 'status')
                                <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th scope="col">Người lập</th>
                    <th scope="col" class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($imports as $import)
                    <tr wire:key="import-{{ $import->id }}">
                        <td class="whitespace-nowrap font-medium text-slate-900">{{ $import->code }}</td>
                        <td class="whitespace-nowrap">{{ $import->import_date->format('d/m/Y') }}</td>
                        <td class="text-slate-600">{{ $import->supplier?->name ?? '—' }}</td>
                        <td class="text-slate-600">{{ $import->warehouse?->name ?? '—' }}</td>
                        <td class="text-right">{{ \App\Support\Format::number((float) $import->total_amount) }}</td>
                        <td>@include('livewire.imports.status-badge', ['status' => $import->status])</td>
                        <td class="text-slate-600">{{ $import->creator?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('imports.edit', $import) }}" class="btn btn-secondary">
                                {{ ($import->status instanceof \BackedEnum ? $import->status->value : $import->status) === 'draft' ? 'Sửa' : 'Xem' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $from !== '' || $to !== '' || $status !== '' || $supplier_id !== '' || $warehouse_id !== '')
                                Không có phiếu nhập nào khớp bộ lọc.
                            @else
                                Chưa có phiếu nhập nào.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $imports->links('partials.pagination') }}
    </div>
</div>