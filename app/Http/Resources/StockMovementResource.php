<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Sổ biến động tồn kho (thẻ kho), chỉ ghi thêm, không sửa — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class StockMovementResource extends JsonResource
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
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'type' => $this->type,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            // Số phiếu của chứng từ gốc (BR04.02-6); chỉ có khi thẻ kho được dựng qua InventoryService::getStockCard()
            'reference_code' => $this->resource->getAttributes()['reference_code'] ?? null,
            'qty_change' => $this->qty_change,
            'balance_after' => $this->balance_after,
            'unit_cost' => $this->unit_cost,
            'avg_cost_after' => $this->avg_cost_after,
            'created_by' => $this->created_by,
            'product' => ProductResource::make($this->whenLoaded('product')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
        ];
    }
}
