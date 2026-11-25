<?php

namespace App\Livewire\Exports;

use App\Exceptions\BusinessConflictException;
use App\Models\Export;
use App\Services\ExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * UC03.03 / BR03.03-6 — chi tiết phiếu xuất; UC03.02 xác nhận; UC03.04 hủy nháp.
 * Chỉ giữ id của phiếu; mọi action nạp lại bản ghi từ CSDL và authorize lại.
 */
class ExportShow extends Component
{
    use AuthorizesRequests;

    public int $exportId;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(int $exportId): void
    {
        $export = Export::query()->findOrFail($exportId);
        $this->authorize('view', $export);

        $this->exportId = $export->id;
        $this->notice = session('notice');
        $this->noticeType = session('noticeType', 'success');
    }

    /** BR03.02-3/4: thiếu tồn thì Service ném BusinessConflictException (409 ở API) -> hiện thành thông báo lỗi. */
    public function confirm(): void
    {
        $export = Export::query()->findOrFail($this->exportId);
        $this->authorize('confirm', $export);

        try {
            app(ExportService::class)->confirm($export, auth()->user());
            $this->flash('Đã xác nhận xuất kho.');
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function cancel(): void
    {
        $export = Export::query()->findOrFail($this->exportId);
        $this->authorize('cancel', $export);

        try {
            app(ExportService::class)->cancel($export, auth()->user());
            $this->flash('Đã hủy phiếu xuất nháp.');
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        return view('livewire.exports.export-show', [
            'export' => app(ExportService::class)->getDetail($this->exportId),
        ]);
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}