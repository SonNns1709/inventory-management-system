<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Kho / địa điểm lưu trữ — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class WarehouseResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'is_active' => $this->is_active,
            // Tổng tồn của kho: chỉ có khi truy vấn dùng withSum('inventories as total_stock', 'quantity')
            'total_stock' => $this->when(
                array_key_exists('total_stock', $this->resource->getAttributes()),
                fn () => (int) $this->total_stock,
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
