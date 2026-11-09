# Inventory Management — Hệ thống Quản lý hàng tồn kho

Khung dự án (boilerplate) **Laravel 13** cho đề tài *Quản lý hàng tồn kho* — Chuyên đề tổng hợp, Nhóm 11 (Jira `CDTH_68PM34_Nhom11`).

Khung được dựng từ hai nguồn:

1. **demo1 — "Quản lý sinh viên"**: lấy bố cục thư mục, tệp cấu hình và các mẫu mã đã dùng ở đó (Service, Filter, Form Request, Policy, `ApiResponse`, kiểm thử SQLite...).
2. **Báo cáo Nhóm 11 (tệp Word)**: quyết định *nội dung* — 15 bảng dữ liệu, 25 use case, 161 quy tắc nghiệp vụ (BR), bảng API 61 dòng, 2 vai trò (Admin, Nhân viên kho), kiến trúc Layered Monolith (MVC + Service Layer).

Khi demo1 và báo cáo khác nhau, **báo cáo là nguồn quyết định**.

> **Lưu ý về tệp Word:** bản nhận được có nội dung đầy đủ của Chương 1–3 (khảo sát, phân tích, thiết kế). Chương 4–7 (triển khai, bảo mật, sao lưu, Jira, kiểm thử, CI/CD) **chỉ có tiêu đề trong mục lục**, không có nội dung. Phần Docker / CI / CD / sao lưu của khung vì vậy bám theo tiêu đề mục và thông lệ Laravel — xem mục 13, điểm 7.

> **Trạng thái:** phần nền (xác thực, phân quyền, 15 bảng, Danh mục, tra cứu phiếu, tồn kho / thẻ kho, Dashboard, quản lý người dùng, nhật ký) đã được cài đặt. Các thao tác **ghi sổ** nhập / xuất / kiểm kê và **báo cáo** mới là khung: trả HTTP 501 kèm hướng dẫn từng bước ngay trong mã (xem mục 11–12). Khung **chưa được chạy dưới Composer / PHPUnit** trong môi trường tạo ra nó (xem mục 14).

## Mục lục

1. [Công nghệ](#1-công-nghệ)
2. [Kiến trúc](#2-kiến-trúc)
3. [Cấu trúc thư mục](#3-cấu-trúc-thư-mục)
4. [Đối chiếu với demo1](#4-đối-chiếu-với-demo1)
5. [Đối chiếu với báo cáo Word](#5-đối-chiếu-với-báo-cáo-word)
6. [Cài đặt và chạy](#6-cài-đặt-và-chạy)
7. [Tài khoản và dữ liệu mẫu](#7-tài-khoản-và-dữ-liệu-mẫu)
8. [REST API](#8-rest-api)
9. [Quy tắc nghiệp vụ đã dựng sẵn](#9-quy-tắc-nghiệp-vụ-đã-dựng-sẵn)
10. [Kiểm thử, CI/CD, Docker](#10-kiểm-thử-cicd-docker)
11. [Trạng thái triển khai](#11-trạng-thái-triển-khai)
12. [Phát triển tiếp](#12-phát-triển-tiếp)
13. [Điểm cần nhóm thống nhất](#13-điểm-cần-nhóm-thống-nhất)
14. [Những gì chưa kiểm chứng](#14-những-gì-chưa-kiểm-chứng)

## 1. Công nghệ

| Thành phần | Phiên bản / ghi chú |
|---|---|
| PHP | `^8.3` (ảnh Docker dùng 8.4) |
| Laravel | `^13.8` |
| Laravel Sanctum | `^4.0` — token Bearer cho API, phiên (session) cho web |
| Livewire | `^4.0` — màn hình tương tác (mẫu tham chiếu: Danh mục) |
| Tailwind CSS | `^4` qua Vite 8 (`@tailwindcss/vite`), cấu hình trong `resources/css/app.css` |
| CSDL | MySQL 8.0 khi chạy thật; SQLite `:memory:` khi chạy test |
| Kiểm thử / định dạng | PHPUnit `^12.5`, Laravel Pint |
| Node | 22 (chỉ để build CSS/JS) |

Ngôn ngữ giao diện, thông báo lỗi và chú thích mã là **tiếng Việt** (`APP_LOCALE=vi`, thư mục `lang/vi`); tên lớp, bảng, cột, route là tiếng Anh như báo cáo.

## 2. Kiến trúc

Báo cáo chọn **Layered Monolith = MVC + Service Layer**. Web (Blade / Livewire) và REST API **dùng chung một lớp Service**, nên mọi quy tắc nghiệp vụ chỉ viết một lần.

```
Trình duyệt                              Ứng dụng khách API (Bearer token)
    │                                          │
┌───▼──────────────────────────────────────────▼────────────────────────┐
│ Presentation  Blade + Livewire 4 + Tailwind 4        REST JSON (/api)  │
├────────────────────────────────────────────────────────────────────────┤
│ Application   Controllers · Livewire · Form Requests · Middleware      │
│               · Policies                                               │
├────────────────────────────────────────────────────────────────────────┤
│ Business      Services (15) — transaction, quy tắc nghiệp vụ, Activity │
│               Log, giá vốn bình quân, số chứng từ                      │
├────────────────────────────────────────────────────────────────────────┤
│ Data Access   Eloquent Models · Filters · Migrations · Seeders         │
├────────────────────────────────────────────────────────────────────────┤
│ MySQL 8       15 bảng của báo cáo (+ bảng hạ tầng Laravel / Sanctum)   │
└────────────────────────────────────────────────────────────────────────┘
```

Luồng xử lý một yêu cầu ghi (ví dụ tạo danh mục):

```
Route ─▶ Middleware (auth|auth:sanctum, active, password.changed, role:admin)
      ─▶ Controller / Livewire ─▶ Form Request (kiểm tra dữ liệu + authorize qua Policy)
      ─▶ Service (DB::transaction, quy tắc BRxx, ActivityLogService::record)
      ─▶ Eloquent ─▶ MySQL
      ─▶ Resource (JSON { "data": ... }) hoặc Blade / Livewire view
```

Lỗi nghiệp vụ được ném thành exception rồi `bootstrap/app.php` đổi thành JSON cho mọi đường dẫn `/api/*`:
`422 { "message", "errors" }`, `401 / 403 / 404 / 409 / 429 / 501 { "message" }`.

## 3. Cấu trúc thư mục

```
inventory-management/
├── .github/                    ci.yml · cd.yml · pull_request_template.md
├── app/
│   ├── Enums/                  Role, UserStatus, DocumentStatus, MovementType, ExportReason,
│   │                           AdjustmentReason, ActivityAction (+ Concerns/HasOptions)
│   ├── Exceptions/             BusinessConflictException (409), TooManyLoginAttemptsException (429),
│   │                           NotImplementedException (501)
│   ├── Http/
│   │   ├── Controllers/Api/    14 controller REST (Auth, Profile, User, Category, Supplier, Warehouse,
│   │   │                       Product, Import, Export, Inventory, StockAdjustment, Dashboard,
│   │   │                       Report, ActivityLog)
│   │   ├── Controllers/Web/    Auth, Dashboard, Profile, Category + PlaceholderController
│   │   ├── Filters/            lọc / tìm kiếm / sắp xếp / phân trang (có whitelist cột sắp xếp)
│   │   ├── Middleware/         EnsureUserHasRole (role:), EnsureUserIsActive (active),
│   │   │                       EnsurePasswordChanged (password.changed)
│   │   ├── Requests/           Store*/Update*Request (+ Concerns/VietnameseMessages)
│   │   └── Resources/          JSON Resource cho từng thực thể
│   ├── Livewire/Categories/    CategoryIndex — màn hình Livewire tham chiếu
│   ├── Models/                 15 model (+ Concerns: HasDocumentStatus, HasStockLevels, Searchable)
│   ├── Policies/               9 policy
│   ├── Providers/              AppServiceProvider
│   ├── Services/               15 service (xem bảng ở mục 11)
│   ├── Support/                Format, Navigation, SessionRevoker
│   └── Traits/ApiResponse.php  phản hồi thành công { "data": ... }
├── bootstrap/app.php           alias middleware + kết xuất lỗi JSON cho /api/*
├── config/                     cấu hình Laravel + inventory.php (nghiệp vụ) + navigation.php (menu theo vai trò)
├── database/
│   ├── migrations/             3 bảng hạ tầng + 15 migration (14 bảng nghiệp vụ + personal_access_tokens)
│   ├── seeders/                User, Category, Supplier, Warehouse, Product, Inventory (+ DatabaseSeeder)
│   └── factories/
├── docker/ · Dockerfile · docker-compose.yml · .dockerignore
├── docs/                       API.md (61 dòng API) · TRACEABILITY.md (UC / BR → mã nguồn)
├── lang/vi/                    thông báo tiếng Việt (auth, validation, passwords, pagination)
├── public/ · storage/
├── resources/
│   ├── css/app.css · js/app.js
│   └── views/                  layouts, partials, auth, dashboard, profile, categories, livewire, errors
├── routes/                     web.php · api.php · console.php
├── scripts/                    db-backup.sh · db-restore.sh
└── tests/                      Feature (10 tệp) · Unit (1 tệp) — 134 test
```

## 4. Đối chiếu với demo1

| demo1 (Quản lý sinh viên) | Khung này (Quản lý hàng tồn kho) | Ghi chú |
|---|---|---|
| `SinhVien` + `SinhVienFilter`, `SinhVienPolicy`, `Store/UpdateSinhVienRequest`, controller web + API | `Product` + cùng bộ lớp | Thực thể nghiệp vụ chính |
| `LopHoc` (danh mục tham chiếu) | `Category` (+ `Supplier`, `Warehouse`) | **Category là lát cắt tham chiếu đầy đủ**: migration → model → filter → request → policy → service → resource → API → Livewire → test |
| `User`, `Enums/Role` | `User`, `Enums/Role` (Admin, Staff), `Enums/UserStatus` | Báo cáo: 2 vai trò; khóa tài khoản, buộc đổi mật khẩu lần đầu |
| `Menu` + `MenuController` (menu lưu trong CSDL) | `config/navigation.php` + `Support/Navigation` | 15 bảng của báo cáo không có bảng menu |
| `CheckGioHanhChinh` + `config/gio_hanh_chinh.php` | `config/inventory.php` | Báo cáo không có giờ hành chính; file cấu hình nghiệp vụ thay thế (đăng nhập sai, mật khẩu, số chứng từ, số lẻ, phân trang, Dashboard) |
| `EnsureUserHasRole`, `EnsureUserIsActive` | giữ nguyên + `EnsurePasswordChanged` | Alias `role`, `active`, `password.changed` khai báo trong `bootstrap/app.php` |
| `Traits/ApiResponse` | `Traits/ApiResponse` | Theo quy ước API của báo cáo: `{ "data": ... }` |
| `Models/Concerns/TimKiemTheoTuKhoa` | `Models/Concerns/Searchable` | Tìm LIKE có thoát ký tự `%` và `_` |
| `Requests/Concerns/ThongBaoTiengViet` | `Requests/Concerns/VietnameseMessages` + `lang/vi` | |
| `Http/Filters/*` (SORTABLE, PER_PAGE_OPTIONS) | `Http/Filters/*` + lớp cha `Filter` | Giá trị lạ bị bỏ qua, không gây lỗi SQL |
| `views/layouts/layoutmaster`, `lop_hocs/`, `sinh_viens/`, `users/` (Blade CRUD) | `views/layouts/app` + `guest`, `categories/` + `livewire/categories/` | Báo cáo chọn Blade + Livewire |
| Tests SQLite `:memory:`, thời gian cố định trong `TestCase` | Cùng cách làm (`tests/TestCase.php`) | "Bây giờ" cố định 10:00 thứ Hai 02/11/2026 |
| `bootstrap/app.php`, `config/*`, `phpunit.xml`, `vite.config.js`, `.npmrc`, `.editorconfig`... | Sao chép và chỉnh | Thêm kết xuất lỗi 409 / 429 / 501 cho API |

## 5. Đối chiếu với báo cáo Word

| Nội dung trong báo cáo | Nơi thể hiện trong khung |
|---|---|
| Vai trò Admin / Nhân viên kho (Chương 1, Bảng 3) và bảng phân quyền | `Enums/Role`, middleware `role:admin`, 9 Policy, `config/navigation.php`, `INVENTORY_ADMIN_CAN_OPERATE` |
| 25 use case UC01.01 – UC06.02 | Docblock của Service / Controller ghi mã UC; chỉ mục đầy đủ ở `docs/TRACEABILITY.md` |
| 161 quy tắc nghiệp vụ `BRxx.xx-n` | Chú thích `BRxx.xx-n` đặt ngay tại mã thực thi (tìm nhanh bằng `grep -rn "BR03.01" app`); bảng đối chiếu ở `docs/TRACEABILITY.md` |
| Từ điển dữ liệu + ERD (15 bảng, tên khóa ngoại) | `database/migrations/2026_11_02_*` — đúng cột, kiểu, NULL, default, UNIQUE, CHECK, tên khóa ngoại |
| Thiết kế API (61 dòng, 7 nhóm) | `routes/api.php` — mỗi route ghi `// #n` là số dòng trong bảng; `docs/API.md` |
| Sơ đồ lớp: 14 Controller, 15 Service | `app/Http/Controllers/Api/*`, `app/Services/*` (tên và phụ thuộc theo sơ đồ) |
| Kiến trúc phân lớp | Mục 2 của README và cấu trúc `app/` |
| Quy trình đăng nhập: khóa 5 lần sai / 15 phút, buộc đổi mật khẩu | `Services/AuthService`, `Middleware/EnsurePasswordChanged`, `config/inventory.php` |
| Số chứng từ `PN` / `PX` / `KK` + `yyyymmdd-nnnn` | `Services/DocumentNumberService` + bảng `document_sequences` |
| Giá vốn bình quân gia quyền (WAC) | `Services/CostingService` (có test thuần `tests/Unit/CostingServiceTest.php`) |
| Phiếu draft → confirmed / cancelled, thẻ kho chỉ ghi thêm | `Models/Concerns/HasDocumentStatus`, `Services/InventoryService::applyMovement`, bảng `stock_movements` |
| Triển khai, sao lưu dữ liệu (Chương 4); CI/CD, GitHub Flow (Chương 7) — *chỉ có tiêu đề trong mục lục* | `Dockerfile`, `docker-compose.yml`, `scripts/db-*.sh`, `.github/workflows`, `.github/pull_request_template.md` |

## 6. Cài đặt và chạy

**Yêu cầu:** PHP ≥ 8.3 (extension `pdo_mysql`, `mbstring`, `bcmath` khuyến nghị), Composer 2, Node 22, MySQL 8.

```bash
# 1) Tạo CSDL (MySQL)
mysql -u root -p -e "CREATE DATABASE inventory_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 2) Cài đặt
cp .env.example .env              # chỉnh DB_USERNAME / DB_PASSWORD nếu cần
composer install
php artisan key:generate
php artisan migrate --seed        # tạo bảng + dữ liệu mẫu
npm install && npm run build      # khi phát triển: npm run dev

# 3) Chạy
php artisan serve                 # http://localhost:8000
```

Lối tắt: `composer setup` (cài đặt đầy đủ), `composer dev` (server + queue + log + Vite), `composer test`, `composer lint` (Pint, chỉ kiểm tra), `composer format` (Pint, tự sửa).

**Docker** (ứng dụng + MySQL 8):

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan db:seed         # hoặc đặt RUN_SEEDERS=true trước khi up
```

| Dịch vụ | Địa chỉ | Ghi chú |
|---|---|---|
| Ứng dụng | http://localhost:8000 | `APP_PORT` đổi cổng; entrypoint tự `migrate` khi `RUN_MIGRATIONS=true` (mặc định bật) |
| MySQL | `localhost:3307` | `DB_FORWARD_PORT` đổi cổng |
| phpMyAdmin | http://localhost:8080 | `docker compose --profile tools up -d` |
| Vite dev server | `localhost:5173` | `docker compose --profile vite up -d` |

Nếu chưa có `public/build`, chạy `npm install && npm run build` trên máy (hoặc bật profile `vite`), nếu không trang sẽ báo *Vite manifest not found*.

## 7. Tài khoản và dữ liệu mẫu

`php artisan migrate --seed` tạo sẵn (chỉ dùng cho phát triển / demo — **đổi hoặc xóa khi triển khai thật**):

| Email | Mật khẩu | Vai trò | Mục đích |
|---|---|---|---|
| `admin@inventory.test` | `Admin@123` | Admin | Toàn quyền |
| `staff@inventory.test` | `Staff@123` | Nhân viên kho | Lập phiếu, xem tồn kho |
| `locked@inventory.test` | `Staff@123` | Nhân viên kho | Đã khóa → "Tài khoản đã bị khóa" (UC01.01, luồng ngoại lệ E2) |
| `newbie@inventory.test` | `Staff@123` | Nhân viên kho | Buộc đổi mật khẩu ở lần đăng nhập đầu (UC01.01, luồng thay thế A1) |

Ngoài ra có danh mục, nhà cung cấp, kho, sản phẩm và tồn kho mẫu (`CategorySeeder`, `SupplierSeeder`, `WarehouseSeeder`, `ProductSeeder`, `InventorySeeder`). Chạy lại seeder nhiều lần không bị trùng dữ liệu (`updateOrCreate`; riêng `InventorySeeder` bỏ qua nếu đã nạp).

## 8. REST API

- Tiền tố `/api` (khai báo trong `bootstrap/app.php`); danh sách đầy đủ: [`docs/API.md`](docs/API.md) và `routes/api.php`.
- Thành công: `{ "data": ... }` (200 / 201). Lỗi kiểm tra dữ liệu: `422 { "message", "errors" }`. Các lỗi khác: `401`, `403`, `404`, `409` (vi phạm quy tắc nghiệp vụ, ví dụ sai trạng thái phiếu), `429` (đăng nhập sai quá 5 lần / 15 phút), `501` (chức năng còn là khung).
- Luôn gửi header `Accept: application/json`.

```bash
# Đăng nhập — nhận token Sanctum
curl -s -X POST http://localhost:8000/api/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@inventory.test","password":"Admin@123"}'
# => { "data": { "token": "...", "token_type": "Bearer", "must_change_password": false, "user": { ... } } }

# Gọi API có xác thực
curl -s http://localhost:8000/api/products -H 'Accept: application/json' -H "Authorization: Bearer $TOKEN"
```

Khi `must_change_password = true`, mọi API trả `403` cho tới khi gọi `PUT /api/auth/password` (trừ `auth/logout`, `auth/me`).

## 9. Quy tắc nghiệp vụ đã dựng sẵn

- **Giá vốn bình quân gia quyền** — `CostingService::weightedAverage()`, làm tròn 4 chữ số thập phân (`config('inventory.decimals')`).
- **Chứng từ** `draft → confirmed | cancelled`; chứng từ đã xác nhận / hủy chỉ đọc. Số chứng từ `PNyyyymmdd-nnnn`, `PXyyyymmdd-nnnn`, `KKyyyymmdd-nnnn`, sinh bằng `DocumentNumberService::next()` (khóa dòng trong `document_sequences`).
- **Thẻ kho** `stock_movements` chỉ ghi thêm (append-only); mọi biến động tồn đi qua `InventoryService::applyMovement()`.
- **Tồn thấp** = tổng tồn của sản phẩm trên mọi kho < `min_stock_level`; thiếu hụt = ngưỡng − tồn, sắp xếp giảm dần (BR04.04).
- **Dashboard** 5 chỉ số: Total Products, Low Stock, Today's Import, Today's Export, Inventory Value (BR05.01). "Hôm nay" tính theo giờ máy chủ (`Asia/Ho_Chi_Minh`); số liệu nhập / xuất là **số phiếu** đã xác nhận trong ngày, kèm tổng giá trị.
- **Đăng nhập**: tối đa 5 lần sai / 15 phút cho mỗi cặp email + IP (HTTP 429), tài khoản khóa không đăng nhập được, tài khoản mới buộc đổi mật khẩu (UC01.01).
- **Nhật ký hoạt động** (`activity_logs`): `ActivityLogService::record()` được gọi trong Service cho các hành động quan trọng (`login_success`, `login_failed`, thay đổi dữ liệu...).

## 10. Kiểm thử, CI/CD, Docker

```bash
composer test                # php artisan config:clear && php artisan test  (SQLite :memory:, không cần MySQL)
composer lint                # Pint (kiểm tra)
```

- **134 test**: `ApiAuth`, `Auth`, `ApiCategory`, `ApiProduct`, `ApiWarehouse`, `ApiUser`, `ApiPermission`, `DocumentNumberService`, `Migration`, `Seeder` (Feature) và `CostingService` (Unit). `TestCase` tắt Vite và cố định thời gian; có sẵn helper `taoUser()`, `dangNhapAdmin()`, `dangNhapNhanVien()`, `luc()`.
- **CI** (`.github/workflows/ci.yml`, chạy khi push / pull request): `lint` (Pint) · `test` (PHPUnit, ma trận PHP) · `migrate-mysql` (`migrate:fresh --seed` rồi `migrate:rollback` trên MySQL 8) · `assets` (Vite build).
- **CD** (`cd.yml`): dựng và đẩy ảnh Docker lên `ghcr.io`, rồi gọi webhook triển khai nếu đã đặt secret `DEPLOY_HOOK_URL`.
- **Docker**: `Dockerfile` nhiều tầng (Node build giao diện → Composer cài thư viện → PHP 8.4 + Apache); `docker/entrypoint.sh` chờ MySQL, tạo `APP_KEY`, chạy migration theo biến môi trường, cache cấu hình khi `APP_ENV=production`.
- **Sao lưu**: `./scripts/db-backup.sh` và `./scripts/db-restore.sh` (MySQL trong Docker Compose).
- `.github/pull_request_template.md`: mẫu Pull Request theo quy trình GitHub Flow (tiêu đề Chương 7 §IV của báo cáo; quy ước nhánh là gợi ý).

## 11. Trạng thái triển khai

"Đã cài đặt" = có logic thật; "Khung 501" = Controller, Form Request, Policy, Resource, route đã nối sẵn nhưng Service ném `NotImplementedException` (HTTP 501) kèm hướng dẫn `TODO` từng bước.

| Nhóm use case | Service | API | Giao diện web |
|---|---|---|---|
| UC01.01 – 01.04 Đăng nhập, đăng xuất, đổi mật khẩu, hồ sơ | `AuthService` — đã cài đặt | 6 route | Đã cài đặt |
| UC02.01 Danh mục sản phẩm | `CategoryService` — đã cài đặt | CRUD | **Livewire** `CategoryIndex` (mẫu tham chiếu) |
| UC02.02 – 02.03 Nhà cung cấp, Kho | `SupplierService`, `WarehouseService` — đã cài đặt | CRUD + toggle | Trang giữ chỗ |
| UC02.04 – 02.07 Sản phẩm (tìm, thêm, sửa, ngừng kinh doanh / xóa), thẻ kho | `ProductService` — đã cài đặt | CRUD + `check-sku`, `toggle`, `stock-card` | Trang giữ chỗ |
| UC03.01, 03.03, 03.04 Phiếu nhập | Tra cứu, chi tiết, xem trước giá vốn — đã cài đặt; **lập / sửa / xác nhận / hủy: Khung 501 (Sprint 3)** | Đã nối | Trang giữ chỗ |
| UC03.02, 03.03, 03.04 Phiếu xuất | Tra cứu, chi tiết, kiểm tra tồn — đã cài đặt; **lập / sửa / xác nhận / hủy: Khung 501 (Sprint 3)** | Đã nối | Trang giữ chỗ |
| UC04.01, 04.02, 04.04 Tồn kho, thẻ kho, tồn thấp | `InventoryService` — đã cài đặt | Đã cài đặt | Trang giữ chỗ |
| UC04.03 Kiểm kê | Tra cứu, chi tiết — đã cài đặt; **tạo / lưu số đếm / làm mới / xác nhận / hủy: Khung 501 (Sprint 3)** | Đã nối | Trang giữ chỗ |
| UC05.01 Dashboard | `DashboardService` — đã cài đặt (5 chỉ số) | Đã cài đặt | Đã cài đặt |
| UC05.02 – 05.04 Báo cáo, xuất Excel | `ReportService` — **Khung 501 (Sprint 4)** | Đã nối | Trang giữ chỗ |
| UC06.01 – 06.02 Người dùng, nhật ký hoạt động | `UserService`, `ActivityLogService` — đã cài đặt | Đã cài đặt | Trang giữ chỗ |

"Trang giữ chỗ" là `PlaceholderController` — hiển thị tên chức năng, mã UC và route API tương ứng.

## 12. Phát triển tiếp

**Thay khung 501 bằng logic thật.** Tìm các điểm cần làm: `grep -rn "NotImplementedException" app` và `grep -rn "TODO (Sprint" app`. Mỗi phương thức có docblock liệt kê đúng các bước của use case (luồng chính, luồng thay thế, ngoại lệ, mã BR). Chỉ cần thay dòng `throw` — Controller, Request, Policy, Resource và route đã sẵn. Thứ tự gợi ý: Nhập kho (`ImportService::confirm` là mẫu: khóa dòng tồn → tính lại giá vốn → cộng tồn → ghi `stock_movements`) → Xuất kho → Kiểm kê → Báo cáo → xuất Excel (cần thêm thư viện, ví dụ `maatwebsite/excel` hoặc `openspout/openspout`).

**Làm màn hình web mới theo khuôn Category.** Sao chép lần lượt: `app/Livewire/Categories/CategoryIndex.php` → `resources/views/livewire/categories/category-index.blade.php` → `resources/views/categories/index.blade.php`, rồi thay `PlaceholderController` trong `routes/web.php` bằng controller trang thật. Mọi thao tác Livewire đều gọi `$this->authorize()` (Policy) và dùng lại `Store*Request::rulesFor()` — luật kiểm tra chung với API.

**Thêm một thực thể mới:** migration → model (`Searchable`) → `Filter` → `Store/Update Request` → Policy → Service → Resource → controller API → route → test (sao từ `ApiProductTest` / `ApiCategoryTest`).

## 13. Điểm cần nhóm thống nhất

Một số chỗ trong báo cáo không khớp nhau; khung chọn phương án ghi bên dưới, nhóm nên chốt rồi sửa báo cáo hoặc mã cho thống nhất.

1. **Database First hay Code First?** Lời mở đầu ghi *Database First*, Chương 3 lại ghi *Code First*. Khung dùng **migration** (Code First) và đã đối chiếu từng cột, ràng buộc với từ điển dữ liệu của báo cáo.
2. **Số Controller / endpoint.** Một chỗ ghi 13 Controller và 46 endpoint; sơ đồ lớp có **14 Controller** và bảng API có **61 dòng**. Khung theo sơ đồ lớp và bảng API; thêm 7 route lấy từ sơ đồ lớp nhưng không có trong bảng (đánh dấu `(+)` trong `routes/api.php`).
3. **`POST /api/imports/preview-cost`** dùng camelCase (`qtyIn`, `unitCost`, `newAvgCost`) trong khi mọi API khác dùng snake_case. Khung giữ **đúng như báo cáo**; muốn thống nhất thì sửa `PreviewImportCostRequest` và `ImportController::previewCost`.
4. **Dashboard "Nhập / Xuất hôm nay"**: báo cáo (BR05.01-4) chỉ nói "phiếu đã xác nhận trong ngày", không rõ là số phiếu hay giá trị. Khung trả **cả hai** (`todays_import` / `todays_export` là số phiếu; `todays_import_amount` / `todays_export_cost` là giá trị).
5. **Admin có được lập phiếu kho không?** Bảng phân quyền chưa rõ. Khung đặt thành cấu hình `INVENTORY_ADMIN_CAN_OPERATE` (mặc định `true`).
6. Lỗi chính tả nhỏ trong tiêu đề từ điển dữ liệu ("Iventories") — tên bảng đúng là `inventories`.
7. **Tệp Word chỉ có nội dung Chương 1–3.** Mục lục liệt kê Chương 4 (triển khai, bảo mật, sao lưu), Chương 5–6 và Chương 7 (Jira, kế hoạch 8 tuần, kế hoạch kiểm thử, GitHub Flow, `ci.yml` / `cd.yml`) nhưng các chương này không có nội dung trong tệp. Vì vậy `Dockerfile`, `docker-compose.yml`, biến môi trường, `scripts/db-*.sh`, `ci.yml`, `cd.yml` và mẫu Pull Request bám theo **tiêu đề mục** và thông lệ Laravel, *không* theo đoạn mã "rút gọn" của báo cáo; "Danh mục Unit Test tiêu biểu" và "Ngưỡng chất lượng" (Chương 7 §III) cũng chưa có để đối chiếu — các test hiện có viết theo use case và quy tắc BR ở Chương 2. Khi có bản đầy đủ, nhóm đối chiếu lại các tệp này.

## 14. Những gì chưa kiểm chứng

Khung được tạo trong môi trường **không cài được Composer** (mạng chặn packagist), nên **chưa chạy** `composer install`, `php artisan` và PHPUnit. Đã làm được:

- `php -l` toàn bộ tệp PHP: không lỗi cú pháp.
- Bộ kiểm tra tĩnh tự viết: route ↔ controller, `use` / namespace, model ↔ migration ↔ từ điển dữ liệu của báo cáo (cột, kiểu, UNIQUE, FK, CHECK), view / route / config được tham chiếu trong Blade.
- Chạy riêng `CostingService` ngoài framework (8/8 ca đúng) và `npm run build` (Vite) thành công.
- `bash -n` cho 3 script shell, phân tích cú pháp YAML cho 2 workflow và `docker compose config` cho `docker-compose.yml` đều đạt. **Chưa** dựng thử ảnh Docker, chưa chạy workflow GitHub Actions và các script sao lưu / phục hồi.

Việc cần làm ngay sau khi nhận khung:

1. `composer install` rồi **commit `composer.lock`** (khung chưa có).
2. `php artisan test` và `php artisan migrate:fresh --seed` — nếu có test đỏ, đọc thông báo và sửa; CI sẽ chạy lại cả hai trên MySQL 8.
3. `composer format` để Pint chuẩn hóa định dạng nếu CI báo lỗi định dạng.
