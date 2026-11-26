<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Export;
use Illuminate\Contracts\View\View;

/**
 * Phiếu xuất kho — UC03.02, UC03.03, UC03.04.
 */
class ExportController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Export::class);

        return view('exports.index');
    }

    public function create(): View
    {
        $this->authorize('create', Export::class);

        return view('exports.form', ['exportId' => null]);
    }

    public function show(Export $export): View
    {
        $this->authorize('view', $export);

        return view('exports.show', ['exportId' => $export->getKey()]);
    }

    public function edit(Export $export): View
    {
        $this->authorize('update', $export);

        return view('exports.form', ['exportId' => $export->getKey()]);
    }
}
