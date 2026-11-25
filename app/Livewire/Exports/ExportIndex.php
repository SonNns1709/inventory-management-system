<?php

namespace App\Livewire\Exports;

use App\Http\Filters\ExportFilter;
use App\Models\Export;
use App\Models\Warehouse;
use App\Services\ExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ExportIndex extends Component
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
    public string $reason = '';

    #[Url(as: 'warehouse', except: '')]
    public string $warehouseId = '';

    #[Url(except: 'export_date')]
    public string $sort = 'export_date';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->authorize('viewAny', Export::class);
        $this->perPage = (int) config('inventory.pagination.default', 20);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['keyword', 'from', 'to', 'status', 'reason', 'warehouseId', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ExportFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'from', 'to', 'status', 'reason', 'warehouseId']);
        $this->resetPage();
    }

    public function render(): View
    {
        $params = array_filter([
            'keyword' => trim($this->keyword),
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status,
            'reason' => $this->reason,
            'warehouse_id' => $this->warehouseId,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ], fn ($value) => $value !== '' && $value !== null);

        return view('livewire.exports.export-index', [
            'exports' => app(ExportService::class)->search($params),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'canCreate' => auth()->user()->can('create', Export::class),
        ]);
    }
}
