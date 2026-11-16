{{--
    UC06.02 — Nhật ký hoạt động (view của App\Livewire\ActivityLogs\ActivityLogIndex). Bố cục theo bản mock "Nhật ký":
    thanh lọc (từ ngày, đến ngày, người dùng, hành động), bảng 5 cột Thời gian / Người dùng / Hành động / Đối tượng / Địa chỉ IP,
    phân trang. Khác mock: thêm cột "Mô tả" (cột description) kèm chi tiết giá trị cũ/mới (properties) bấm để mở.
    Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
--}}
<div>
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs text-slate-500">
            Từ ngày
            <input type="date" wire:model.live="from" class="input w-auto" aria-label="Từ ngày">
        </label>

        <label class="flex flex-col gap-1 text-xs text-slate-500">
            Đến ngày
            <input type="date" wire:model.live="to" class="input w-auto {{ $dateError ? 'input-invalid' : '' }}" aria-label="Đến ngày">
        </label>

        <label class="flex flex-col gap-1 text-xs text-slate-500">
            Người dùng
            <select wire:model.live="userId" class="input w-auto" aria-label="Lọc theo người dùng">
                <option value="">Tất cả</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1 text-xs text-slate-500">
            Hành động
            <select wire:model.live="action" class="input w-auto" aria-label="Lọc theo hành động">
                <option value="">Tất cả</option>
                @foreach ($actions as $code)
                    <option value="{{ $code }}">{{ $code }}</option>
                @endforeach
            </select>
        </label>

        <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="Tìm trong mô tả…"
               class="input w-full sm:w-56" aria-label="Tìm trong mô tả hoặc tên hành động">

        @if ($keyword !== '' || $from !== '' || $to !== '' || $userId !== '' || $action !== '')
            <button type="button" wire:click="clearFilters" class="btn btn-secondary">Xóa lọc</button>
        @endif
    </div>

    @if ($dateError)
        <p class="field-error mb-3">"Đến ngày" phải sau hoặc bằng "Từ ngày"; đang tạm bỏ qua "Đến ngày".</p>
    @endif

    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Thời gian</th>
                    <th scope="col">Người dùng</th>
                    <th scope="col">Hành động</th>
                    <th scope="col">Đối tượng</th>
                    <th scope="col">Mô tả</th>
                    <th scope="col">Địa chỉ IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}">
                        <td class="whitespace-nowrap tabular-nums">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="whitespace-nowrap">
                            @if ($log->user)
                                {{ $log->user->name }}
                            @else
                                <span class="italic text-slate-500">(không xác định)</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            <span class="badge badge-{{ $log->actionBadge() }} font-mono">{{ $log->action }}</span>
                        </td>
                        <td class="whitespace-nowrap">
                            @if ($log->subject_type)
                                {{ $log->subject_type }}@if ($log->subject_id) #{{ $log->subject_id }}@endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="max-w-md text-sm">
                            {{ $log->description ?? '—' }}
                            @if (! empty($log->properties))
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-xs text-brand hover:underline">Chi tiết</summary>
                                    <pre class="mt-1 overflow-x-auto rounded bg-slate-50 p-2 text-xs text-slate-700">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                        </td>
                        <td class="whitespace-nowrap font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-500">
                            @if ($keyword !== '' || $from !== '' || $to !== '' || $userId !== '' || $action !== '')
                                Không có bản ghi nào khớp bộ lọc.
                            @else
                                Chưa có nhật ký nào.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links('partials.pagination') }}
    </div>
</div>
