<?php

namespace App\Livewire\Warehouses;

use App\Exceptions\BusinessConflictException;
use App\Http\Filters\WarehouseFilter;
use App\Http\Requests\StoreWarehouseRequest;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC02.03 — Quản lý kho.
 *
 * Livewire action -> Policy + StoreWarehouseRequest rules -> WarehouseService -> Eloquent.
 * Các action luôn nạp lại bản ghi và kiểm tra quyền vì public properties do trình duyệt gửi lên.
 */
class WarehouseIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    /** '' = tất cả, 'active' = đang hoạt động, 'inactive' = ngừng hoạt động */
    #[Url(except: '')]
    public string $status = '';

    #[Url(except: 'code')]
    public string $sort = 'code';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public int $perPage = 20;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $address = '';

    public ?int $deletingId = null;

    public string $deletingName = '';

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorize('viewAny', Warehouse::class);

        $this->perPage = (int) config('inventory.pagination.default', 20);
    }

    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, WarehouseFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Warehouse::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $warehouse = Warehouse::query()->findOrFail($id);
        $this->authorize('update', $warehouse);

        $this->resetForm();
        $this->editingId = $warehouse->id;
        $this->code = $warehouse->code;
        $this->name = $warehouse->name;
        $this->address = (string) $warehouse->address;
        $this->showForm = true;
    }

    public function save(): void
    {
        $warehouse = $this->editingId !== null ? Warehouse::query()->findOrFail($this->editingId) : null;

        if ($warehouse === null) {
            $this->authorize('create', Warehouse::class);
        } else {
            $this->authorize('update', $warehouse);
        }

        $this->code = strtoupper(trim($this->code));
        $this->name = trim($this->name);
        $this->address = trim($this->address);

        $request = new StoreWarehouseRequest;
        $data = $this->validate(
            StoreWarehouseRequest::rulesFor($warehouse),
            $request->messages(),
            $request->attributes(),
        );
        $data['address'] = ($data['address'] ?? '') !== '' ? $data['address'] : null;

        $service = app(WarehouseService::class);

        if ($warehouse === null) {
            $service->create($data);
            $this->flash('Đã thêm kho.');
        } else {
            $service->update($warehouse, $data);
            $this->flash('Đã cập nhật kho.');
        }

        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    public function toggle(int $id): void
    {
        $warehouse = Warehouse::query()->findOrFail($id);
        $this->authorize('update', $warehouse);

        try {
            $warehouse = app(WarehouseService::class)->toggle($warehouse);
            $this->flash($warehouse->is_active ? 'Đã kích hoạt kho.' : 'Đã ngừng hoạt động kho.');
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function confirmDelete(int $id): void
    {
        $warehouse = Warehouse::query()->findOrFail($id);
        $this->authorize('delete', $warehouse);

        $this->deletingId = $warehouse->id;
        $this->deletingName = $warehouse->name;
    }

    public function delete(): void
    {
        abort_if($this->deletingId === null, 404);

        $warehouse = Warehouse::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $warehouse);

        try {
            app(WarehouseService::class)->delete($warehouse);
            $this->flash('Đã xóa kho.');
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');
        }

        $this->cancelDelete();
        $this->resetPage();
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
        $this->deletingName = '';
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        $params = [
            'keyword' => $this->keyword,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ];

        if ($this->status === 'active') {
            $params['is_active'] = true;
        } elseif ($this->status === 'inactive') {
            $params['is_active'] = false;
        }

        $warehouses = app(WarehouseService::class)->list($params);

        return view('livewire.warehouses.warehouse-index', ['warehouses' => $warehouses]);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'code', 'name', 'address']);
        $this->resetValidation();
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}
