<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PlaceholderController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Giao diện web (Blade + Livewire) — phiên đăng nhập bằng session
|--------------------------------------------------------------------------
| Giao diện và REST API (routes/api.php) dùng chung tầng Service, chỉ khác cách giữ đăng nhập (session / token).
|
| Menu bên trái được dựng từ config/navigation.php: mỗi mục có `route` trùng với tên route ở đây.
| Màn hình `ready = true` đã có thật (Dashboard, Danh mục sản phẩm); các màn hình còn lại tạm trỏ về
| PlaceholderController — trang khung cho biết Use Case, Sprint, API và Service cần cài đặt.
| Khi cài đặt xong một màn hình: đổi route sang controller / Livewire thật và đặt `ready = true` trong config/navigation.php.
*/

Route::redirect('/', '/dashboard');

// Trang dành cho khách: đã đăng nhập mà vào đây sẽ được chuyển sang /dashboard (redirectUsersTo trong bootstrap/app.php).
// Chống dò mật khẩu do AuthService xử lý (BR01.01-2: 5 lần sai / 15 phút cho mỗi cặp email + IP -> HTTP 429).
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Từ đây trở xuống: phải đăng nhập, tài khoản chưa bị khóa, và không còn buộc đổi mật khẩu
// (UC01.01 Alternative Flow A1: chỉ vào được trang đổi mật khẩu và đăng xuất cho tới khi đổi xong).
Route::middleware(['auth', 'active', 'password.changed'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // UC05.01 — cả hai vai trò
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // UC01.03, UC01.04 — hồ sơ cá nhân và đổi mật khẩu (cả hai vai trò)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // ---------------------------------------------------------------- Danh mục nền (Admin — Bảng 9)
    Route::middleware('role:admin')->group(function () {
        // UC02.01 — màn hình tham chiếu đầy đủ: Livewire -> Policy -> CategoryService
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

        // Sprint 1–2: trang khung
        Route::get('/suppliers', PlaceholderController::class)->name('suppliers.index');        // UC02.02
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');      // UC02.03
    });

    // ---------------------------------------------------------------- Dùng chung Admin + Nhân viên kho
    Route::get('/products', PlaceholderController::class)->name('products.index');              // UC02.04 – UC02.07 (Admin: thêm/sửa/xóa)
    Route::get('/imports', PlaceholderController::class)->name('imports.index');                // UC03.01, UC03.03, UC03.04
    Route::get('/exports', PlaceholderController::class)->name('exports.index');                // UC03.02, UC03.03, UC03.04
    Route::get('/adjustments', PlaceholderController::class)->name('adjustments.index');        // UC04.03
    Route::get('/inventory', PlaceholderController::class)->name('inventory.index');            // UC04.01, UC04.02
    Route::get('/inventory/low-stock', PlaceholderController::class)->name('inventory.low-stock'); // UC04.04

    // ---------------------------------------------------------------- Báo cáo và Quản trị (chỉ Admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('/reports/stock-summary', PlaceholderController::class)->name('reports.stock-summary'); // UC05.02, UC05.04
        Route::get('/reports/adjustments', PlaceholderController::class)->name('reports.adjustments');     // UC05.03, UC05.04
        Route::get('/users', PlaceholderController::class)->name('users.index');                // UC06.01
        Route::get('/activity-logs', PlaceholderController::class)->name('activity-logs.index'); // UC06.02
    });
});
