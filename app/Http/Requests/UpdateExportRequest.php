<?php

namespace App\Http\Requests;

/** UC03.02 — sửa phiếu xuất nháp (PUT /api/exports/{export} #40). Cùng bộ luật như lúc lập; Policy `update` trên đúng phiếu. */
class UpdateExportRequest extends StoreExportRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('export'));
    }
}
