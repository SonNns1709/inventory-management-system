<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\ProductFilter;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * UC02.04 – UC02.07 — Sản phẩm (báo cáo: ProductService phụ thuộc CategoryService để dựng ô chọn danh mục).
 *
 *  - BR02.04-4/5: danh sách kèm tổng tồn (total_stock) và cờ tồn dưới ngưỡng; BR02.04-6: chi tiết kèm tồn từng kho;
 *  - BR02.05-1: SKU duy nhất; BR02.05-2/3: sản phẩm mới avg_cost = 0, is_active = true, giá vốn không nhập tay;
 *  - BR02.06-3/4: sản phẩm đã có giao dịch không được đổi SKU; BR02.06-5: ghi giá trị cũ / mới vào Activity Log;
 *  - UC02.07: đã có giao dịch hoặc còn tồn thì không xóa, chỉ "ngừng kinh doanh" (is_active = false).
 *
 * Quy tắc đối chiếu bổ sung:
 *  - UC02.05 / UC02.06: create() / update() — dữ liệu đã qua StoreProductRequest / UpdateProductRequest.
 *  - BR02.05-6: create() ghi Activity Log ProductCreated.
 *  - BR02.06-2: sản phẩm phải tồn tại trước khi cập nhật — route model binding `{product}` trả 404 nếu không có.
 *  - BR02.06-6: thông tin cập nhật được UpdateProductRequest kiểm tra trước khi lưu.
 *  - BR02.07-1: chỉ Admin được ngừng kinh doanh / xóa (route `role:admin`, ProductPolicy).
 *  - BR02.07-2, BR02.07-3, BR02.07-4: deactivate() chỉ đặt is_active = false, không xóa dòng nào nên thẻ kho và báo cáo quá khứ vẫn đủ dữ liệu.
 *  - BR02.07-5, BR02.07-6: delete() chỉ cho xóa khi chưa có giao dịch và tồn = 0; ngược lại trả 409 và gợi ý "Ngừng kinh doanh".
 *  - BR02.07-7: deactivate() / activate() / delete() ghi Activity Log.
 */
class ProductService
{
    private const FIELDS = ['sku', 'name', 'category_id', 'unit', 'price', 'min_stock_level', 'description'];

    public function __construct(private readonly ActivityLogService $logs) {}

    /** @param  array<string, mixed>  $params  keyword (SKU / tên), category_id, is_active, low_stock, sort, direction, per_page */
    public function search(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = ProductFilter::valid($params);

        return ProductFilter::apply(Product::query()->with('category')->withTotalStock(), $filters)
            ->paginate($perPage ?? ProductFilter::perPage($filters));
    }

    /** Chi tiết: danh mục, tổng tồn và tồn theo từng kho. */
    public function getDetail(int|string $id): Product
    {
        return Product::query()->with(['category', 'inventories.warehouse'])->withTotalStock()->findOrFail($id);
    }

    /** UC02.05 — kiểm tra SKU đã tồn tại chưa (bảng API #22: check-sku). */
    public function skuExists(string $sku, ?int $ignoreId = null): bool
    {
        return Product::query()
            ->where('sku', strtoupper(trim($sku)))
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Product
    {
        $product = Product::create($this->payload($data) + ['avg_cost' => 0, 'is_active' => true]);

        $this->logs->record(
            ActivityAction::ProductCreated,
            $product,
            "Thêm sản phẩm {$product->sku} — {$product->name}",
            ['new' => $product->only(self::FIELDS)],
        );

        return $product->load('category');
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws BusinessConflictException  đổi SKU khi sản phẩm đã có giao dịch (BR02.06-3, BR02.06-4)
     */
    public function update(Product $product, array $data): Product
    {
        if (isset($data['sku']) && $data['sku'] !== $product->sku && $this->hasTransactions($product)) {
            throw new BusinessConflictException('Sản phẩm đã phát sinh giao dịch, không được đổi SKU');
        }

        $old = $product->only(self::FIELDS);

        $product->fill($this->payload($data + ['sku' => $product->sku]))->save();

        $this->logs->record(
            ActivityAction::ProductUpdated,
            $product,
            "Cập nhật sản phẩm {$product->sku}",
            ['old' => $old, 'new' => $product->only(self::FIELDS)],
        );

        return $product->load('category');
    }

    /** UC02.07 — chỉ xóa được sản phẩm chưa có giao dịch và không còn tồn; ngược lại -> HTTP 409 (dùng "ngừng kinh doanh"). */
    public function delete(Product $product): void
    {
        if ($this->hasTransactions($product) || $product->inventories()->where('quantity', '>', 0)->exists()) {
            throw new BusinessConflictException('Sản phẩm đã có giao dịch hoặc còn tồn kho, không thể xóa — hãy chuyển sang "Ngừng kinh doanh"');
        }

        $old = $product->only(self::FIELDS);
        $product->inventories()->delete();
        $product->delete();

        $this->logs->record(ActivityAction::ProductDeleted, $product, "Xóa sản phẩm {$old['sku']}", ['old' => $old]);
    }

    /** UC02.07 A1 — ngừng kinh doanh: sản phẩm bị ẩn khỏi danh sách chọn khi lập phiếu mới nhưng vẫn giữ lịch sử và thẻ kho. */
    public function deactivate(Product $product): Product
    {
        $product->forceFill(['is_active' => false])->save();

        $this->logs->record(ActivityAction::ProductDeactivated, $product, "Ngừng kinh doanh sản phẩm {$product->sku}");

        return $product;
    }

    public function activate(Product $product): Product
    {
        $product->forceFill(['is_active' => true])->save();

        $this->logs->record(ActivityAction::ProductActivated, $product, "Kinh doanh lại sản phẩm {$product->sku}");

        return $product;
    }

    /** Sản phẩm đã xuất hiện trên bất kỳ phiếu nhập / xuất / kiểm kê hoặc thẻ kho nào? */
    private function hasTransactions(Product $product): bool
    {
        return $product->importItems()->exists()
            || $product->exportItems()->exists()
            || $product->adjustmentItems()->exists()
            || $product->stockMovements()->exists();
    }

    /**
     * avg_cost và is_active không bao giờ lấy từ dữ liệu client (BR02.05-3).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return array_merge(['description' => null], array_intersect_key($data, array_flip(self::FIELDS)));
    }
}
