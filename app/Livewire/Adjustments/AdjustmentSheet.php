<?php

namespace App\Livewire\Adjustments;

use App\Enums\AdjustmentReason;
use App\Exceptions\BusinessConflictException;
use App\Http\Requests\UpdateStockAdjustmentRequest;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use App\Support\Format;
use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * UC04.03 — Bước 2: nhập số đếm thực tế, làm mới snapshot, xác nhận điều chỉnh tồn, hủy phiếu.
 *
 *   thao tác Livewire -> $this->authorize() (Policy) -> UpdateStockAdjustmentRequest::rules() (dùng chung với API)
 *                     -> StockAdjustmentService (saveCounts / refresh / confirm / cancel) -> Eloquent
 *
 * Quy tắc hiển thị:
 *  - system_qty là SNAPSHOT trong CSDL (BR04.03-2): luôn đọc lại từ CSDL ở mỗi lần render, KHÔNG nhận từ trình duyệt.
 *  - diff = counted − system (BR04.03-4); diff ≠ 0 thì bắt buộc có lý do (BR04.03-5): kiểm tra sớm ở đây để báo lỗi dưới từng ô,
 *    Service vẫn kiểm tra lại (422).
 *  - BR04.03-3: tồn đã đổi sau khi lập phiếu thì Service từ chối xác nhận (409) -> hiện thông báo, người dùng bấm "Làm mới số liệu".
 *
 * Lưu ý bảo mật: $adjustmentId / $status do trình duyệt gửi lên nên KHÔNG tin được — mọi action nạp lại phiếu từ CSDL và gọi lại
 * Policy; việc "chỉ sửa khi draft" do Service chốt (409).
 */
class AdjustmentSheet extends Component
{
    use AuthorizesRequests;

    /** Nhãn lý do chênh lệch (giá trị enum AdjustmentReason -> tiếng Việt). */
    private const REASON_LABELS = [
        'loss' => 'Thất lạc',
        'damaged' => 'Hư hỏng',
        'shrinkage' => 'Hao hụt',
        'other' => 'Khác',
    ];

    public int $adjustmentId;

    /** Giá trị enum trạng thái dạng chuỗi: draft | confirmed | cancelled (chỉ để hiển thị). */
    public string $status = 'draft';

    public string $note = '';

    /** @var array<int, array{product_id: string, counted_qty: string, reason: string, note: string}> */
    public array $items = [];

    /** Hộp thoại đang mở: '' | 'confirm' | 'cancel' */
    public string $dialog = '';

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(int $adjustmentId): void
    {
        $adjustment = app(StockAdjustmentService::class)->getDetail($adjustmentId);
        $this->authorize('view', $adjustment);

        $this->loadFrom($adjustment);

        if ($message = session('adjustment_notice')) {
            $this->flash($message);
        }
    }

    /* ------------------------------------------------------------------ Lưu nháp */

    public function save(): void
    {
        try {
            $adjustment = $this->persist();
        } catch (ValidationException $e) {
            $this->failValidation($e);

            return;
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');

            return;
        }

        $this->loadFrom($adjustment);
        $this->flash('Đã lưu số đếm vào phiếu nháp.');
    }

    /* ------------------------------------------------------------------ Làm mới snapshot */

    /**
     * Đọc lại tồn hiện tại và cập nhật system_qty của các dòng. Số đếm đang nhập trên màn hình được GIỮ NGUYÊN
     * (không nạp lại từ CSDL) — bấm "Lưu nháp" để ghi chúng.
     */
    public function refreshSnapshot(): void
    {
        $adjustment = StockAdjustment::query()->findOrFail($this->adjustmentId);
        $this->authorize('update', $adjustment);

        try {
            app(StockAdjustmentService::class)->refresh($adjustment);
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');

            return;
        }

        $this->resetValidation();
        $this->flash('Đã làm mới số liệu hệ thống. Kiểm tra lại chênh lệch rồi bấm Lưu nháp để ghi số đếm.');
    }

    /* ------------------------------------------------------------------ Xác nhận */

    public function askConfirm(): void
    {
        $this->dialog = 'confirm';
    }

    /** Lưu số đếm mới nhất rồi xác nhận: điều chỉnh tồn thực tế và ghi thẻ kho (một transaction trong Service). */
    public function confirmAdjustment(): void
    {
        $this->dialog = '';

        try {
            $adjustment = $this->persist();
            $this->authorize('confirm', $adjustment);

            $adjustment = app(StockAdjustmentService::class)->confirm($adjustment, auth()->user());
        } catch (ValidationException $e) {
            $this->failValidation($e);

            return;
        } catch (BusinessConflictException $e) {
            // BR04.03-3: tồn đã thay đổi so với snapshot -> yêu cầu làm mới số liệu trước khi xác nhận.
            $this->flash($e->getMessage(), 'error');

            return;
        }

        session()->flash('adjustment_notice', "Đã xác nhận điều chỉnh tồn kho theo phiếu {$adjustment->code}.");
        $this->redirectRoute('adjustments.edit', ['adjustment' => $adjustment->getKey()]);
    }

    /* ------------------------------------------------------------------ Hủy phiếu */

    public function askCancel(): void
    {
        $this->dialog = 'cancel';
    }

    /** Chỉ phiếu draft; người lập hoặc Admin (StockAdjustmentPolicy::cancel). Không đụng tới tồn kho. */
    public function cancelAdjustment(): void
    {
        $this->dialog = '';

        $adjustment = StockAdjustment::query()->findOrFail($this->adjustmentId);
        $this->authorize('cancel', $adjustment);

        try {
            $adjustment = app(StockAdjustmentService::class)->cancel($adjustment, auth()->user());
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');

            return;
        }

        session()->flash('adjustment_notice', "Đã hủy phiếu kiểm kê {$adjustment->code}.");
        $this->redirectRoute('adjustments.index');
    }

    public function closeDialog(): void
    {
        $this->dialog = '';
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    /* ------------------------------------------------------------------ Render */

    public function render(): View
    {
        $user = auth()->user();
        $adjustment = StockAdjustment::query()->with(['warehouse', 'creator', 'items.product'])->findOrFail($this->adjustmentId);

        $isDraft = $this->status === 'draft';
        $editable = $isDraft && $user->can('update', $adjustment);

        // system_qty / unit_cost lấy từ snapshot trong CSDL theo product_id của từng dòng.
        $snapshot = $adjustment->items->keyBy('product_id');

        $rows = [];
        $diffLines = 0;
        $totalDiff = 0;
        $totalValue = 0.0;

        foreach ($this->items as $i => $row) {
            $item = $snapshot->get((int) ($row['product_id'] ?? 0));
            $counted = ctype_digit((string) ($row['counted_qty'] ?? '')) ? (int) $row['counted_qty'] : null;
            $system = $item !== null ? (int) $item->system_qty : null;

            $diff = ($system !== null && $counted !== null) ? $counted - $system : null;
            $value = $diff !== null ? $diff * (float) $item->unit_cost : null;

            if ($diff !== null && $diff !== 0) {
                $diffLines++;
                $totalDiff += $diff;
                $totalValue += $value;
            }

            $rows[$i] = [
                'sku' => $item?->product?->sku,
                'name' => $item?->product?->name,
                'system' => $system,
                'diff' => $diff,
                'diffText' => $diff !== null ? $this->signed($diff) : '—',
            ];
        }

        $lineCount = count($this->items);

        return view('livewire.adjustments.adjustment-sheet', [
            'adjustment' => $adjustment,
            'isDraft' => $isDraft,
            'editable' => $editable,
            'canCancel' => $isDraft && $user->can('cancel', $adjustment),
            'rows' => $rows,
            'summary' => [
                'lines' => $lineCount,
                'diffLines' => $diffLines,
                'totalDiffText' => $this->signed($totalDiff),
                'totalValueText' => $this->signed($totalValue),
                'ratio' => $lineCount > 0 ? (int) round($diffLines / $lineCount * 100) : 0,
            ],
            'reasons' => $this->reasonOptions(),
        ]);
    }

    /* ------------------------------------------------------------------ Hàm phụ */

    /**
     * Kiểm tra bằng luật của API + BR04.03-5 (diff ≠ 0 thì bắt buộc có lý do) rồi lưu số đếm.
     * Ném ValidationException (lỗi dưới từng ô) hoặc BusinessConflictException (phiếu không còn là draft -> 409).
     */
    private function persist(): StockAdjustment
    {
        $adjustment = StockAdjustment::query()->with('items')->findOrFail($this->adjustmentId);
        $this->authorize('update', $adjustment);

        $this->note = trim($this->note);

        $request = new UpdateStockAdjustmentRequest;
        $data = $this->validate($request->rules(), $request->messages(), $request->attributes());

        $snapshot = $adjustment->items->keyBy('product_id');
        $errors = [];
        $items = [];

        foreach ($data['items'] as $i => $row) {
            $item = $snapshot->get((int) $row['product_id']);

            if ($item === null) {
                $errors["items.$i.product_id"] = 'Sản phẩm không thuộc phiếu kiểm kê này.';

                continue;
            }

            $counted = (int) $row['counted_qty'];
            $hasDiff = $counted !== (int) $item->system_qty;

            if ($hasDiff && ($row['reason'] ?? '') === '') {
                $errors["items.$i.reason"] = 'Bắt buộc chọn lý do khi số đếm khác số hệ thống.';
            }

            $items[] = [
                'product_id' => (int) $row['product_id'],
                'counted_qty' => $counted,
                // Lý do chỉ có nghĩa khi có chênh lệch: dòng khớp thì bỏ lý do cũ.
                'reason' => $hasDiff ? $row['reason'] : null,
                'note' => ($row['note'] ?? '') !== '' ? $row['note'] : null,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return app(StockAdjustmentService::class)->saveCounts($adjustment, [
            'note' => ($data['note'] ?? '') !== '' ? $data['note'] : null,
            'items' => $items,
        ]);
    }

    /** Nạp trạng thái form từ phiếu trong CSDL. */
    private function loadFrom(StockAdjustment $adjustment): void
    {
        $adjustment->loadMissing('items');

        $this->adjustmentId = $adjustment->getKey();
        $this->status = $adjustment->status instanceof BackedEnum ? (string) $adjustment->status->value : (string) $adjustment->status;
        $this->note = (string) $adjustment->note;
        $this->items = $adjustment->items->map(fn ($item) => [
            'product_id' => (string) $item->product_id,
            'counted_qty' => (string) $item->counted_qty,
            'reason' => $item->reason instanceof BackedEnum ? (string) $item->reason->value : (string) $item->reason,
            'note' => (string) $item->note,
        ])->values()->all();

        $this->resetValidation();
    }

    /** @return array<string, string> giá trị enum => nhãn tiếng Việt */
    private function reasonOptions(): array
    {
        $options = [];

        foreach (AdjustmentReason::values() as $value) {
            $options[$value] = self::REASON_LABELS[$value] ?? ucfirst((string) $value);
        }

        return $options;
    }

    /** -3 => "-3", 2 => "+2", 0 => "0"; số tiền dùng Format::number cho dấu phân cách hàng nghìn. */
    private function signed(int|float $number): string
    {
        $text = Format::number(abs($number));

        return match (true) {
            $number > 0 => '+'.$text,
            $number < 0 => '-'.$text,
            default => $text,
        };
    }

    private function failValidation(ValidationException $e): void
    {
        $this->setErrorBag($e->errors());
        $this->flash('Vui lòng kiểm tra lại các dòng có lỗi.', 'error');
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}