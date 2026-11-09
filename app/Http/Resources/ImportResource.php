<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Phiếu nhập kho — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 */
class ImportResource extends JsonResource
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
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'import_date' => $this->import_date,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at,
            'supplier' => SupplierResource::make($this->whenLoaded('supplier')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'confirmer' => UserResource::make($this->whenLoaded('confirmer')),
            'items' => ImportItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
