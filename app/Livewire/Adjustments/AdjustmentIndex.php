<?php

namespace App\Livewire\Adjustments;

use App\Http\Filters\StockAdjustmentFilter;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\StockAdjustmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC04.03 — Tra cứu phiếu kiểm kê và điều chỉnh tồn kho. Copy khuôn ImportIndex / CategoryIndex:
 *
 *   thao tác Livewire -> $this->authorize() (Policy) -> StockAdjustmentService::search() (StockAdjustmentFilter whitelist)
 *
 * Màn hình chỉ đọc; lập phiếu ở AdjustmentCreate, nhập số đếm / xác nhận / hủy ở AdjustmentSheet.
 */
class AdjustmentIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $warehouse_id = '';

    #[Url(except: 'adjustment_date')]
    public string $sort = 'adjustment_date';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    public int $perPage = 20;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorize('viewAny', StockAdjustment::class);

        $this->perPage = (int) config('inventory.pagination.default', 20);

        if ($message = session('adjustment_notice')) {
            $this->notice = $message;
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['keyword', 'from', 'to', 'status', 'warehouse_id', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, StockAdjustmentFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'from', 'to', 'status', 'warehouse_id']);
        $this->resetPage();
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        $params = array_filter([
            'keyword' => trim($this->keyword),
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status,
            'warehouse_id' => $this->warehouse_id,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ], fn ($value) => $value !== '' && $value !== null);

        return view('livewire.adjustments.adjustment-index', [
            'adjustments' => app(StockAdjustmentService::class)->search($params),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'canCreate' => auth()->user()->can('create', StockAdjustment::class),
        ]);
    }
}
