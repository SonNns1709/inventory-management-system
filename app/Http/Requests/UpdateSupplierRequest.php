<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** UC02.02 — sửa nhà cung cấp (PUT /api/suppliers/{supplier} #13). */
class UpdateSupplierRequest extends StoreSupplierRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('supplier'));
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:150', Rule::unique('suppliers', 'name')->ignore($this->route('supplier'))],
        ]);
    }
}
