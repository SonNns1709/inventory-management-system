<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/** Dashboard — UC05.01 (bảng API, nhóm 7: #51). Cả hai vai trò; chỉ đọc. */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * GET /api/dashboard/summary (#51) — 5 chỉ số theo thời gian thực
     * (total_products, low_stock, todays_import, todays_export, inventory_value) và danh sách rút gọn Low Stock.
     */
    public function summary(): JsonResponse
    {
        return $this->success($this->dashboard->getSummary());
    }
}
