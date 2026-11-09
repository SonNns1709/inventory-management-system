# Danh sách REST API

Nguồn: báo cáo Nhóm 11, Chương 3 §III "Thiết kế API" (bảng 61 dòng) cộng các route bổ sung lấy từ sơ đồ lớp thiết kế. Mã nguồn: [`routes/api.php`](../routes/api.php) — mỗi route ghi `// #n` là số thứ tự dòng trong bảng.

- Tiền tố `/api`; xác thực `Authorization: Bearer <token>` (trừ `POST /api/auth/login`); luôn gửi `Accept: application/json`.
- Thành công: `{ "data": ... }`. Lỗi dữ liệu: `422 { "message", "errors" }`. Lỗi khác: `{ "message" }` với 401 / 403 / 404 / 409 / 429 / 501.
- Cột **Quyền** lấy nguyên văn từ báo cáo; ở mã nguồn có hai lớp chặn: middleware `role:admin` ở route và Policy / Form Request cho từng hành động.
- Cột **Cài đặt**: `Đã cài đặt` hoặc `Khung 501` (Controller đã nối nhưng Service còn ném `NotImplementedException` — xem README mục 11).


## 1. Auth & Hồ sơ cá nhân

| # | Method | Endpoint | Chức năng | Quyền | Mã trạng thái | Controller | Cài đặt |
|---|---|---|---|---|---|---|---|
| 1 | POST | `/api/auth/login` | Đăng nhập, trả về access token | Không cần đăng nhập | 200/201 (Thành công), 422 (Dữ liệu không hợp lệ) | `AuthController::login` | Đã cài đặt |
| 2 | POST | `/api/auth/logout` | Đăng xuất, thu hồi token hiện tại | Đã đăng nhập | 200/201 (Thành công) | `AuthController::logout` | Đã cài đặt |
| 3 | GET | `/api/auth/me` | Lấy thông tin tài khoản đang đăng nhập | Đã đăng nhập | 200/201 (Thành công), 401 (Chưa đăng nhập) | `AuthController::me` | Đã cài đặt |
| 4 | PUT | `/api/auth/password` | Đổi mật khẩu (UC01.03) | Đã đăng nhập | 200/201 (Thành công), 401 (Chưa đăng nhập), 422 (Dữ liệu không hợp lệ) | `AuthController::changePassword` | Đã cài đặt |
| 5 | GET | `/api/profile` | Xem hồ sơ cá nhân | Đã đăng nhập | 200/201 (Thành công), 401 (Chưa đăng nhập) | `ProfileController::show` | Đã cài đặt |
| 6 | PUT | `/api/profile` | Cập nhật hồ sơ cá nhân (UC01.04) | Đã đăng nhập | 200/201 (Thành công), 401 (Chưa đăng nhập), 422 (Lỗi họ tên trống hoặc SĐT sai định dạng) | `ProfileController::update` | Đã cài đặt |

## 2. Danh mục nền — Category, Supplier, Warehouse

| # | Method | Endpoint | Chức năng | Quyền | Mã trạng thái | Controller | Cài đặt |
|---|---|---|---|---|---|---|---|
| 7 | GET | `/api/categories` | Danh sách danh mục sản phẩm | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `CategoryController::index` | Đã cài đặt |
| 8 | POST | `/api/categories` | Thêm danh mục (UC02.01) | Admin | 201 (Thành công), 403 (Không đủ quyền), 422 (Dữ liệu không hợp lệ) | `CategoryController::store` | Đã cài đặt |
| 9 | PUT | `/api/categories/{category}` | Cập nhật danh mục | Admin | 200 (Thành công), 404 (Không tìm thấy), 422 (Dữ liệu không hợp lệ) | `CategoryController::update` | Đã cài đặt |
| 10 | DELETE | `/api/categories/{category}` | Xóa danh mục (chặn nếu còn sản phẩm) | Admin | 200 (Thành công), 404 (Không tìm thấy), 409 (Xung đột nghiệp vụ - còn sản phẩm) | `CategoryController::destroy` | Đã cài đặt |
| 11 | GET | `/api/suppliers` | Danh sách nhà cung cấp, hỗ trợ tìm kiếm | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `SupplierController::index` | Đã cài đặt |
| 12 | POST | `/api/suppliers` | Thêm nhà cung cấp (UC02.02) | Admin | 201 (Thành công), 403 (Không đủ quyền), 422 (Dữ liệu không hợp lệ) | `SupplierController::store` | Đã cài đặt |
| 13 | PUT | `/api/suppliers/{supplier}` | Cập nhật nhà cung cấp | Admin | 200 (Thành công), 404 (Không tìm thấy), 422 (Dữ liệu không hợp lệ) | `SupplierController::update` | Đã cài đặt |
| 14 | DELETE | `/api/suppliers/{supplier}` | Xóa nhà cung cấp (chặn nếu đã có phiếu nhập) | Admin | 200 (Thành công), 404 (Không tìm thấy), 409 (Xung đột - đã có phiếu nhập) | `SupplierController::destroy` | Đã cài đặt |
| 15 | PATCH | `/api/suppliers/{supplier}/toggle` | Ngừng/kích hoạt hợp tác | Admin | 200 (Thành công), 404 (Không tìm thấy) | `SupplierController::toggle` | Đã cài đặt |
| 16 | GET | `/api/warehouses` | Danh sách kho kèm tổng tồn | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `WarehouseController::index` | Đã cài đặt |
| 17 | POST | `/api/warehouses` | Thêm kho (UC02.03) | Admin | 201 (Thành công), 403 (Không đủ quyền), 422 (Mã kho bị trùng hoặc không hợp lệ) | `WarehouseController::store` | Đã cài đặt |
| 18 | PUT | `/api/warehouses/{warehouse}` | Cập nhật kho | Admin | 200 (Thành công), 404 (Không tìm thấy) | `WarehouseController::update` | Đã cài đặt |
| 19 | DELETE | `/api/warehouses/{warehouse}` | Xóa kho (chặn nếu còn tồn/phiếu) | Admin | 200 (Thành công), 404 (Không tìm thấy), 409 (Xung đột - còn tồn/chứng từ) | `WarehouseController::destroy` | Đã cài đặt |
| 20 | PATCH | `/api/warehouses/{warehouse}/toggle` | Ngừng hoạt động kho (chỉ khi tồn = 0) | Admin | 200 (Thành công), 404 (Không tìm thấy), 409 (Xung đột - tồn lớn hơn 0) | `WarehouseController::toggle` | Đã cài đặt |

## 3. Sản phẩm (Product)

| # | Method | Endpoint | Chức năng | Quyền | Mã trạng thái | Controller | Cài đặt |
|---|---|---|---|---|---|---|---|
| 21 | GET | `/api/products` | Danh sách/tìm kiếm sản phẩm (UC02.04) | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `ProductController::index` | Đã cài đặt |
| 22 | GET | `/api/products/check-sku` | Kiểm tra SKU đã tồn tại trước khi lưu | Admin | 200 (Thành công), 401 (Chưa đăng nhập), 403 (Không đủ quyền) | `ProductController::checkSku` | Đã cài đặt |
| 23 | POST | `/api/products` | Thêm sản phẩm mới (UC02.05) | Admin | 201 (Thành công), 403 (Không đủ quyền), 422 (SKU trùng, lỗi dữ liệu) | `ProductController::store` | Đã cài đặt |
| 24 | GET | `/api/products/{product}` | Chi tiết sản phẩm kèm tồn theo kho | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập), 404 (Không tìm thấy) | `ProductController::show` | Đã cài đặt |
| 25 | PUT | `/api/products/{product}` | Cập nhật sản phẩm (UC02.06) | Admin | 200 (Thành công), 403 (Không đủ quyền), 404 (Không tìm thấy), 422 (Lỗi xác thực/Đổi SKU sai quy tắc) | `ProductController::update` | Đã cài đặt |
| 26 | DELETE | `/api/products/{product}` | Xóa sản phẩm (chỉ khi chưa có giao dịch) | Admin | 200 (Thành công), 404 (Không tìm thấy), 409 (Đã có giao dịch hoặc còn tồn) | `ProductController::destroy` | Đã cài đặt |
| 27 | PATCH | `/api/products/{product}/toggle` | Ngừng kinh doanh sản phẩm (UC02.07) | Admin | 200 (Thành công), 403 (Không đủ quyền), 404 (Không tìm thấy) | `ProductController::toggle` | Đã cài đặt |
| 28 | GET | `/api/products/{product}/stock-card` | Thẻ kho của sản phẩm (UC04.02) | Đã đăng nhập | 200 (Thành công), 404 (Không tìm thấy), 422 (Thời gian không hợp lệ) | `ProductController::stockCard` | Đã cài đặt |

## 4. Nghiệp vụ Nhập kho (Import)

| # | Method | Endpoint | Chức năng | Quyền | Mã trạng thái | Controller | Cài đặt |
|---|---|---|---|---|---|---|---|
| 29 | GET | `/api/imports` | Danh sách/tra cứu phiếu nhập (UC03.03) | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `ImportController::index` | Đã cài đặt |
| 30 | POST | `/api/imports/preview-cost` | Xem trước giá vốn bình quân dự kiến | Nhân viên kho | 200 (Thành công), 403 (Không đủ quyền), 422 (Lỗi dữ liệu) | `ImportController::previewCost` | Đã cài đặt |
| 31 | POST | `/api/imports` | Lập phiếu nhập kho, trạng thái draft (UC03.01) | Nhân viên kho | 201 (Thành công), 403 (Không đủ quyền), 422 (Dữ liệu không hợp lệ) | `ImportController::store` | Khung 501 |
| 32 | GET | `/api/imports/{import}` | Chi tiết phiếu nhập | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập), 404 (Không tìm thấy) | `ImportController::show` | Đã cài đặt |
| 33 | PUT | `/api/imports/{import}` | Cập nhật phiếu nhập còn ở trạng thái draft | Nhân viên kho | 200 (Thành công), 404 (Không tìm thấy), 409 (Phiếu đã xác nhận/hủy, không thể sửa) | `ImportController::update` | Khung 501 |
| 34 | POST | `/api/imports/{import}/confirm` | Xác nhận nhập kho: cộng tồn, tính giá vốn | Nhân viên kho | 200 (Thành công), 404 (Không tìm thấy), 409 (Phiếu không ở trạng thái draft hoặc lỗi transaction) | `ImportController::confirm` | Khung 501 |
| 35 | POST | `/api/imports/{import}/cancel` | Hủy phiếu nháp (UC03.04) | Người lập phiếu / Admin | 200 (Thành công), 403 (Không phải người lập hoặc Admin), 404 (Không tìm thấy), 409 (Phiếu không còn ở trạng thái draft) | `ImportController::cancel` | Khung 501 |

## 5. Nghiệp vụ Xuất kho (Export)

| # | Method | Endpoint | Chức năng | Quyền | Mã trạng thái | Controller | Cài đặt |
|---|---|---|---|---|---|---|---|
| 36 | GET | `/api/exports` | Danh sách/tra cứu phiếu xuất (UC03.03) | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `ExportController::index` | Đã cài đặt |
| 37 | POST | `/api/exports/check-availability` | Kiểm tra tồn khả dụng trước khi xác nhận | Nhân viên kho | 200 (Thành công), 403 (Không đủ quyền), 422 (Lỗi dữ liệu) | `ExportController::checkAvailability` | Đã cài đặt |
| 38 | POST | `/api/exports` | Lập phiếu xuất kho, trạng thái draft (UC03.02) | Nhân viên kho | 201 (Thành công), 403 (Không đủ quyền), 422 (Dữ liệu không hợp lệ) | `ExportController::store` | Khung 501 |
| 39 | GET | `/api/exports/{export}` | Chi tiết phiếu xuất | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập), 404 (Không tìm thấy) | `ExportController::show` | Đã cài đặt |
| 40 | PUT | `/api/exports/{export}` | Cập nhật phiếu xuất còn ở trạng thái draft | Nhân viên kho | 200 (Thành công), 404 (Không tìm thấy), 409 (Phiếu không ở trạng thái draft), 422 (Lỗi dữ liệu) | `ExportController::update` | Khung 501 |
| 41 | POST | `/api/exports/{export}/confirm` | Xác nhận xuất kho: kiểm tra tồn, trừ tồn | Nhân viên kho | 200 (Thành công), 404 (Không tìm thấy), 409 (Số lượng xuất vượt tồn khả dụng, hoặc lỗi transaction) | `ExportController::confirm` | Khung 501 |
| 42 | POST | `/api/exports/{export}/cancel` | Hủy phiếu nháp (UC03.04) | Người lập phiếu / Admin | 200 (Thành công), 403 (Không phải người lập hoặc Admin), 404 (Không tìm thấy), 409 (Phiếu không còn ở trạng thái draft) | `ExportController::cancel` | Khung 501 |

## 6. Tồn kho và Kiểm kê (Inventory, StockAdjustment)

| # | Method | Endpoint | Chức năng | Quyền | Mã trạng thái | Controller | Cài đặt |
|---|---|---|---|---|---|---|---|
| 43 | GET | `/api/inventory` | Tồn kho theo sản phẩm/kho (UC04.01) | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `InventoryController::index` | Đã cài đặt |
| 44 | GET | `/api/inventory/low-stock` | Danh sách cảnh báo tồn thấp (UC04.04) | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `InventoryController::lowStock` | Đã cài đặt |
| 45 | GET | `/api/inventory/total-value` | Tổng giá trị tồn kho hiện tại | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `InventoryController::totalValue` | Đã cài đặt |
| 46 | GET | `/api/adjustments` | Danh sách phiếu kiểm kê | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `StockAdjustmentController::index` | Đã cài đặt |
| 47 | POST | `/api/adjustments` | Lập phiếu kiểm kê, ghi snapshot tồn hệ thống (UC04.03) | Nhân viên kho | 201 (Thành công), 403 (Không đủ quyền), 422 (Dữ liệu không hợp lệ) | `StockAdjustmentController::store` | Khung 501 |
| 48 | PUT | `/api/adjustments/{adjustment}` | Nhập số thực đếm và lý do từng dòng | Nhân viên kho | 200 (Thành công), 404 (Không tìm thấy), 409 (Phiếu đã xác nhận, không thể sửa), 422 (Thiếu dữ liệu/Lý do) | `StockAdjustmentController::update` | Khung 501 |
| 49 | POST | `/api/adjustments/{adjustment}/refresh` | Làm mới snapshot tồn hệ thống | Nhân viên kho | 200 (Thành công), 404 (Không tìm thấy), 409 (Phiếu không ở trạng thái draft) | `StockAdjustmentController::refresh` | Khung 501 |
| 50 | POST | `/api/adjustments/{adjustment}/confirm` | Xác nhận điều chỉnh: ghi chênh lệch vào thẻ kho | Nhân viên kho | 200 (Thành công), 404 (Không tìm thấy), 409 (Tồn hệ thống bị thay đổi, thiếu lý do, hoặc lỗi transaction) | `StockAdjustmentController::confirm` | Khung 501 |

## 7. Báo cáo, Dashboard và Quản trị

| # | Method | Endpoint | Chức năng | Quyền | Mã trạng thái | Controller | Cài đặt |
|---|---|---|---|---|---|---|---|
| 51 | GET | `/api/dashboard/summary` | 5 chỉ số Dashboard theo thời gian thực (UC05.01) | Đã đăng nhập | 200 (Thành công), 401 (Chưa đăng nhập) | `DashboardController::summary` | Đã cài đặt |
| 52 | GET | `/api/reports/stock-summary` | Báo cáo Nhập – Xuất – Tồn (UC05.02) | Admin | 200 (Thành công), 403 (Không đủ quyền) | `ReportController::stockSummary` | Khung 501 |
| 53 | GET | `/api/reports/stock-summary/export` | Xuất Excel báo cáo Nhập – Xuất – Tồn (UC05.04) | Admin | 200 (Thành công), 403 (Không đủ quyền), 404 (Không có dữ liệu) | `ReportController::exportStockSummary` | Khung 501 |
| 54 | GET | `/api/reports/adjustments` | Báo cáo lịch sử kiểm kê/điều chỉnh (UC05.03) | Admin | 200 (Thành công), 403 (Không đủ quyền) | `ReportController::adjustments` | Khung 501 |
| 55 | GET | `/api/reports/adjustments/export` | Xuất Excel báo cáo kiểm kê (UC05.04) | Admin | 200 (Thành công), 403 (Không đủ quyền), 404 (Không có dữ liệu) | `ReportController::exportAdjustments` | Khung 501 |
| 56 | GET | `/api/users` | Danh sách tài khoản người dùng | Admin | 200 (Thành công), 403 (Không đủ quyền) | `UserController::index` | Đã cài đặt |
| 57 | POST | `/api/users` | Tạo tài khoản mới (UC06.01) | Admin | 201 (Thành công), 403 (Không đủ quyền), 422 (Lỗi dữ liệu) | `UserController::store` | Đã cài đặt |
| 58 | PUT | `/api/users/{user}` | Cập nhật thông tin/vai trò người dùng | Admin | 200 (Thành công), 403 (Không đủ quyền), 404 (Không tìm thấy), 422 (Lỗi dữ liệu) | `UserController::update` | Đã cài đặt |
| 59 | PATCH | `/api/users/{user}/toggle` | Khóa/mở khóa tài khoản | Admin | 200 (Thành công), 403 (Không đủ quyền), 404 (Không tìm thấy) | `UserController::toggle` | Đã cài đặt |
| 60 | POST | `/api/users/{user}/reset-password` | Đặt lại mật khẩu tạm cho người dùng | Admin | 200 (Thành công), 403 (Không đủ quyền), 404 (Không tìm thấy) | `UserController::resetPassword` | Đã cài đặt |
| 61 | GET | `/api/activity-logs` | Tra cứu nhật ký hoạt động (UC06.02) | Admin | 200 (Thành công), 403 (Không đủ quyền) | `ActivityLogController::index` | Đã cài đặt |

## Route bổ sung (có trong sơ đồ lớp, không có trong bảng API)

| Method | Endpoint | Quyền | Controller | Cài đặt |
|---|---|---|---|---|
| GET | `/api/categories/{category}` | Mọi tài khoản đã đăng nhập | `CategoryController::show` | Đã cài đặt |
| GET | `/api/suppliers/{supplier}` | Mọi tài khoản đã đăng nhập | `SupplierController::show` | Đã cài đặt |
| GET | `/api/warehouses/{warehouse}` | Mọi tài khoản đã đăng nhập | `WarehouseController::show` | Đã cài đặt |
| GET | `/api/warehouses/{warehouse}/inventories` | Mọi tài khoản đã đăng nhập | `WarehouseController::inventories` | Đã cài đặt |
| GET | `/api/adjustments/{adjustment}` | Mọi tài khoản đã đăng nhập | `StockAdjustmentController::show` | Đã cài đặt |
| POST | `/api/adjustments/{adjustment}/cancel` | Mọi tài khoản đã đăng nhập | `StockAdjustmentController::cancel` | Khung 501 |
| GET | `/api/users/{user}` | Admin | `UserController::show` | Đã cài đặt |
