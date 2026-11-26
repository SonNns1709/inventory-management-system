<?php

namespace App\Livewire\Exports;

use App\Exceptions\BusinessConflictException;
use App\Http\Requests\StoreExportRequest;
use App\Models\Export;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * UC03.02 — Lập / sửa phiếu xuất kho nháp (theo mock_export_form.html).
 *
 *   thao tác Livewire -> $this->authorize() (Policy) -> StoreExportRequest::rulesFor() (dùng chung với API)
 *                     -> ExportService (createDraft / updateDraft / confirm) -> Eloquent
 *
 * Tên thuộc tính trùng tên trường trong StoreExportRequest (warehouse_id, reason, dest_warehouse_id, export_date, note, items)
 * để luật `different:warehouse_id` và `items.*` hoạt động. Cột "Giá vốn BQ" chỉ là giá trị xem trước:
 * phiếu nháp không lưu giá vốn, giá vốn thật được chốt lúc xác nhận (BR03.02-5).
 */
class ExportForm extends Component
{
    use AuthorizesRequests;

    public ?int $editingId = null;

    public string $code = '';

    public string $warehouse_id = '';

    public string $reason = 'sale';

    public string $dest_warehouse_id = '';

    public string $export_date = '';

    public string $note = '';

    /** @var array<int, array{product_id: string, quantity: int|string}> */
    public array $items = [];

    public ?string $notice = null;

    public string $noticeType = 'error';

    public function mount(?int $exportId = null): void
    {
        if ($exportId === null) {
            $this->authorize('create', Export::class);

            $this->export_date = now()->toDateString();
            $this->items = [$this->blankLine()];

            return;
        }

        $export = Export::query()->findOrFail($exportId);
        $this->authorize('update', $export);

        // Chỉ phiếu nháp mới sửa được (BR03.04-2: phiếu đã xác nhận / hủy là bất biến)
        if ($this->scalar($export->status) !== 'draft') {
            session()->flash('notice', 'Chỉ sửa được phiếu ở trạng thái nháp.');
            session()->flash('noticeType', 'error');
            $this->redirectRoute('exports.show', ['export' => $export->getKey()]);

            return;
        }

        $export->load('items');

        $this->editingId = $export->id;
        $this->code = (string) $export->code;
        $this->warehouse_id = (string) $export->warehouse_id;
        $this->reason = $this->scalar($export->reason) ?: 'sale';
        $this->dest_warehouse_id = (string) ($export->dest_warehouse_id ?? '');
        $this->export_date = Carbon::parse($export->export_date)->format('Y-m-d');
        $this->note = (string) $export->note;
        $this->items = $export->items
            ->map(fn ($item) => ['product_id' => (string) $item->product_id, 'quantity' => (int) $item->quantity])
            ->values()
            ->all();

        if ($this->items === []) {
            $this->items = [$this->blankLine()];
        }
    }

    /** Bán hàng thì không có kho đích. */
    public function updatedReason(): void
    {
        if ($this->reason !== 'transfer') {
            $this->dest_warehouse_id = '';
        }
    }

    /** Kho nhận phải khác kho xuất. */
    public function updatedWarehouseId(): void
    {
        if ($this->dest_warehouse_id === $this->warehouse_id) {
            $this->dest_warehouse_id = '';
        }
    }

    public function addLine(): void
    {
        $this->items[] = $this->blankLine();
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->items = [$this->blankLine()];
        }

        $this->resetValidation();
    }

    /** Lưu nháp; $confirm = true thì xác nhận xuất kho ngay sau khi lưu (trừ tồn do ExportService::confirm()). */
    public function save(bool $confirm = false): void
    {
        $export = $this->editingId !== null ? Export::query()->findOrFail($this->editingId) : null;

        if ($export === null) {
            $this->authorize('create', Export::class);
        } else {
            $this->authorize('update', $export);
        }

        $this->notice = null;
        $this->note = trim($this->note);

        if ($this->reason !== 'transfer') {
            $this->dest_warehouse_id = '';
        }

        $request = new StoreExportRequest;
        $data = $this->validate(StoreExportRequest::rulesFor(), $request->messages(), $request->attributes());

        $payload = [
            'warehouse_id' => (int) $data['warehouse_id'],
            'reason' => $data['reason'],
            'dest_warehouse_id' => $data['reason'] === 'transfer' ? (int) $data['dest_warehouse_id'] : null,
            'export_date' => $data['export_date'],
            'note' => ($data['note'] ?? '') !== '' ? $data['note'] : null,
            'items' => array_map(
                fn (array $line) => ['product_id' => (int) $line['product_id'], 'quantity' => (int) $line['quantity']],
                array_values($data['items']),
            ),
        ];

        $service = app(ExportService::class);
        $user = auth()->user();
        $saved = false;

        try {
            $export = $export === null ? $service->createDraft($user, $payload) : $service->updateDraft($export, $payload);
            $this->editingId = (int) $export->getKey();
            $saved = true;

            if ($confirm) {
                $this->authorize('confirm', $export);
                $service->confirm($export, $user);
            }
        } catch (BusinessConflictException $e) {
            // Đã lưu nháp nhưng xác nhận thất bại (vd thiếu tồn): ở lại form để người dùng chỉnh số lượng.
            $this->flash(($saved ? 'Đã lưu nháp nhưng chưa xác nhận: ' : '').$e->getMessage());

            return;
        }

        session()->flash('notice', $confirm ? 'Đã xác nhận xuất kho.' : 'Đã lưu phiếu xuất nháp.');
        session()->flash('noticeType', 'success');

        $this->redirectRoute('exports.show', ['export' => $this->editingId]);
    }

    /** Hủy phiếu nháp đã lưu (BR03.04); phiếu chưa lưu thì chỉ quay về danh sách. */
    public function cancelDocument(): void
    {
        if ($this->editingId === null) {
            $this->redirectRoute('exports.index');

            return;
        }

        $export = Export::query()->findOrFail($this->editingId);
        $this->authorize('cancel', $export);

        try {
            app(ExportService::class)->cancel($export, auth()->user());
        } catch (BusinessConflictException $e) {
            $this->flash($e->getMessage());

            return;
        }

        session()->flash('notice', 'Đã hủy phiếu xuất nháp.');
        session()->flash('noticeType', 'success');

        $this->redirectRoute('exports.show', ['export' => $this->editingId]);
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        $selectedIds = collect($this->items)->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->all();

        // Sản phẩm ngừng kinh doanh bị ẩn khỏi danh sách chọn (UC02.07), trừ khi đang nằm sẵn trong phiếu nháp này.
        $products = Product::query()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $selectedIds))
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit', 'avg_cost']);
        $byId = $products->keyBy('id');

        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Tồn khả dụng tại kho xuất (chỉ tra cứu, không giữ chỗ hàng)
        $payload = collect($this->items)
            ->filter(fn ($line) => ($line['product_id'] ?? '') !== '')
            ->map(fn ($line) => ['product_id' => (int) $line['product_id'], 'quantity' => max(0, (int) ($line['quantity'] ?: 0))])
            ->values()
            ->all();

        $availability = [];
        if ($this->warehouse_id !== '' && $payload !== []) {
            $check = app(ExportService::class)->checkAvailability((int) $this->warehouse_id, $payload);
            foreach ($check['details'] as $detail) {
                $availability[$detail['product_id']] = $detail;
            }
        }

        $rows = [];
        $shortages = [];
        $total = 0.0;

        foreach ($this->items as $index => $line) {
            $productId = (int) (($line['product_id'] ?? '') ?: 0);
            $qty = max(0, (int) (($line['quantity'] ?? 0) ?: 0));
            $product = $byId[$productId] ?? null;
            $stock = $availability[$productId] ?? null;

            $cost = $product ? (float) $product->avg_cost : 0.0;
            $value = $qty * $cost;
            $total += $value;
            $short = $stock !== null && ! $stock['is_enough'];

            if ($short) {
                $shortages[] = "{$stock['sku']} cần {$stock['requested']}, tồn khả dụng còn {$stock['available']}";
            }

            $rows[$index] = [
                'product' => $product,
                'available' => $stock['available'] ?? null,
                'cost' => $cost,
                'value' => $value,
                'short' => $short,
                'shortage' => $stock['shortage'] ?? 0,
            ];
        }

        return view('livewire.exports.export-form', [
            'products' => $products,
            'warehouses' => $warehouses,
            'rows' => $rows,
            'total' => $total,
            'shortages' => $shortages,
            'hasShortage' => $shortages !== [],
            'warehouseName' => $warehouses->firstWhere('id', (int) $this->warehouse_id)?->name,
        ]);
    }

    /** @return array{product_id: string, quantity: int} */
    private function blankLine(): array
    {
        return ['product_id' => '', 'quantity' => 1];
    }

    private function scalar(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }

    private function flash(string $message, string $type = 'error'): void
    {
        $this->notice = $message;
        $this->noticeType = $type;
    }
}
