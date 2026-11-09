<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;

/** Dashboard — UC05.01. Cả hai vai trò dùng chung một màn hình; chỉ đọc (BR05.01-7). */
class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View
    {
        return view('dashboard.index', ['summary' => $dashboard->getSummary()]);
    }
}
