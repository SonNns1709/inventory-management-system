# Đối chiếu Use Case / Quy tắc nghiệp vụ → mã nguồn

Tài liệu được sinh tự động từ chú thích `UCxx.yy` và `BRxx.yy-n` trong mã nguồn đối chiếu với báo cáo Nhóm 11. Đây là **chỉ mục để lần theo**: một mã được liệt kê nghĩa là tệp đó ghi mã ấy trong chú thích tại nơi quy tắc được thực thi (hoặc sẽ được thực thi, với phần khung 501) — không phải bằng chứng quy tắc đã được kiểm thử đầy đủ.

## 1. Use case → mã nguồn

| UC | Tên | Service | Controller / Livewire | Test | Trạng thái |
|---|---|---|---|---|---|
| UC01.01 | Đăng nhập | `Services\AuthService` | `Api\AuthController`, `Web\AuthController` | `tests\Feature\ApiAuthTest`, `tests\Feature\AuthTest`, `tests\Feature\SeederTest` | Đã cài đặt |
| UC01.02 | Đăng xuất | `Services\AuthService` | `Web\AuthController` | — | Đã cài đặt |
| UC01.03 | Đổi mật khẩu | `Services\AuthService` | `Api\AuthController`, `Web\ProfileController` | `tests\Feature\AuthTest` | Đã cài đặt |
| UC01.04 | Cập nhật hồ sơ cá nhân | `Services\AuthService` | `Api\ProfileController`, `Web\ProfileController` | — | Đã cài đặt |
| UC02.01 | Quản lý danh mục sản phẩm | `Services\CategoryService` | `Api\CategoryController`, `Web\CategoryController`, `Livewire\Categories\CategoryIndex` | `tests\Feature\ApiCategoryTest` | Đã cài đặt |
| UC02.02 | Quản lý nhà cung cấp | `Services\SupplierService` | `Api\SupplierController` | — | Đã cài đặt |
| UC02.03 | Quản lý kho | `Services\WarehouseService` | `Api\WarehouseController` | `tests\Feature\ApiWarehouseTest` | Đã cài đặt |
| UC02.04 | Xem danh sách và tìm kiếm sản phẩm | `Services\ProductService` | `Api\ProductController` | `tests\Feature\ApiProductTest` | Đã cài đặt |
| UC02.05 | Thêm sản phẩm | `Services\ProductService` | — | — | Đã cài đặt |
| UC02.06 | Cập nhật sản phẩm | `Services\ProductService` | — | — | Đã cài đặt |
| UC02.07 | Ngừng kinh doanh / Xóa sản phẩm | `Services\ProductService` | `Api\ProductController` | `tests\Feature\ApiProductTest` | Đã cài đặt |
| UC03.01 | Lập phiếu nhập kho | `Services\CostingService`, `Services\ImportService` | `Api\ImportController` | `tests\Unit\CostingServiceTest` | Một phần — khung 501: `ImportService::createDraft`, `ImportService::updateDraft`, `ImportService::confirm` |
| UC03.02 | Lập phiếu xuất kho | `Services\ExportService` | `Api\ExportController` | — | Một phần — khung 501: `ExportService::createDraft`, `ExportService::updateDraft`, `ExportService::confirm` |
| UC03.03 | Tra cứu phiếu nhập / xuất kho | `Services\ExportService`, `Services\ImportService` | `Api\ExportController`, `Api\ImportController` | — | Đã cài đặt |
| UC03.04 | Hủy phiếu nháp | `Services\ExportService`, `Services\ImportService` | `Api\ExportController`, `Api\ImportController` | — | Một phần — khung 501: `ExportService::cancel`, `ImportService::cancel` |
| UC04.01 | Xem tồn kho theo sản phẩm / kho | `Services\InventoryService` | `Api\InventoryController` | — | Đã cài đặt |
| UC04.02 | Xem thẻ kho | `Services\InventoryService` | `Api\ProductController` | `tests\Feature\ApiProductTest` | Đã cài đặt |
| UC04.03 | Kiểm kê và điều chỉnh tồn kho | `Services\StockAdjustmentService` | `Api\StockAdjustmentController` | — | Một phần — khung 501: `StockAdjustmentService::createSheet`, `StockAdjustmentService::saveCounts`, `StockAdjustmentService::refresh`, `StockAdjustmentService::confirm`, `StockAdjustmentService::cancel` |
| UC04.04 | Xem cảnh báo tồn kho thấp | `Services\InventoryService` | `Api\InventoryController` | — | Đã cài đặt |
| UC05.01 | Xem Dashboard | `Services\DashboardService` | `Api\DashboardController`, `Web\DashboardController` | — | Đã cài đặt |
| UC05.02 | Báo cáo Nhập – Xuất – Tồn | `Services\ReportService` | `Api\ReportController` | — | Một phần — khung 501: `ReportService::stockSummary` |
| UC05.03 | Báo cáo lịch sử kiểm kê / điều chỉnh | `Services\ReportService` | `Api\ReportController` | — | Một phần — khung 501: `ReportService::adjustmentReport` |
| UC05.04 | Xuất báo cáo ra Excel | `Services\ReportService` | `Api\ReportController` | — | Một phần — khung 501: `ReportService::buildExport` |
| UC06.01 | Quản lý tài khoản người dùng | `Services\UserService` | `Api\UserController` | `tests\Feature\ApiUserTest` | Đã cài đặt |
| UC06.02 | Xem nhật ký hoạt động | `Services\ActivityLogService` | `Api\ActivityLogController` | — | Đã cài đặt |

## 2. Quy tắc nghiệp vụ (BR) → mã nguồn

Báo cáo có **161** quy tắc BR; **161** quy tắc có chú thích trong mã, **0** chưa có (nằm ở mục 3). Tìm nhanh trong mã: `grep -rn "BR03.01-2" app routes database tests`.


### UC01.01 — Đăng nhập

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR01.01-1 | Mật khẩu người dùng phải được băm bằng bcrypt, không được lưu trữ hoặc hiển thị dưới dạng plain text. | `Services\UserService`, `tests\Feature\ApiUserTest` |
| BR01.01-2 | Hệ thống giới hạn tối đa 5 lần đăng nhập sai trong khoảng thời gian 15 phút đối với mỗi cặp email + địa chỉ IP. | `.env.example`, `Services\AuthService`, `config\inventory`, `routes\web` (+2) |
| BR01.01-3 | Người dùng chỉ được truy cập các chức năng tương ứng với quyền được cấp theo vai trò Admin hoặc Nhân viên kho. | `Middleware\EnsureUserHasRole` |

### UC01.02 — Đăng xuất

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR01.02-1 | Đăng xuất chỉ hủy phiên đăng nhập hiện tại, không ảnh hưởng đến các phiên đăng nhập khác của cùng tài khoản trên thiết bị khác. | `Api\AuthController`, `Web\AuthController`, `Services\AuthService`, `tests\Feature\ApiAuthTest` |
| BR01.02-2 | Sau khi đăng xuất thành công, người dùng phải thực hiện đăng nhập lại để tiếp tục truy cập các chức năng yêu cầu xác thực. | `Services\AuthService` |

### UC01.03 — Đổi mật khẩu

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR01.03-1 | (xem báo cáo) | `Requests\ChangePasswordRequest`, `Services\AuthService` |
| BR01.03-2 | (xem báo cáo) | `Requests\ChangePasswordRequest`, `Providers\AppServiceProvider`, `Services\AuthService`, `config\inventory` (+3) |
| BR01.03-3 | (xem báo cáo) | `Api\AuthController`, `Web\ProfileController`, `Services\AuthService`, `Support\SessionRevoker` (+1) |

### UC01.04 — Cập nhật hồ sơ cá nhân

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR01.04-1 | (xem báo cáo) | `Api\ProfileController`, `Web\ProfileController`, `Requests\UpdateProfileRequest`, `Services\AuthService` (+2) |
| BR01.04-2 | (xem báo cáo) | `Api\ProfileController`, `Web\ProfileController`, `Requests\UpdateProfileRequest`, `Services\AuthService` (+2) |
| BR01.04-3 | (xem báo cáo) | `Requests\UpdateProfileRequest`, `Services\UserService` |

### UC02.01 — Quản lý danh mục sản phẩm

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR02.01-1 | (xem báo cáo) | `Requests\StoreCategoryRequest`, `Livewire\Categories\CategoryIndex`, `Services\CategoryService`, `resources\views\livewire\categories\category-index.blade` (+1) |
| BR02.01-2 | (xem báo cáo) | `Requests\StoreCategoryRequest`, `Livewire\Categories\CategoryIndex`, `Services\CategoryService`, `database\factories\CategoryFactory` (+1) |
| BR02.01-3 | (xem báo cáo) | `Api\CategoryController`, `Livewire\Categories\CategoryIndex`, `Services\CategoryService`, `resources\views\livewire\categories\category-index.blade` (+2) |
| BR02.01-4 | (xem báo cáo) | `Policies\CategoryPolicy`, `Services\CategoryService` |
| BR02.01-5 | (xem báo cáo) | `Services\ActivityLogService`, `Services\CategoryService`, `tests\Feature\ApiCategoryTest` |

### UC02.02 — Quản lý nhà cung cấp

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR02.02-1 | Tên nhà cung cấp phải duy nhất trong hệ thống. | `Requests\StoreSupplierRequest`, `Services\SupplierService` |
| BR02.02-2 | Thông tin nhà cung cấp phải được kiểm tra tính hợp lệ trước khi lưu. | `Requests\StoreSupplierRequest` |
| BR02.02-3 | Nhà cung cấp đã phát sinh phiếu nhập liên quan không được xóa vĩnh viễn. | `Services\SupplierService` |
| BR02.02-4 | Đối với nhà cung cấp đã có giao dịch, Admin chỉ được chuyển sang trạng thái "Ngừng hợp tác" để bảo toàn dữ liệu lịch sử. | `Services\SupplierService` |
| BR02.02-5 | Khi ngừng hoặc kích hoạt hợp tác, hệ thống thay đổi trạng thái is_active nhưng không xóa dữ liệu lịch sử. | `Api\SupplierController`, `Services\SupplierService` |
| BR02.02-6 | Chỉ Admin được phép thực hiện các thao tác quản lý nhà cung cấp. | `Requests\StoreSupplierRequest`, `Policies\SupplierPolicy`, `Services\SupplierService` |
| BR02.02-7 | Các thao tác thêm, sửa, ngừng hợp tác, kích hoạt hoặc xóa nhà cung cấp phải được ghi nhận vào Activity Log. | `Services\SupplierService` |

### UC02.03 — Quản lý kho

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR02.03-1 | Mã kho phải hợp lệ và duy nhất trong hệ thống. | `Requests\StoreWarehouseRequest`, `Services\WarehouseService`, `tests\Feature\ApiWarehouseTest` |
| BR02.03-2 | Chỉ Admin được phép quản lý thông tin kho. | `Requests\StoreWarehouseRequest`, `Policies\WarehousePolicy` |
| BR02.03-3 | Một kho chỉ được ngừng hoạt động khi tổng tồn kho bằng 0. | `Api\WarehouseController`, `Services\WarehouseService`, `tests\Feature\ApiWarehouseTest` |
| BR02.03-4 | Một kho chỉ được xóa khi chưa từng phát sinh tồn kho hoặc chứng từ. | `Services\WarehouseService`, `tests\Feature\ApiWarehouseTest` |
| BR02.03-5 | Không được xóa hoặc ngừng hoạt động kho nếu việc này làm ảnh hưởng đến tính toàn vẹn của dữ liệu tồn kho và chứng từ lịch sử. | `Services\WarehouseService` |
| BR02.03-6 | Các thao tác thêm, cập nhật, ngừng hoạt động hoặc xóa kho phải được ghi nhận vào Activity Log. | `Services\WarehouseService` |

### UC02.04 — Xem danh sách và tìm kiếm sản phẩm

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR02.04-1 | Người dùng chỉ được xem danh sách và thông tin sản phẩm theo quyền truy cập đã được cấp. | `Policies\ProductPolicy` |
| BR02.04-2 | Có thể tìm kiếm sản phẩm theo SKU hoặc tên sản phẩm. | `Filters\ProductFilter`, `tests\Feature\ApiProductTest` |
| BR02.04-3 | Danh sách sản phẩm có thể được lọc theo danh mục, trạng thái và tình trạng tồn dưới ngưỡng. | `Filters\ProductFilter`, `tests\Feature\ApiProductTest` |
| BR02.04-4 | Khi xem danh sách, hệ thống phải cung cấp thông tin tổng tồn của sản phẩm tại các kho. | `Api\ProductController`, `Services\ProductService` |
| BR02.04-5 | Sản phẩm có tồn dưới ngưỡng cảnh báo phải được đánh dấu để người dùng dễ nhận biết. | `Api\ProductController`, `Resources\ProductResource`, `Services\ProductService` |
| BR02.04-6 | Khi xem chi tiết sản phẩm, hệ thống phải hiển thị tồn theo từng kho, giá vốn bình quân và ngưỡng cảnh báo. | `Api\ProductController`, `Resources\ProductResource`, `Services\ProductService`, `tests\Feature\ApiProductTest` |

### UC02.05 — Thêm sản phẩm

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR02.05-1 | SKU là duy nhất trên toàn hệ thống. SKU được kiểm tra ở tầng ứng dụng và được đảm bảo bằng ràng buộc UNIQUE trong CSDL. | `Requests\StoreProductRequest`, `Services\ProductService`, `tests\Feature\ApiProductTest` |
| BR02.05-2 | Sản phẩm mới được tạo với avg_cost = 0 và is_active = true, chưa có tồn kho. | `Api\ProductController`, `Requests\StoreProductRequest`, `Services\ProductService`, `database\factories\ProductFactory` |
| BR02.05-3 | Giá vốn bình quân không được nhập thủ công khi tạo sản phẩm. Giá vốn chỉ được hệ thống tính khi xác nhận phiếu nhập đầu tiên — UC0… | `Requests\StoreProductRequest`, `Services\ProductService`, `tests\Feature\ApiProductTest` |
| BR02.05-4 | Chỉ Admin được phép thực hiện chức năng thêm sản phẩm. | `Requests\StoreProductRequest`, `Policies\ProductPolicy` |
| BR02.05-5 | Sản phẩm phải có đầy đủ các thông tin bắt buộc và dữ liệu phải đúng định dạng trước khi được lưu. | `Requests\StoreProductRequest`, `database\factories\ProductFactory` |
| BR02.05-6 | Khi thêm sản phẩm thành công, hệ thống phải ghi nhận thao tác vào Activity Log. | `Services\ProductService` |

### UC02.06 — Cập nhật sản phẩm

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR02.06-1 | Chỉ Admin được phép cập nhật thông tin sản phẩm. | `Policies\ProductPolicy` |
| BR02.06-2 | Sản phẩm phải tồn tại trong hệ thống trước khi thực hiện cập nhật. | `Services\ProductService` |
| BR02.06-3 | SKU chỉ được phép thay đổi khi sản phẩm chưa phát sinh bất kỳ giao dịch nào. | `Api\ProductController`, `Requests\UpdateProductRequest`, `Services\ProductService`, `tests\Feature\ApiProductTest` |
| BR02.06-4 | Sản phẩm đã có giao dịch không được đổi SKU nhằm bảo đảm tính liên tục của thẻ kho và dữ liệu lịch sử. | `Requests\UpdateProductRequest`, `Services\ProductService` |
| BR02.06-5 | Khi cập nhật sản phẩm, hệ thống phải ghi nhận giá trị cũ và giá trị mới vào nhật ký hoạt động. | `Services\ProductService`, `tests\Feature\ApiProductTest` |
| BR02.06-6 | Các thông tin cập nhật phải được kiểm tra tính hợp lệ trước khi lưu. | `Services\ProductService` |

### UC02.07 — Ngừng kinh doanh / Xóa sản phẩm

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR02.07-1 | (xem báo cáo) | `Services\ProductService` |
| BR02.07-2 | (xem báo cáo) | `Services\ProductService` |
| BR02.07-3 | (xem báo cáo) | `Services\ProductService` |
| BR02.07-4 | (xem báo cáo) | `Services\ProductService` |
| BR02.07-5 | (xem báo cáo) | `Services\ProductService` |
| BR02.07-6 | (xem báo cáo) | `Services\ProductService` |
| BR02.07-7 | (xem báo cáo) | `Services\ProductService` |

### UC03.01 — Lập phiếu nhập kho

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR03.01-1 | Chỉ Nhân viên kho được phép lập phiếu nhập kho. | `.env.example`, `Requests\StoreImportRequest`, `Models\User`, `Services\ImportService` (+1) |
| BR03.01-2 | Phiếu nhập phải có nhà cung cấp và kho nhập hợp lệ. | `Requests\StoreImportRequest`, `Services\ImportService` |
| BR03.01-3 | Phiếu có thể được lưu ở trạng thái draft và phiếu nháp chưa được phép làm thay đổi tồn kho. | `Api\ImportController`, `Services\ImportService` |
| BR03.01-4 | Chỉ khi phiếu được xác nhận, hệ thống mới cộng tồn kho và cập nhật giá vốn bình quân. | `Services\ImportService` |
| BR03.01-5 | Khi xác nhận, hệ thống phải tính lại giá vốn bình quân gia quyền cho từng sản phẩm. | `Services\CostingService`, `Services\ImportService`, `tests\Unit\CostingServiceTest` |
| BR03.01-6 | Khi xác nhận thành công, hệ thống phải ghi nhận biến động vào thẻ kho. | `Services\ImportService` |
| BR03.01-7 | Các dữ liệu quan trọng khi xác nhận phiếu gồm người xác nhận và thời gian xác nhận phải được ghi nhận. | `Services\ImportService` |
| BR03.01-8 | Nếu dữ liệu không hợp lệ, hệ thống không được xác nhận một phần mà phải từ chối xác nhận phiếu. | `Services\ImportService`, `Services\InventoryService` |

### UC03.02 — Lập phiếu xuất kho

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR03.02-1 | Chỉ Nhân viên kho được phép lập phiếu xuất kho. | `.env.example`, `Models\User`, `Services\ExportService`, `config\inventory` |
| BR03.02-2 | Kho xuất phải đang hoạt động và có tồn kho để thực hiện xuất. | `Requests\StoreExportRequest`, `Services\ExportService` |
| BR03.02-3 | Số lượng xuất của mọi dòng không được vượt quá tồn khả dụng tại kho xuất. | `Requests\StoreExportRequest`, `Services\ExportService` |
| BR03.02-4 | Không cho phép xác nhận một phần phiếu; tất cả các dòng phải hợp lệ thì phiếu mới được xác nhận. | `Api\ExportController`, `Services\ExportService`, `Services\InventoryService` |
| BR03.02-5 | Giá vốn hàng xuất được lấy theo giá vốn bình quân của sản phẩm tại đúng thời điểm xác nhận phiếu xuất. | `Services\CostingService`, `Services\ExportService`, `tests\Unit\CostingServiceTest` |
| BR03.02-6 | Khi chuyển kho, giá vốn bình quân của sản phẩm không thay đổi, chỉ dịch chuyển số lượng giữa hai kho. | `Services\CostingService`, `Services\ExportService`, `tests\Unit\CostingServiceTest` |
| BR03.02-7 | Phiếu chỉ được chuyển sang trạng thái confirmed sau khi toàn bộ các dòng đã được kiểm tra hợp lệ và cập nhật tồn kho thành công. | `Services\ExportService` |
| BR03.02-8 | Phiếu xuất ở trạng thái draft chưa được phép làm thay đổi tồn kho. | `Api\ExportController`, `Services\ExportService` |

### UC03.03 — Tra cứu phiếu nhập / xuất kho

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR03.03-1 | Chỉ Nhân viên kho và Admin được phép tra cứu phiếu nhập/xuất kho. | `Policies\ExportPolicy`, `Policies\ImportPolicy`, `Policies\StockAdjustmentPolicy` |
| BR03.03-2 | Người dùng có thể tra cứu phiếu theo các tiêu chí: | `Filters\ExportFilter`, `Filters\ImportFilter`, `Services\ImportService` |
| BR03.03-3 | Kết quả tra cứu phải được hiển thị theo dạng phân trang. | `Services\ImportService` |
| BR03.03-4 | Phiếu ở trạng thái draft có thể hiển thị các thao tác Sửa, Xác nhận, Hủy. | `Models\Concerns\HasDocumentStatus` |
| BR03.03-5 | Phiếu ở trạng thái confirmed hoặc cancelled chỉ được hiển thị ở chế độ chỉ đọc. | `Models\Concerns\HasDocumentStatus` |
| BR03.03-6 | Khi xem chi tiết phiếu, hệ thống phải hiển thị các thông tin quan trọng gồm dòng hàng, giá vốn, người lập, người xác nhận và thời … | `Api\ImportController`, `Services\ExportService`, `Services\ImportService` |
| BR03.03-7 | Chức năng tra cứu không được tự ý làm thay đổi tồn kho hoặc trạng thái phiếu. | `Services\ExportService`, `Services\ImportService` |

### UC03.04 — Hủy phiếu nháp

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR03.04-1 | Chỉ phiếu ở trạng thái draft mới được phép hủy. | `Services\ImportService` |
| BR03.04-2 | Phiếu ở trạng thái confirmed là bất biến và không thể hủy trực tiếp. | `Requests\UpdateImportRequest`, `Services\ImportService` |
| BR03.04-3 | Phiếu bị hủy chuyển sang trạng thái cancelled. | `Services\ImportService` |
| BR03.04-4 | Hủy phiếu nháp không làm thay đổi tồn kho, vì phiếu chưa được xác nhận chính thức. | `Services\ImportService` |
| BR03.04-5 | Người thực hiện phải có quyền hủy phiếu; người không phải người lập phiếu hoặc Admin thì không được phép thực hiện thao tác này. | `Api\ImportController`, `Policies\ExportPolicy`, `Policies\ImportPolicy`, `Policies\StockAdjustmentPolicy` (+1) |
| BR03.04-6 | Mọi thao tác hủy phiếu phải được ghi nhận vào nhật ký hoạt động. | `Services\ActivityLogService`, `Services\ImportService` |

### UC04.01 — Xem tồn kho theo sản phẩm / kho

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR04.01-1 | (xem báo cáo) | `Filters\InventoryFilter` |
| BR04.01-2 | (xem báo cáo) | `Services\InventoryService` |
| BR04.01-3 | (xem báo cáo) | `Services\InventoryService` |
| BR04.01-4 | (xem báo cáo) | `Services\InventoryService` |
| BR04.01-5 | (xem báo cáo) | `Services\InventoryService` |

### UC04.02 — Xem thẻ kho

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR04.02-1 | Chỉ Nhân viên kho và Admin được phép xem thẻ kho. | `Services\InventoryService` |
| BR04.02-2 | Thẻ kho được xác định theo sản phẩm, kho và khoảng thời gian được người dùng lựa chọn. | `Services\InventoryService` |
| BR04.02-3 | Hệ thống phải tính tồn đầu kỳ trước khi hiển thị các biến động trong khoảng thời gian được chọn. | `Services\InventoryService`, `tests\Feature\ApiProductTest` |
| BR04.02-4 | Mỗi giao dịch được xác nhận phải sinh đúng một dòng stock_movements. | `Services\InventoryService`, `tests\Feature\SeederTest` |
| BR04.02-5 | Thẻ kho là sổ chỉ ghi thêm (append-only); các dòng đã ghi không được sửa hoặc xóa. | `Services\InventoryService` |
| BR04.02-6 | Mỗi dòng biến động phải thể hiện tối thiểu ngày, loại, số phiếu, số lượng thay đổi, tồn sau và giá vốn. | `Resources\StockMovementResource`, `Services\InventoryService`, `tests\Feature\ApiProductTest` |
| BR04.02-7 | Người dùng có thể mở chi tiết phiếu gốc từ một dòng biến động. | `Services\InventoryService`, `tests\Feature\ApiProductTest` |
| BR04.02-8 | Chức năng xem thẻ kho chỉ có tính chất tra cứu, không được làm thay đổi dữ liệu stock_movements hoặc số lượng tồn. | `Api\ProductController`, `Services\InventoryService` |

### UC04.03 — Kiểm kê và điều chỉnh tồn kho

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR04.03-1 | Chỉ Nhân viên kho được phép thực hiện nghiệp vụ kiểm kê và điều chỉnh tồn kho. | `.env.example`, `Models\User`, `Policies\StockAdjustmentPolicy`, `config\inventory` |
| BR04.03-2 | Số lượng hệ thống dùng để so sánh là snapshot tại thời điểm lập phiếu, không phải số liệu tức thời tại thời điểm xác nhận. | `Api\StockAdjustmentController`, `Services\StockAdjustmentService` |
| BR04.03-3 | Hệ thống phải kiểm tra lại tồn kho trước khi ghi sổ để phát hiện các thay đổi phát sinh sau khi lập phiếu. | `Services\StockAdjustmentService` |
| BR04.03-4 | Chênh lệch được tính theo công thức: | `Api\StockAdjustmentController`, `Services\StockAdjustmentService` |
| BR04.03-5 | Nếu diff_qty ≠ 0, Nhân viên phải chọn lý do chênh lệch; các lý do gồm thất lạc, hư hỏng, hao hụt hoặc khác. | `Enums\AdjustmentReason`, `Requests\UpdateStockAdjustmentRequest`, `Services\StockAdjustmentService` |
| BR04.03-6 | Chênh lệch sau khi xác nhận phải được ghi nhận vào thẻ kho kèm lý do. | `Services\StockAdjustmentService` |
| BR04.03-7 | Kiểm kê và điều chỉnh tồn kho là cơ chế duy nhất được phép thay đổi trực tiếp số lượng tồn kho ngoài các phiếu nhập/xuất. | `Services\StockAdjustmentService` |
| BR04.03-8 | Phiếu kiểm kê có các trạng thái draft, confirmed, cancelled, trong đó trạng thái mặc định khi tạo là draft. | `Services\StockAdjustmentService` |

### UC04.04 — Xem cảnh báo tồn kho thấp

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR04.04-1 | Chỉ Nhân viên kho và Admin được phép xem cảnh báo tồn kho thấp. | `Services\InventoryService` |
| BR04.04-2 | Ngưỡng cảnh báo được thiết lập riêng cho từng sản phẩm thông qua min_stock_level. | `Services\InventoryService` |
| BR04.04-3 | Hệ thống phải so sánh min_stock_level với tổng tồn của sản phẩm trên tất cả các kho, không phải tồn riêng từng kho. | `Api\InventoryController`, `Filters\ProductFilter`, `Models\Concerns\HasStockLevels`, `Services\InventoryService` (+1) |
| BR04.04-4 | Một sản phẩm được đưa vào danh sách cảnh báo khi: | `Api\InventoryController`, `Resources\ProductResource`, `Models\Concerns\HasStockLevels`, `Services\InventoryService` |
| BR04.04-5 | Mức thiếu được xác định theo: | `Api\InventoryController`, `Resources\ProductResource`, `Models\Concerns\HasStockLevels`, `Services\InventoryService` |
| BR04.04-6 | Danh sách cảnh báo phải được sắp xếp giảm dần theo mức thiếu. | `Api\InventoryController`, `Models\Concerns\HasStockLevels`, `Services\InventoryService` |
| BR04.04-7 | Chức năng cảnh báo chỉ có tính chất theo dõi/tra cứu, không trực tiếp làm thay đổi số lượng tồn kho. | `Services\InventoryService` |
| BR04.04-8 | Người dùng có thể chọn sản phẩm trong danh sách cảnh báo để chuyển sang chi tiết sản phẩm và thẻ kho. | `Services\InventoryService` |

### UC05.01 — Xem Dashboard

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR05.01-1 | (xem báo cáo) | `Services\DashboardService` |
| BR05.01-2 | (xem báo cáo) | `Services\DashboardService`, `resources\views\dashboard\index.blade` |
| BR05.01-3 | (xem báo cáo) | `Api\InventoryController`, `Services\CostingService`, `Services\InventoryService` |
| BR05.01-4 | (xem báo cáo) | `Services\DashboardService` |
| BR05.01-5 | (xem báo cáo) | `Services\DashboardService` |
| BR05.01-6 | (xem báo cáo) | `Services\DashboardService` |
| BR05.01-7 | (xem báo cáo) | `Web\DashboardController`, `Services\DashboardService` |

### UC05.02 — Báo cáo Nhập – Xuất – Tồn

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR05.02-1 | Chức năng Báo cáo Nhập – Xuất – Tồn chỉ dành cho Admin. | `Providers\AppServiceProvider` |
| BR05.02-2 | Báo cáo cho phép lọc theo: | `Services\ReportService` |
| BR05.02-3 | Dữ liệu báo cáo được tổng hợp từ stock_movements theo từng sản phẩm. | `Services\ReportService` |
| BR05.02-4 | Báo cáo phải thể hiện các chỉ tiêu: | `Services\ReportService` |
| BR05.02-5 | Tồn cuối được đối chiếu theo công thức: | `Services\ReportService` |
| BR05.02-6 | Hệ thống phải tính giá trị theo giá vốn và hiển thị dòng tổng cộng. | `Services\ReportService` |
| BR05.02-7 | Khi chọn một kho cụ thể, giao dịch chuyển kho được tính vào Nhập/Xuất của đúng kho đó. | `Services\ReportService` |
| BR05.02-8 | Khi xem báo cáo tất cả các kho, hai chiều của giao dịch chuyển kho triệt tiêu lẫn nhau. | `Services\ReportService` |
| BR05.02-9 | Báo cáo có tính chất tra cứu và tổng hợp, không được làm thay đổi dữ liệu tồn kho hoặc stock_movements. | `Api\ReportController`, `Services\ReportService` |

### UC05.03 — Báo cáo lịch sử kiểm kê / điều chỉnh

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR05.03-1 | Chức năng Báo cáo lịch sử kiểm kê / điều chỉnh chỉ dành cho Admin. Danh sách UC của tài liệu xác định UC05.03 có tác nhân là Admin… | `Providers\AppServiceProvider` |
| BR05.03-2 | Báo cáo cho phép lọc theo: | `Services\ReportService` |
| BR05.03-3 | Hệ thống chỉ truy vấn các phiếu kiểm kê đã confirmed trong kỳ. | `Services\ReportService` |
| BR05.03-4 | Mỗi dòng báo cáo phải thể hiện: | `Services\ReportService` |
| BR05.03-5 | Hệ thống phải tổng hợp tối thiểu: | `Services\ReportService` |
| BR05.03-6 | Báo cáo có tính chất tra cứu và tổng hợp, không được trực tiếp thay đổi số lượng tồn kho hoặc dữ liệu phiếu kiểm kê. | `Api\ReportController`, `Services\ReportService` |
| BR05.03-7 | Các phiếu ở trạng thái chưa confirmed không được đưa vào kết quả báo cáo, vì Basic Flow của tài liệu chỉ truy vấn các phiếu đã con… | `Services\ReportService` |

### UC05.04 — Xuất báo cáo ra Excel

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR05.04-1 | Chức năng Xuất báo cáo ra Excel chỉ dành cho Admin. | `Providers\AppServiceProvider` |
| BR05.04-2 | Admin chỉ có thể xuất Excel từ các báo cáo được hỗ trợ: | `Services\ReportService` |
| BR05.04-3 | Báo cáo phải có dữ liệu trước khi thực hiện xuất Excel. | `Services\ReportService` |
| BR05.04-4 | Tệp Excel phải được tạo theo đúng bộ lọc hiện tại của báo cáo. | `Services\ReportService` |
| BR05.04-5 | Định dạng tệp xuất ra phải là .xlsx. | `Services\ReportService` |
| BR05.04-6 | Nếu báo cáo không có dữ liệu, hệ thống không được tạo tệp Excel rỗng. | `Services\ReportService` |
| BR05.04-7 | Sau khi xuất thành công, hệ thống phải ghi nhật ký hoạt động với hành động report_exported. | `Enums\ActivityAction`, `Services\ActivityLogService`, `Services\ReportService` |
| BR05.04-8 | Việc xuất báo cáo chỉ có tính chất đọc và xuất dữ liệu, không được làm thay đổi dữ liệu tồn kho, phiếu nhập/xuất, phiếu kiểm kê ho… | `Api\ReportController`, `Services\ReportService` |

### UC06.01 — Quản lý tài khoản người dùng

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR06.01-1 | Chức năng Quản lý tài khoản người dùng chỉ dành cho Admin. | `Policies\UserPolicy` |
| BR06.01-2 | Admin có thể thực hiện các thao tác: | `Services\UserService` |
| BR06.01-3 | Khi tạo tài khoản, thông tin gồm họ tên, email, vai trò và mật khẩu tạm. | `Requests\StoreUserRequest`, `Services\UserService`, `tests\Feature\ApiUserTest` |
| BR06.01-4 | Email của tài khoản phải là duy nhất trong hệ thống. Nếu email đã tồn tại, hệ thống không được tạo tài khoản mới. | `Requests\StoreUserRequest`, `Services\UserService`, `tests\Feature\ApiUserTest` |
| BR06.01-5 | Luôn phải tồn tại ít nhất một tài khoản Admin đang hoạt động trong hệ thống. | `Services\UserService` |
| BR06.01-6 | Không được khóa hoặc hạ quyền tài khoản Admin duy nhất còn lại. | `Api\UserController`, `Requests\UpdateUserRequest`, `Policies\UserPolicy`, `Services\UserService` (+1) |
| BR06.01-7 | Admin không được tự khóa tài khoản đang đăng nhập. | `Policies\UserPolicy`, `Services\UserService`, `tests\Feature\ApiUserTest` |
| BR06.01-8 | Khi khóa tài khoản, hệ thống phải thu hồi ngay các phiên đăng nhập hiện có của tài khoản đó. | `Api\UserController`, `Middleware\EnsureUserIsActive`, `Services\UserService`, `Support\SessionRevoker` (+2) |
| BR06.01-9 | Khi đặt lại mật khẩu, hệ thống tạo mật khẩu tạm, hiển thị một lần cho Admin và đặt must_change_password = true để buộc người dùng … | `Api\UserController`, `Services\UserService`, `Support\SessionRevoker`, `config\inventory` (+1) |
| BR06.01-10 | Mọi thao tác quản lý tài khoản phải được ghi nhật ký. | `Services\ActivityLogService`, `Services\UserService`, `tests\Feature\ApiUserTest` |

### UC06.02 — Xem nhật ký hoạt động

| BR | Nội dung | Nơi gắn mã |
|---|---|---|
| BR06.02-1 | Chức năng Xem nhật ký hoạt động chỉ dành cho Admin. | `Policies\ActivityLogPolicy` |
| BR06.02-2 | Nhật ký có thể được lọc theo: | `Filters\ActivityLogFilter` |
| BR06.02-3 | Dữ liệu nhật ký được truy vấn từ bảng activity_logs. | `Services\ActivityLogService` |
| BR06.02-4 | Danh sách nhật ký phải được phân trang và hiển thị theo thứ tự mới nhất trước. | `Filters\ActivityLogFilter`, `Services\ActivityLogService` |
| BR06.02-5 | Admin có thể chọn một bản ghi để xem giá trị cũ và giá trị mới dạng JSON. | `Resources\ActivityLogResource` |
| BR06.02-6 | Nhật ký hoạt động chỉ được hệ thống ghi tự động. | `Api\ActivityLogController`, `Policies\ActivityLogPolicy`, `Services\ActivityLogService` |
| BR06.02-7 | Không người dùng nào được phép sửa hoặc xóa nhật ký qua giao diện. | `Api\ActivityLogController`, `Policies\ActivityLogPolicy`, `Services\ActivityLogService` |
| BR06.02-8 | Việc xem nhật ký chỉ có tính chất tra cứu, không được làm thay đổi dữ liệu trong activity_logs. | `Services\ActivityLogService` |

## 3. Quy tắc chưa có chú thích mã trong mã nguồn

Không còn quy tắc nào thiếu chú thích.
