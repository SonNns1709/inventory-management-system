<?php

namespace App\Livewire\Suppliers;

use App\Exceptions\BusinessConflictException;
use App\Http\Filters\SupplierFilter;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC02.02 — Nhà cung cấp (copy khuôn Livewire\Categories\CategoryIndex, thêm lọc trạng thái và "ngừng / kích hoạt hợp tác").
 *
 *   thao tác Livewire -> $this->authorize() (Policy) -> StoreSupplierRequest::rulesFor() (dùng chung với API)
 *                     -> SupplierService (nghiệp vụ + Activity Log) -> Eloquent
 *
 * Thuộc tính public do trình duyệt gửi lên nên KHÔNG tin được: mọi action nạp lại bản ghi từ CSDL và authorize lại.
 */
class SupplierIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    private const FORM_FIELDS = ['name', 'contact_person', 'phone', 'email', 'address', 'tax_code'];

    // --- Tìm kiếm / lọc / sắp xếp / phân trang ---
    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    /** '' = tất cả, 'active' = đang hợp tác, 'inactive' = ngừng hợp tác */
    #[Url(except: '')]
    public string $status = '';

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public int $perPage = 20;

    // --- Form thêm / sửa ---
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $contact_person = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $tax_code = '';

    // --- Xác nhận xóa ---
    public ?int $deletingId = null;

    public string $deletingName = '';

    // --- Thông báo kết quả thao tác ---
    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorize('viewAny', Supplier::class);

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
        if (! in_array($column, SupplierFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Supplier::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $supplier = Supplier::query()->findOrFail($id);
        $this->authorize('update', $supplier);

        $this->resetForm();
        $this->editingId = $supplier->id;

        foreach (self::FORM_FIELDS as $field) {
            $this->{$field} = (string) $supplier->{$field};
        }

        $this->showForm = true;
    }

    /** Lưu (thêm khi editingId rỗng, ngược lại cập nhật). BR02.02-1, BR02.02-2 do StoreSupplierRequest::rulesFor() kiểm tra. */
    public function save(): void
    {
        $supplier = $this->editingId !== null ? Supplier::query()->findOrFail($this->editingId) : null;

        if ($supplier === null) {
            $this->authorize('create', Supplier::class);
        } else {
            $this->authorize('update', $supplier);
        }

        foreach (self::FORM_FIELDS as $field) {
            $this->{$field} = trim($this->{$field});
        }
        $this->email = mb_strtolower($this->email);

        $request = new StoreSupplierRequest;
        $data = $this->validate(
            StoreSupplierRequest::rulesFor($supplier),
            $request->messages(),
            $request->attributes(),
        );

        // Chuỗi rỗng -> null
        foreach (self::FORM_FIELDS as $field) {
            $data[$field] = ($data[$field] ?? '') !== '' ? $data[$field] : null;
        }

        $service = app(SupplierService::class);

        if ($supplier === null) {
            $service->create($data);
            $this->flash('Đã thêm nhà cung cấp.');
        } else {
            $service->update($supplier, $data);
            $this->flash('Đã cập nhật nhà cung cấp.');
        }

        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->resetForm();
    }

    /** BR02.02-5: ngừng / kích hoạt hợp tác chỉ đổi is_active, không xóa dữ liệu lịch sử. */
    public function toggle(int $id): void
    {
        $supplier = Supplier::query()->findOrFail($id);
        $this->authorize('update', $supplier);

        $supplier = app(SupplierService::class)->toggle($supplier);

        $this->flash($supplier->is_active ? 'Đã kích hoạt hợp tác với nhà cung cấp.' : 'Đã ngừng hợp tác với nhà cung cấp.');
    }

    public function confirmDelete(int $id): void
    {
        $supplier = Supplier::query()->findOrFail($id);
        $this->authorize('delete', $supplier);

        $this->deletingId = $supplier->id;
        $this->deletingName = $supplier->name;
    }

    /** BR02.02-3 / BR02.02-4: đã có phiếu nhập thì Service ném BusinessConflictException (409 ở API) -> hiện thành thông báo lỗi. */
    public function delete(): void
    {
        abort_if($this->deletingId === null, 404);

        $supplier = Supplier::query()->findOrFail($this->deletingId);
        $this->authorize('delete', $supplier);

        try {
            app(SupplierService::class)->delete($supplier);
            $this->flash('Đã xóa nhà cung cấp.');
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

        $suppliers = app(SupplierService::class)->list($params);

        return view('livewire.suppliers.supplier-index', ['suppliers' => $suppliers]);
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', ...self::FORM_FIELDS]);
        $this->resetValidation();
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}