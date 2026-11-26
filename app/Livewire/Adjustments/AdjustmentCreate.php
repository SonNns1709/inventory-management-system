<?php

namespace App\Livewire\Adjustments;

use App\Exceptions\BusinessConflictException;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\StockAdjustmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * UC04.03 — Bước 1: lập phiếu kiểm kê nháp kèm snapshot system_qty (POST /api/adjustments #47).
 * Chọn kho, ngày, phạm vi (all | category | products), ghi chú; Service sinh các dòng kèm tồn hệ thống tại thời điểm lập (BR04.03-2).
 * Lập xong chuyển sang AdjustmentSheet để nhập số đếm thực tế.
 *
 *   Livewire action -> $this->authorize() (Policy) -> StoreStockAdjustmentRequest::rules() (dùng chung với API)
 *                   -> StockAdjustmentService::createSheet() -> Eloquent
 */
class AdjustmentCreate extends Component
{
    use AuthorizesRequests;

    public string $warehouse_id = '';

    public string $adjustment_date = '';

    /** all | category | products */
    public string $scope = 'all';

    public string $category_id = '';

    /** @var array<int, string> id sản phẩm được tick (chuỗi vì đến từ checkbox) */
    public array $product_ids = [];

    public string $productSearch = '';

    public string $note = '';

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorize('create', StockAdjustment::class);

        $this->adjustment_date = now()->toDateString();
    }

    public function save(): void
    {
        $this->authorize('create', StockAdjustment::class);

        $this->note = trim($this->note);

        $request = new StoreStockAdjustmentRequest;
        $rules = $request->rules();

        // Chỉ kiểm tra phần của phạm vi đang chọn: mảng rỗng 'product_ids' sẽ trượt min:1 dù phạm vi là "all".
        if ($this->scope !== 'category') {
            unset($rules['category_id']);
        }
        if ($this->scope !== 'products') {
            unset($rules['product_ids'], $rules['product_ids.*']);
        }

        $data = $this->validate($rules, $request->messages(), $request->attributes());

        $payload = [
            'warehouse_id' => (int) $data['warehouse_id'],
            'adjustment_date' => ($data['adjustment_date'] ?? '') !== '' ? $data['adjustment_date'] : null,
            'note' => ($data['note'] ?? '') !== '' ? $data['note'] : null,
            'scope' => $this->scope,
        ];

        if ($this->scope === 'category') {
            $payload['category_id'] = (int) $data['category_id'];
        } elseif ($this->scope === 'products') {
            $payload['product_ids'] = array_map('intval', array_values($data['product_ids']));
        }

        try {
            $sheet = app(StockAdjustmentService::class)->createSheet(auth()->user(), $payload);
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');

            return;
        } catch (ValidationException $e) {
            // Ví dụ Service từ chối vì phạm vi không có sản phẩm nào có tồn ở kho.
            $this->setErrorBag($e->errors());
            $this->flash('Vui lòng kiểm tra lại thông tin lập phiếu.', 'error');

            return;
        }

        session()->flash('adjustment_notice', "Đã lập phiếu kiểm kê {$sheet->code}. Hãy nhập số lượng đếm thực tế.");
        $this->redirectRoute('adjustments.edit', ['adjustment' => $sheet->getKey()]);
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        $search = trim($this->productSearch);

        // Danh sách chọn sản phẩm chỉ cần khi phạm vi là "products"; giới hạn 50 dòng, người dùng thu hẹp bằng ô tìm kiếm.
        $products = $this->scope !== 'products' ? collect() : Product::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like));
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'sku', 'name']);

        return view('livewire.adjustments.adjustment-create', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'products' => $products,
        ]);
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}
