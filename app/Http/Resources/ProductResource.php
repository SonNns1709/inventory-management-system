<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Sản phẩm (mặt hàng quản lý theo SKU) — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'unit' => $this->unit,
            'price' => $this->price,
            'avg_cost' => $this->avg_cost,
            'min_stock_level' => $this->min_stock_level,
            'description' => $this->description,
            'is_active' => $this->is_active,
            // total_stock chỉ có khi truy vấn dùng scope withTotalStock() (tổng SUM rỗng = chưa có dòng tồn -> 0)
            'total_stock' => $this->when($this->hasTotalStock(), fn () => (int) $this->total_stock),
            // BR02.04-5 / BR04.04-4: đánh dấu sản phẩm có tổng tồn dưới ngưỡng cảnh báo
            'is_low_stock' => $this->when(
                $this->hasTotalStock(),
                fn () => (int) $this->total_stock < (int) $this->min_stock_level,
            ),
            // BR04.04-5: mức thiếu = min_stock_level - tổng tồn (0 nếu chưa thiếu)
            'shortage' => $this->when(
                $this->hasTotalStock(),
                fn () => max(0, (int) $this->min_stock_level - (int) $this->total_stock),
            ),
            // Tồn theo từng kho (chi tiết sản phẩm — BR02.04-6)
            'inventories' => InventoryResource::collection($this->whenLoaded('inventories')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function hasTotalStock(): bool
    {
        return array_key_exists('total_stock', $this->resource->getAttributes());
    }
}
