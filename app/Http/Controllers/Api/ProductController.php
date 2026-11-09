<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Sản phẩm — UC02.04 – UC02.07 và thẻ kho UC04.02 (bảng API, nhóm 3: #21 – #28). */
class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductService $products,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * GET /api/products (#21) — phân trang, kèm total_stock và cờ is_low_stock (BR02.04-4, BR02.04-5).
     * Tham số: keyword (SKU / tên), category_id, is_active, low_stock, sort, direction, per_page, page.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return $this->successPaginated(ProductResource::class, $this->products->search($request->query()));
    }

    /** GET /api/products/check-sku?sku=...&ignore_id=... (#22) — { "data": { "exists": true|false } }. Admin. */
    public function checkSku(Request $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:30'],
            'ignore_id' => ['nullable', 'integer'],
        ]);

        return $this->success(['exists' => $this->products->skuExists($data['sku'], $data['ignore_id'] ?? null)]);
    }

    /** GET /api/products/{product} (#24) — chi tiết kèm tồn theo từng kho, giá vốn bình quân, ngưỡng cảnh báo (BR02.04-6). */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return $this->success(ProductResource::make($this->products->getDetail($product->getKey())));
    }

    /** POST /api/products (#23) — Admin; avg_cost = 0 và is_active = true (BR02.05-2). */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->created(ProductResource::make($this->products->create($request->validated())));
    }

    /** PUT /api/products/{product} (#25) — Admin; SKU chỉ đổi được khi chưa có giao dịch (BR02.06-3). */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        return $this->success(ProductResource::make($this->products->update($product, $request->validated())));
    }

    /** DELETE /api/products/{product} (#26) — Admin; đã có giao dịch hoặc còn tồn thì 409 (chỉ được ngừng kinh doanh). */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        return $this->message('Xóa sản phẩm thành công');
    }

    /** PATCH /api/products/{product}/toggle (#27) — ngừng kinh doanh (is_active = false) hoặc kinh doanh lại. */
    public function toggle(Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $product->is_active ? $this->products->deactivate($product) : $this->products->activate($product);

        return $this->success(ProductResource::make($product));
    }

    /**
     * GET /api/products/{product}/stock-card (#28) — thẻ kho: tồn đầu kỳ + các biến động theo thời gian.
     * Tham số: warehouse_id, start_date, end_date (YYYY-MM-DD). Chỉ tra cứu (BR04.02-8).
     */
    public function stockCard(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $data = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $card = $this->inventory->getStockCard(
            $product,
            isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
        );

        return $this->success([
            'product' => ProductResource::make($product),
            'opening_balance' => $card['opening_balance'],
            'closing_balance' => $card['closing_balance'],
            'avg_cost' => $card['avg_cost'],
            'movements' => StockMovementResource::collection($card['movements']),
        ]);
    }
}
