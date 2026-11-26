{{--
    UC04.03 — Phiếu kiểm kê: nhập số đếm, làm mới snapshot, xác nhận điều chỉnh, hủy (view của App\Livewire\Adjustments\AdjustmentSheet).
    Mock: mock_adjustment.html. $editable = false (đã xác nhận / hủy hoặc không có quyền sửa) -> khóa ô nhập, ẩn nút ghi.
    Livewire yêu cầu MỘT phần tử gốc duy nhất cho mỗi component.
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

    <form wire:submit="save" class="card space-y-4" novalidate>
        <h2 class="flex items-center gap-2 text-lg font-semibold text-brand">
            Phiếu kiểm kê và điều chỉnh tồn kho
            @include('livewire.adjustments.status-badge', ['status' => $status])
        </h2>

        {{-- Đầu phiếu (chỉ đọc: kho và snapshot đã chốt lúc lập phiếu) --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <span class="label">Mã phiếu</span>
                <div class="input bg-slate-50 text-slate-600">{{ $adjustment->code }}</div>
            </div>
            <div>
                <span class="label">Kho kiểm kê</span>
                <div class="input bg-slate-50 text-slate-600">{{ $adjustment->warehouse?->name ?? '—' }}</div>
            </div>
            <div>
                <span class="label">Ngày kiểm kê</span>
                <div class="input bg-slate-50 text-slate-600">{{ $adjustment->adjustment_date?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <span class="label">Phạm vi</span>
                <div class="input bg-slate-50 text-slate-600">{{ $summary['lines'] }} sản phẩm</div>
            </div>
        </div>

        <div>
            <label for="adj-note" class="label">Ghi chú</label>
            <input id="adj-note" type="text" wire:model="note" maxlength="255" @disabled(! $editable)
                   @class(['input', 'input-invalid' => $errors->has('note')])>
            @error('note')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        @error('items')
            <p class="field-error">{{ $message }}</p>
        @enderror

        {{-- Bảng đối chiếu: SL hệ thống (snapshot) so với SL thực đếm. Dòng lệch bắt buộc có lý do (BR04.03-5) --}}
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col">Sản phẩm</th>
                        <th scope="col" class="text-right">SL hệ thống (snapshot)</th>
                        <th scope="col" class="text-right">SL thực đếm <span class="text-red-600">*</span></th>
                        <th scope="col" class="text-right">Chênh lệch</th>
                        <th scope="col">Lý do (bắt buộc nếu lệch)</th>
                        <th scope="col">Ghi chú dòng</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $i => $item)
                        <tr wire:key="item-{{ $i }}" @class(['bg-red-50' => ($rows[$i]['diff'] ?? 0) !== 0 && $rows[$i]['diff'] !== null])>
                            <td class="whitespace-nowrap text-slate-500">{{ $rows[$i]['sku'] ?? '—' }}</td>
                            <td class="font-medium text-slate-900">{{ $rows[$i]['name'] ?? '—' }}</td>
                            <td class="text-right text-slate-600">
                                {{ $rows[$i]['system'] !== null ? \App\Support\Format::number($rows[$i]['system']) : '—' }}
                            </td>

                            <td class="text-right">
                                @if ($editable)
                                    <input type="number" min="0" step="1" inputmode="numeric" wire:model.live.debounce.300ms="items.{{ $i }}.counted_qty"
                                           aria-label="Số lượng thực đếm dòng {{ $i + 1 }}"
                                           @class(['input w-24 text-right', 'input-invalid' => $errors->has("items.$i.counted_qty")])>
                                @else
                                    {{ \App\Support\Format::number((int) $item['counted_qty']) }}
                                @endif
                                @error("items.$i.counted_qty")
                                    <p class="field-error text-left">{{ $message }}</p>
                                @enderror
                            </td>

                            <td @class([
                                'text-right font-semibold',
                                'text-red-700' => ($rows[$i]['diff'] ?? 0) < 0,
                                'text-green-700' => ($rows[$i]['diff'] ?? 0) > 0,
                            ])>{{ $rows[$i]['diffText'] }}</td>

                            <td class="min-w-[10rem]">
                                @if ($editable)
                                    <select wire:model="items.{{ $i }}.reason" aria-label="Lý do dòng {{ $i + 1 }}"
                                            @class(['input', 'input-invalid' => $errors->has("items.$i.reason")])>
                                        <option value="">— Chọn lý do —</option>
                                        @foreach ($reasons as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{ $reasons[$item['reason']] ?? '—' }}
                                @endif
                                @error("items.$i.reason")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </td>

                            <td class="min-w-[10rem]">
                                @if ($editable)
                                    <input type="text" maxlength="255" wire:model="items.{{ $i }}.note" aria-label="Ghi chú dòng {{ $i + 1 }}"
                                           @class(['input', 'input-invalid' => $errors->has("items.$i.note")])>
                                @else
                                    {{ $item['note'] !== '' ? $item['note'] : '—' }}
                                @endif
                                @error("items.$i.note")
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tổng hợp chênh lệch (giá trị theo giá vốn bình quân tại thời điểm lập phiếu) --}}
        <div class="grid gap-3 lg:grid-cols-2">
            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $summary['diffLines'] }} / {{ $summary['lines'] }} dòng có chênh lệch.
                Tổng chênh lệch: <strong>{{ $summary['totalDiffText'] }}</strong> đơn vị.
                Giá trị chênh lệch theo giá vốn: <strong>{{ $summary['totalValueText'] }} đ</strong>.
                Tỷ lệ sai lệch: {{ $summary['ratio'] }}% số dòng đã kiểm.
            </div>

            @if ($isDraft)
                <div class="rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                    Số liệu hệ thống là ảnh chụp lúc lập phiếu. Nếu tồn thay đổi trước khi xác nhận, hệ thống yêu cầu
                    <strong>làm mới số liệu</strong> rồi kiểm tra lại chênh lệch.
                </div>
            @endif
        </div>

        {{-- Nút thao tác --}}
        <div class="flex flex-wrap items-center gap-2">
            @if ($editable)
                <button type="submit" class="btn btn-secondary" wire:loading.attr="disabled" wire:target="save">Lưu nháp</button>
                <button type="button" wire:click="refreshSnapshot" class="btn btn-secondary" wire:loading.attr="disabled" wire:target="refreshSnapshot">
                    Làm mới số liệu hệ thống
                </button>
                <button type="button" wire:click="askConfirm" class="btn btn-primary" wire:loading.attr="disabled">Xác nhận điều chỉnh</button>
            @endif

            @if ($canCancel)
                <button type="button" wire:click="askCancel" class="btn btn-danger-outline">Hủy phiếu</button>
            @endif

            <a href="{{ route('adjustments.index') }}" class="btn btn-secondary ml-auto">← Danh sách kiểm kê</a>
        </div>
    </form>

    {{-- Hộp thoại xác nhận điều chỉnh: sau bước này tồn kho bị thay đổi thật, phiếu không thể sửa --}}
    @if ($dialog === 'confirm')
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="adj-confirm-title"
             wire:keydown.escape="closeDialog">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="adj-confirm-title" class="text-lg font-semibold text-brand">Xác nhận điều chỉnh tồn kho</h2>
                <p class="text-sm">
                    Phiếu sẽ được lưu rồi xác nhận: <strong>{{ $summary['diffLines'] }} dòng</strong> chênh lệch
                    (tổng <strong>{{ $summary['totalDiffText'] }}</strong> đơn vị, giá trị <strong>{{ $summary['totalValueText'] }} đ</strong>)
                    sẽ được điều chỉnh vào tồn kho và ghi vào thẻ kho kèm lý do.
                    Sau khi xác nhận <strong>không thể sửa hay hủy</strong> phiếu.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeDialog" class="btn btn-secondary">Quay lại</button>
                    <button type="button" wire:click="confirmAdjustment" class="btn btn-primary" wire:loading.attr="disabled" wire:target="confirmAdjustment">Xác nhận</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Hộp thoại hủy phiếu (chỉ phiếu nháp, không đụng tới tồn kho) --}}
    @if ($dialog === 'cancel')
        <div class="fixed inset-0 z-30 flex items-center justify-center bg-black/40 p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="adj-cancel-title"
             wire:keydown.escape="closeDialog">
            <div class="card w-full max-w-md space-y-4">
                <h2 id="adj-cancel-title" class="text-lg font-semibold text-red-700">Hủy phiếu kiểm kê</h2>
                <p class="text-sm">
                    Bạn có chắc muốn hủy phiếu <strong>{{ $adjustment->code }}</strong>? Thao tác này không thể hoàn tác.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeDialog" class="btn btn-secondary">Quay lại</button>
                    <button type="button" wire:click="cancelAdjustment" class="btn btn-danger" wire:loading.attr="disabled" wire:target="cancelAdjustment">Hủy phiếu</button>
                </div>
            </div>
        </div>
    @endif
</div>