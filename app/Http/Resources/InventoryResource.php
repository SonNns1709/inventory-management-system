<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Tồn kho hiện tại theo sản phẩm và kho — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class InventoryResource extends JsonResource
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
            'quantity' => $this->quantity,
            'product' => ProductResource::make($this->whenLoaded('product')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
