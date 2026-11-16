<?php

namespace App\Http\Requests;

/** UC02.03 — sửa kho (PUT /api/warehouses/{warehouse} #18). */
class UpdateWarehouseRequest extends StoreWarehouseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('warehouse'));
    }

    public function rules(): array
    {
        return static::rulesFor($this->route('warehouse'));
    }
}
