<?php

namespace App\Livewire\Imports;

use App\Http\Filters\ImportFilter;
use App\Models\Import;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC03.03 — Tra cứu phiếu nhập kho. Copy khuôn App\Livewire\Categories\CategoryIndex:
 *
 *   thao tác Livewire -> $this->authorize() (Policy) -> ImportService::search() (ImportFilter whitelist) -> Eloquent
 *
 * Màn hình chỉ đọc (BR03.03-7): lập / sửa / xác nhận / hủy phiếu nằm ở ImportForm. Mọi tham số lọc đồng bộ lên URL
 * để chia sẻ và tải lại trang đúng trạng thái; giá trị lạ bị ImportFilter bỏ qua.
 */
class ImportIndex extends Component
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
    public string $supplier_id = '';

    #[Url(except: '')]
    public string $warehouse_id = '';

    #[Url(except: 'import_date')]
    public string $sort = 'import_date';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    // Số dòng mỗi trang chỉ giữ trong trạng thái component; giá trị lạ bị ImportFilter bỏ qua
    public int $perPage = 20;

    // --- Thông báo kết quả (ví dụ vừa hủy phiếu ở ImportForm rồi quay về danh sách) ---
    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorize('viewAny', Import::class);

        $this->perPage = (int) config('inventory.pagination.default', 20);

        if ($message = session('import_notice')) {
            $this->notice = $message;
        }
    }

    /** Đổi bất kỳ bộ lọc nào thì về trang 1. */
    public function updated(string $property): void
    {
        if (in_array($property, ['keyword', 'from', 'to', 'status', 'supplier_id', 'warehouse_id', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    /** Bấm tiêu đề cột: đổi cột sắp xếp, hoặc đảo chiều nếu bấm lại cùng cột. */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ImportFilter::SORTABLE, true)) {
            return;
        }

        $this->direction = ($this->sort === $column && $this->direction === 'asc') ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'from', 'to', 'status', 'supplier_id', 'warehouse_id']);
        $this->resetPage();
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        // Chỉ gửi tham số có giá trị: ImportFilter dùng isset() nên chuỗi rỗng sẽ bị coi là đang lọc.
        $params = array_filter([
            'keyword' => trim($this->keyword),
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status,
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ], fn ($value) => $value !== '' && $value !== null);

        return view('livewire.imports.import-index', [
            'imports' => app(ImportService::class)->search($params),
            // Bộ lọc cần cả NCC / kho đã ngừng hoạt động để tra cứu phiếu cũ.
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'canCreate' => auth()->user()->can('create', Import::class),
        ]);
    }
}
