<?php

namespace App\Http\Requests;

/**
 * UC03.01 — sửa phiếu nhập nháp (PUT /api/imports/{import} #33). Cùng bộ luật như lúc lập; chỉ khác quyền:
 * Policy `update` trên đúng phiếu. Phiếu không còn là draft thì ImportService trả 409 (BR03.04-2: confirmed bất biến).
 */
class UpdateImportRequest extends StoreImportRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('import'));
    }
}
