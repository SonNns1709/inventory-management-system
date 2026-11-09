<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API — báo cáo Nhóm 11, Chương 3 §III "Thiết kế API"
|--------------------------------------------------------------------------
| Tiền tố /api do bootstrap/app.php gắn sẵn. Bảng API của báo cáo có 61 dòng, chia 7 nhóm; số "#n" ở mỗi route
| là số thứ tự dòng đó trong bảng (khi đối chiếu báo cáo). Route đánh dấu "(+)" lấy từ Sơ đồ lớp thiết kế
| (Sơ đồ 72 – 74) nhưng không có trong bảng API: show, warehouses/{id}/inventories, adjustments/{id}/cancel.
|
| Quy ước phản hồi: thành công { "data": ... } (200/201); lỗi 422 { "message", "errors" };
| 401 / 403 / 404 / 409 / 429 { "message" } (xem bootstrap/app.php).
|
| Hai lớp phân quyền (cột "Quyền" trong bảng API):
|   - middleware `role:admin` chặn theo vai trò ngay ở route (các chức năng chỉ dành cho Admin);
|   - Policy / Form Request chặn theo từng hành động (ví dụ "Người lập phiếu / Admin" khi hủy phiếu).
| Route khai báo cố định (check-sku, preview-cost, low-stock...) luôn đặt TRƯỚC route có tham số {id}.
*/

Route::name('api.')->group(function () {

    // ------------------------------------------------------------------ 1. Auth & Hồ sơ cá nhân (UC01)
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');                       // #1

    // Mọi route còn lại: có token Sanctum hợp lệ, tài khoản chưa bị khóa, và không còn buộc đổi mật khẩu
    // (auth/logout, auth/me, auth/password vẫn được dùng khi must_change_password = true — xem EnsurePasswordChanged).
    Route::middleware(['auth:sanctum', 'active', 'password.changed'])->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');                // #2
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');                             // #3
        Route::put('auth/password', [AuthController::class, 'changePassword'])->name('auth.password');     // #4
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');                   // #5
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');               // #6

        // -------------------------------------------------------------- 2. Danh mục nền (UC02.01 – UC02.03)
        // Đọc: mọi tài khoản đã đăng nhập (Nhân viên kho cần danh sách để lập phiếu); ghi: chỉ Admin.
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');          // #7
        Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show'); // (+)
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');            // #11
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');   // (+)
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');         // #16
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show'); // (+)
        Route::get('warehouses/{warehouse}/inventories', [WarehouseController::class, 'inventories'])
            ->name('warehouses.inventories');                                                              // (+)

        Route::middleware('role:admin')->group(function () {
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');     // #8
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update'); // #9
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy'); // #10

            Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');       // #12
            Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update'); // #13
            Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy'); // #14
            Route::patch('suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('suppliers.toggle'); // #15

            Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');    // #17
            Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update'); // #18
            Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy'); // #19
            Route::patch('warehouses/{warehouse}/toggle', [WarehouseController::class, 'toggle'])->name('warehouses.toggle'); // #20
        });

        // -------------------------------------------------------------- 3. Sản phẩm (UC02.04 – UC02.07, UC04.02)
        Route::get('products', [ProductController::class, 'index'])->name('products.index');               // #21
        Route::get('products/check-sku', [ProductController::class, 'checkSku'])
            ->middleware('role:admin')->name('products.check-sku');                                        // #22 (Admin)
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');       // #24
        Route::get('products/{product}/stock-card', [ProductController::class, 'stockCard'])->name('products.stock-card'); // #28

        Route::middleware('role:admin')->group(function () {
            Route::post('products', [ProductController::class, 'store'])->name('products.store');          // #23
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update'); // #25
            Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy'); // #26
            Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle'); // #27
        });

        // -------------------------------------------------------------- 4. Nhập kho (UC03.01, UC03.03, UC03.04)
        // Lập / sửa / xác nhận: "Nhân viên kho" (Policy dùng User::canOperateWarehouse(), xem config/inventory.php);
        // hủy: "Người lập phiếu / Admin" (Policy ImportPolicy::cancel).
        Route::get('imports', [ImportController::class, 'index'])->name('imports.index');                  // #29
        Route::post('imports/preview-cost', [ImportController::class, 'previewCost'])->name('imports.preview-cost'); // #30
        Route::post('imports', [ImportController::class, 'store'])->name('imports.store');                 // #31
        Route::get('imports/{import}', [ImportController::class, 'show'])->name('imports.show');           // #32
        Route::put('imports/{import}', [ImportController::class, 'update'])->name('imports.update');       // #33
        Route::post('imports/{import}/confirm', [ImportController::class, 'confirm'])->name('imports.confirm'); // #34
        Route::post('imports/{import}/cancel', [ImportController::class, 'cancel'])->name('imports.cancel'); // #35

        // -------------------------------------------------------------- 5. Xuất kho (UC03.02, UC03.03, UC03.04)
        Route::get('exports', [ExportController::class, 'index'])->name('exports.index');                  // #36
        Route::post('exports/check-availability', [ExportController::class, 'checkAvailability'])->name('exports.check-availability'); // #37
        Route::post('exports', [ExportController::class, 'store'])->name('exports.store');                 // #38
        Route::get('exports/{export}', [ExportController::class, 'show'])->name('exports.show');           // #39
        Route::put('exports/{export}', [ExportController::class, 'update'])->name('exports.update');       // #40
        Route::post('exports/{export}/confirm', [ExportController::class, 'confirm'])->name('exports.confirm'); // #41
        Route::post('exports/{export}/cancel', [ExportController::class, 'cancel'])->name('exports.cancel'); // #42

        // -------------------------------------------------------------- 6. Tồn kho và Kiểm kê (UC04.01, UC04.03, UC04.04)
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');           // #43
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock'); // #44
        Route::get('inventory/total-value', [InventoryController::class, 'totalValue'])->name('inventory.total-value'); // #45

        Route::get('adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index'); // #46
        Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store'); // #47
        Route::get('adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->name('adjustments.show'); // (+)
        Route::put('adjustments/{adjustment}', [StockAdjustmentController::class, 'update'])->name('adjustments.update'); // #48
        Route::post('adjustments/{adjustment}/refresh', [StockAdjustmentController::class, 'refresh'])->name('adjustments.refresh'); // #49
        Route::post('adjustments/{adjustment}/confirm', [StockAdjustmentController::class, 'confirm'])->name('adjustments.confirm'); // #50
        Route::post('adjustments/{adjustment}/cancel', [StockAdjustmentController::class, 'cancel'])->name('adjustments.cancel'); // (+)

        // -------------------------------------------------------------- 7. Dashboard, Báo cáo, Quản trị (UC05, UC06)
        Route::get('dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary'); // #51

        Route::middleware('role:admin')->group(function () {
            Route::get('reports/stock-summary', [ReportController::class, 'stockSummary'])->name('reports.stock-summary'); // #52
            Route::get('reports/stock-summary/export', [ReportController::class, 'exportStockSummary'])->name('reports.stock-summary.export'); // #53
            Route::get('reports/adjustments', [ReportController::class, 'adjustments'])->name('reports.adjustments'); // #54
            Route::get('reports/adjustments/export', [ReportController::class, 'exportAdjustments'])->name('reports.adjustments.export'); // #55

            Route::get('users', [UserController::class, 'index'])->name('users.index');                    // #56
            Route::post('users', [UserController::class, 'store'])->name('users.store');                   // #57
            Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');               // (+)
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');           // #58
            Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');  // #59
            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password'); // #60

            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index'); // #61
        });
    });
});
