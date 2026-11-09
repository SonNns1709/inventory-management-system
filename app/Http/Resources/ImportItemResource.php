<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Chi tiết phiếu nhập — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class ImportItemResource extends JsonResource
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
            'import_id' => $this->import_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'line_total' => $this->line_total,
            'product' => ProductResource::make($this->whenLoaded('product')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
