<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Trang khung cho các màn hình chưa cài đặt (Sprint 1–4 của kế hoạch trong báo cáo).
 *
 * Trang đọc siêu dữ liệu của chính route đang chạy từ config/navigation.php (Use Case, Sprint, API, Service) và hiển thị
 * cho nhóm biết cần làm gì. Khi cài đặt xong một màn hình: tạo Controller / Livewire thật, đổi route trong routes/web.php
 * và đặt `ready => true` trong config/navigation.php.
 */
class PlaceholderController extends Controller
{
    public function __invoke(Request $request): View
    {
        $screen = Navigation::find($request->route()?->getName());

        abort_if($screen === null, 404);

        return view('placeholder', ['screen' => $screen]);
    }
}
