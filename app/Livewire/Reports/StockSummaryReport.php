<?php

namespace App\Livewire\Reports;

use App\Models\Category;
use App\Models\Warehouse;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * UC05.02 — Báo cáo Nhập – Xuất – Tồn (view: resources/views/livewire/reports/stock-summary-report.blade.php).
 *
 * Chỉ đọc (BR05.02-9): dữ liệu do ReportService::stockSummary() tổng hợp, component không ghi gì vào CSDL.
 * Báo cáo tổng hợp nhiều dòng nên KHÔNG chạy lại sau mỗi lần đổi ô lọc: các ô nhập ($from, $to, ...) chỉ có tác dụng khi bấm
 * "Xem báo cáo" (apply()), lúc đó mới chép sang $applied. Nhờ vậy bộ lọc đã áp dụng, tiêu đề báo cáo và nút "Xuất Excel" luôn khớp nhau.
 * Mọi giá trị đều đi qua ReportService (kiểm tra lại), giá trị lạ không gây lỗi 500.
 */
class StockSummaryReport extends Component
{
    use AuthorizesRequests;

    // Ô nhập — đồng bộ lên URL để chia sẻ đường dẫn; '' ở kho / danh mục nghĩa là "Tất cả"
    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(as: 'warehouse', except: '')]
    public string $warehouseId = '';

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    /** @var array{from: string, to: string, warehouse_id: string|null, category_id: string|null} bộ lọc đã bấm "Xem báo cáo" */
    public array $applied = [];

    public function mount(): void
    {
        $this->authorize('view-reports');

        // Mặc định: từ ngày 1 của tháng này đến hôm nay (khớp mặc định của ReportService)
        $this->from = $this->from !== '' ? $this->from : Carbon::now()->startOfMonth()->toDateString();
        $this->to = $this->to !== '' ? $this->to : Carbon::now()->toDateString();

        $this->syncApplied();
    }

    /** Bấm "Xem báo cáo": kiểm tra khoảng ngày rồi áp dụng bộ lọc. */
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
        $report = app(ReportService::class)->stockSummary($this->applied);

        return view('livewire.reports.stock-summary-report', [
            'report' => $report,
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'warehouseName' => $report['filters']['warehouse_id'] !== null
                ? Warehouse::query()->whereKey($report['filters']['warehouse_id'])->value('name')
                : null,
            'exportUrl' => route('reports.stock-summary.export', array_filter([
                'from' => $report['filters']['from'],
                'to' => $report['filters']['to'],
                'warehouse' => $report['filters']['warehouse_id'],
                'category' => $report['filters']['category_id'],
            ], fn ($v) => $v !== null)),
        ]);
    }

    private function syncApplied(): void
    {
        // Chuỗi rỗng phải đổi thành null để ReportService hiểu là "không lọc"
        $this->applied = [
            'from' => $this->from,
            'to' => $this->to,
            'warehouse_id' => $this->warehouseId !== '' ? $this->warehouseId : null,
            'category_id' => $this->categoryId !== '' ? $this->categoryId : null,
        ];
    }
}
