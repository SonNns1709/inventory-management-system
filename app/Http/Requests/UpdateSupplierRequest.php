<?php

namespace App\Http\Requests;

/** UC02.02 — sửa nhà cung cấp (PUT /api/suppliers/{supplier} #13). */
class UpdateSupplierRequest extends StoreSupplierRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('supplier'));
    }

    public function rules(): array
    {
        return static::rulesFor($this->route('supplier'));
    }
}
