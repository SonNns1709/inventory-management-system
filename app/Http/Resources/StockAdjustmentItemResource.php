<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Chi tiết phiếu kiểm kê — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class StockAdjustmentItemResource extends JsonResource
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
            'adjustment_id' => $this->adjustment_id,
            'product_id' => $this->product_id,
            'system_qty' => $this->system_qty,
            'counted_qty' => $this->counted_qty,
            'diff_qty' => $this->diff_qty,
            'reason' => $this->reason,
            'unit_cost' => $this->unit_cost,
            'diff_value' => $this->diff_value,
            'note' => $this->note,
            'product' => ProductResource::make($this->whenLoaded('product')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
