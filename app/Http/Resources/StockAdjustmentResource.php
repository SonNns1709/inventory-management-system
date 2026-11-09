<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Phiếu kiểm kê và điều chỉnh tồn kho — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class StockAdjustmentResource extends JsonResource
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
            'code' => $this->code,
            'warehouse_id' => $this->warehouse_id,
            'adjustment_date' => $this->adjustment_date,
            'status' => $this->status,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at,
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'confirmer' => UserResource::make($this->whenLoaded('confirmer')),
            'items' => StockAdjustmentItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
