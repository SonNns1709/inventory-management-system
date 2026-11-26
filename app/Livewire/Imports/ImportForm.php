<?php

namespace App\Livewire\Imports;

use App\Exceptions\BusinessConflictException;
use App\Http\Requests\StoreImportRequest;
use App\Models\Import;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ImportService;
use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * UC03.01 / UC03.04 — Lập, sửa, xác nhận, hủy phiếu nhập kho. Copy khuôn CategoryIndex:
 *
 *   thao tác Livewire -> $this->authorize() (Policy) -> luật kiểm tra dùng chung với API (StoreImportRequest::rules())
 *                     -> ImportService (nghiệp vụ + Activity Log) -> Eloquent
 *
 * Vòng đời: draft -> confirmed | cancelled. Chỉ khi xác nhận mới cộng tồn / tính lại giá vốn (làm trong ImportService::confirm).
 * Phiếu không còn là draft thì form chuyển sang chế độ chỉ đọc (BR03.04-2: confirmed bất biến).
 *
 * Lưu ý bảo mật: thuộc tính public do trình duyệt gửi lên nên KHÔNG tin được ($importId, $status...). Mọi action nạp lại
 * phiếu từ CSDL, gọi lại Policy; việc "chỉ sửa được khi draft" do ImportService chốt (409), không dựa vào $status.
 */
class ImportForm extends Component
{
    use AuthorizesRequests;

    public ?int $importId = null;

    // --- Đầu phiếu ---
    public string $code = '';

    /** Giá trị enum DocumentStatus dạng chuỗi: draft | confirmed | cancelled (chỉ để hiển thị). */
    public string $status = 'draft';

    public string $supplier_id = '';

    public string $warehouse_id = '';

    public string $import_date = '';

    public string $note = '';

    // --- Dòng hàng: mỗi phần tử { product_id, quantity, unit_cost } (chuỗi, vì đến từ ô nhập) ---
    /** @var array<int, array{product_id: string, quantity: string, unit_cost: string}> */
    public array $items = [];

    /** Hộp thoại đang mở: '' | 'confirm' | 'cancel' */
    public string $dialog = '';

    // --- Thông báo kết quả thao tác ---
    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(?int $importId = null): void
    {
        if ($importId === null) {
            $this->authorize('create', Import::class);

            $this->import_date = now()->toDateString();
            $this->items = [$this->blankItem()];
        } else {
            $import = app(ImportService::class)->getDetail($importId);
            $this->authorize('view', $import);

            $this->loadFrom($import);
        }

        if ($message = session('import_notice')) {
            $this->flash($message);
        }
    }

    /* ------------------------------------------------------------------ Dòng hàng */

    public function addItem(): void
    {
        if ($this->status !== 'draft') {
            return;
        }

        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        if ($this->status !== 'draft' || count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->resetValidation();
    }

    /* ------------------------------------------------------------------ Lưu nháp */

    /** Lưu nháp (BR03.01-3: chưa đổi tồn kho). Lập mới xong chuyển sang trang sửa của phiếu vừa tạo. */
    public function save(): void
    {
        $isNew = $this->importId === null;

        try {
            $import = $this->persist();
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');

            return;
        }

        if ($isNew) {
            session()->flash('import_notice', "Đã lưu nháp phiếu nhập {$import->code}.");
            $this->redirectRoute('imports.edit', ['import' => $import->getKey()]);

            return;
        }

        $this->loadFrom($import);
        $this->flash('Đã lưu phiếu nháp.');
    }

    /* ------------------------------------------------------------------ Xác nhận */

    public function askConfirm(): void
    {
        $this->dialog = 'confirm';
    }

    /** Lưu các chỉnh sửa mới nhất rồi xác nhận: cộng tồn, tính lại giá vốn bình quân, ghi thẻ kho (một transaction trong Service). */
    public function confirmImport(): void
    {
        $this->dialog = '';

        try {
            $import = $this->persist();
            $this->authorize('confirm', $import);

            $import = app(ImportService::class)->confirm($import, auth()->user());
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');

            return;
        }

        session()->flash('import_notice', "Đã xác nhận nhập kho {$import->code}. Tồn kho và giá vốn bình quân đã được cập nhật.");
        $this->redirectRoute('imports.edit', ['import' => $import->getKey()]);
    }

    /* ------------------------------------------------------------------ Hủy phiếu */

    public function askCancel(): void
    {
        $this->dialog = 'cancel';
    }

    /** UC03.04: chỉ phiếu draft; người lập hoặc Admin (ImportPolicy::cancel). Không đụng tới tồn kho. */
    public function cancelImport(): void
    {
        $this->dialog = '';

        abort_if($this->importId === null, 404);

        $import = Import::query()->findOrFail($this->importId);
        $this->authorize('cancel', $import);

        try {
            $import = app(ImportService::class)->cancel($import, auth()->user());
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage(), 'error');

            return;
        }

        session()->flash('import_notice', "Đã hủy phiếu nhập {$import->code}.");
        $this->redirectRoute('imports.index');
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
        $import = $this->importId !== null ? Import::query()->find($this->importId) : null;

        $isDraft = $this->status === 'draft';
        $editable = $isDraft && ($import === null
            ? $user->can('create', Import::class)
            : $user->can('update', $import));

        // Sản phẩm: danh sách đang kinh doanh + các sản phẩm đã có trên phiếu (phiếu cũ vẫn hiển thị được dù SP đã ngừng).
        $selectedIds = collect($this->items)->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->all();
        $products = Product::query()
            ->where('is_active', true)
            ->orWhereIn('id', $selectedIds)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        // Tồn hiện tại = tổng tồn của sản phẩm trên mọi kho (cùng cách tính với ImportService::previewCost).
        $stocks = Inventory::query()
            ->whereIn('product_id', $selectedIds)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $service = app(ImportService::class);
        $lines = [];
        $total = 0.0;

        foreach ($this->items as $i => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (string) ($item['quantity'] ?? '');
            $cost = (string) ($item['unit_cost'] ?? '');

            $valid = $productId > 0 && ctype_digit($qty) && (int) $qty > 0 && is_numeric($cost) && (float) $cost >= 0;
            $lineTotal = $valid ? (int) $qty * (float) $cost : null;

            // UC03.01 Alternative Flow A3: xem trước giá vốn bình quân sau nhập (chỉ tính, không ghi gì).
            $newAvg = null;
            if ($isDraft && $valid && $products->has($productId)) {
                try {
                    $newAvg = $service->previewCost($productId, (int) $qty, $cost);
                } catch (ModelNotFoundException) {
                    $newAvg = null;
                }
            }

            $total += $lineTotal ?? 0;
            $lines[$i] = [
                'product' => $products->get($productId),
                'stock' => $productId > 0 ? (int) ($stocks[$productId] ?? 0) : null,
                'lineTotal' => $lineTotal,
                'newAvg' => $newAvg,
            ];
        }

        return view('livewire.imports.import-form', [
            'import' => $import,
            'isDraft' => $isDraft,
            'editable' => $editable,
            'canCancel' => $import !== null && $isDraft && $user->can('cancel', $import),
            'products' => $products,
            'lines' => $lines,
            'total' => $total,
            // NCC / kho đang hoạt động (BR03.01-2) + giá trị đang chọn để phiếu cũ vẫn hiển thị được.
            'suppliers' => Supplier::query()->where('is_active', true)->orWhere('id', (int) $this->supplier_id)->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orWhere('id', (int) $this->warehouse_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /* ------------------------------------------------------------------ Hàm phụ */

    /**
     * Kiểm tra bằng đúng luật của API rồi tạo / cập nhật phiếu nháp. Ném ValidationException (Livewire tự hiện lỗi dưới từng ô)
     * hoặc BusinessConflictException (phiếu không còn là draft -> 409).
     */
    private function persist(): Import
    {
        $import = $this->importId !== null ? Import::query()->findOrFail($this->importId) : null;

        if ($import === null) {
            $this->authorize('create', Import::class);
        } else {
            $this->authorize('update', $import);
        }

        $this->note = trim($this->note);

        $request = new StoreImportRequest;
        $data = $this->validate($request->rules(), $request->messages(), $request->attributes());

        $payload = [
            'supplier_id' => (int) $data['supplier_id'],
            'warehouse_id' => (int) $data['warehouse_id'],
            'import_date' => $data['import_date'],
            'note' => ($data['note'] ?? '') !== '' ? $data['note'] : null,
            'items' => collect($data['items'])->map(fn (array $item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'unit_cost' => (string) $item['unit_cost'],
            ])->values()->all(),
        ];

        $service = app(ImportService::class);

        return $import === null
            ? $service->createDraft(auth()->user(), $payload)
            : $service->updateDraft($import, $payload);
    }

    /** Nạp trạng thái form từ phiếu trong CSDL. */
    private function loadFrom(Import $import): void
    {
        $import->loadMissing('items');

        $this->importId = $import->getKey();
        $this->code = (string) $import->code;
        $this->status = $import->status instanceof BackedEnum ? (string) $import->status->value : (string) $import->status;
        $this->supplier_id = (string) $import->supplier_id;
        $this->warehouse_id = (string) $import->warehouse_id;
        $this->import_date = $import->import_date->format('Y-m-d');
        $this->note = (string) $import->note;
        $this->items = $import->items->map(fn ($item) => [
            'product_id' => (string) $item->product_id,
            'quantity' => (string) $item->quantity,
            'unit_cost' => $this->trimDecimal((string) $item->unit_cost),
        ])->values()->all();

        if ($this->items === []) {
            $this->items = [$this->blankItem()];
        }

        $this->resetValidation();
    }

    /** "6200.0000" -> "6200"; "6200.5000" -> "6200.5". */
    private function trimDecimal(string $value): string
    {
        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }

    /** @return array{product_id: string, quantity: string, unit_cost: string} */
    private function blankItem(): array
    {
        return ['product_id' => '', 'quantity' => '1', 'unit_cost' => ''];
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}
