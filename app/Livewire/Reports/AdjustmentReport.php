<?php

namespace App\Livewire\Reports;

use App\Enums\AdjustmentReason;
use App\Models\Warehouse;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * UC05.03 — Báo cáo kiểm kê / điều chỉnh (view: resources/views/livewire/reports/adjustment-report.blade.php).
 *
 * Cùng khuôn với StockSummaryReport: chỉ đọc (BR05.03-6), các ô nhập chỉ có tác dụng khi bấm "Xem báo cáo" (apply()), lúc đó mới
 * chép sang $applied; ReportService::adjustmentReport() kiểm tra lại mọi giá trị nên giá trị lạ không gây lỗi 500.
 */
class AdjustmentReport extends Component
{
    use AuthorizesRequests;

    /** Số dòng tối đa vẽ ra trang; xem đủ thì dùng "Xuất Excel" (báo cáo vẫn tổng hợp trên TOÀN BỘ dòng). */
    public const MAX_ROWS = 200;

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(as: 'warehouse', except: '')]
    public string $warehouseId = '';

    #[Url(except: '')]
    public string $reason = '';

    /** Tích = hiện cả dòng khớp (không chênh lệch). Mặc định chỉ hiện dòng có chênh lệch. */
    #[Url(as: 'all', except: false)]
    public bool $showMatching = false;

    /** @var array{from: string, to: string, warehouse_id: string|null, reason: string|null, only_diff: bool} */
    public array $applied = [];

    public function mount(): void
    {
        $this->authorize('view-reports');

        $this->from = $this->from !== '' ? $this->from : Carbon::now()->startOfMonth()->toDateString();
        $this->to = $this->to !== '' ? $this->to : Carbon::now()->toDateString();

        $this->syncApplied();
    }

    public function apply(): void
    {
        $this->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'from.required' => 'Hãy chọn "Từ ngày".',
            'from.date_format' => '"Từ ngày" không hợp lệ.',
            'to.required' => 'Hãy chọn "Đến ngày".',
            'to.date_format' => '"Đến ngày" không hợp lệ.',
            'to.after_or_equal' => '"Đến ngày" phải sau hoặc bằng "Từ ngày".',
        ]);

        $this->syncApplied();
    }

    public function render(): View
    {
        $report = app(ReportService::class)->adjustmentReport($this->applied);
        $f = $report['filters'];

        return view('livewire.reports.adjustment-report', [
            'report' => $report,
            'rows' => array_slice($report['rows'], 0, self::MAX_ROWS),
            'truncated' => count($report['rows']) > self::MAX_ROWS,
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'reasons' => AdjustmentReason::cases(),
            'warehouseName' => $f['warehouse_id'] !== null ? Warehouse::query()->whereKey($f['warehouse_id'])->value('name') : null,
            'exportUrl' => route('reports.adjustments.export', array_filter([
                'from' => $f['from'],
                'to' => $f['to'],
                'warehouse' => $f['warehouse_id'],
                'reason' => $f['reason'],
                'diff' => $f['only_diff'] ? '1' : '0',
            ], fn ($v) => $v !== null)),
        ]);
    }

    private function syncApplied(): void
    {
        $this->applied = [
            'from' => $this->from,
            'to' => $this->to,
            'warehouse_id' => $this->warehouseId !== '' ? $this->warehouseId : null,
            'reason' => $this->reason !== '' ? $this->reason : null,
            'only_diff' => ! $this->showMatching,
        ];
    }
}
