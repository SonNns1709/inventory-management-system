<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** UC02.03 — sửa kho (PUT /api/warehouses/{warehouse} #18). */
class UpdateWarehouseRequest extends StoreWarehouseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('warehouse'));
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_\-]+$/', Rule::unique('warehouses', 'code')->ignore($this->route('warehouse'))],
        ]);
    }
}
