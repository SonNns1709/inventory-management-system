<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Phiếu xuất kho (bán hàng hoặc chuyển kho) — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class ExportResource extends JsonResource
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
            'reason' => $this->reason,
            'dest_warehouse_id' => $this->dest_warehouse_id,
            'export_date' => $this->export_date,
            'status' => $this->status,
            'total_cost' => $this->total_cost,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at,
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'dest_warehouse' => WarehouseResource::make($this->whenLoaded('destWarehouse')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'confirmer' => UserResource::make($this->whenLoaded('confirmer')),
            'items' => ExportItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
