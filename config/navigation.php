<?php

/*
 * Menu điều hướng + siêu dữ liệu từng màn hình (thay cho bảng `menus` của dự án Quản lý sinh viên:
 * lược đồ 15 bảng trong báo cáo không có bảng menu nên menu được khai báo bằng cấu hình).
 *
 * roles  : vai trò nhìn thấy mục menu (Bảng 9 — phân quyền theo Use Case)
 * uc     : mã Use Case liên quan (Chương 2)
 * sprint : sprint dự kiến hoàn thiện (Bảng 7 — kế hoạch 4 sprint)
 * ready  : true = màn hình đã có thật; false = đang là trang khung (xem PlaceholderController)
 * api    : endpoint REST tương ứng (Chương 3 §III)
 * service: lớp Service cần cài đặt để màn hình hoạt động
 */
return [

    'groups' => [

        [
            'title' => 'Tổng quan',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'roles' => ['admin', 'staff'],
                    'uc' => ['UC05.01'],
                    'sprint' => 4,
                    'ready' => true,
                    'api' => ['GET /api/dashboard/summary'],
                    'service' => 'DashboardService',
                ],
            ],
        ],

        [
            'title' => 'Danh mục nền',
            'items' => [
                [
                    'label' => 'Danh mục sản phẩm',
                    'route' => 'categories.index',
                    'roles' => ['admin'],
                    'uc' => ['UC02.01'],
                    'sprint' => 1,
                    'ready' => true,
                    'api' => ['GET|POST /api/categories', 'GET|PUT|DELETE /api/categories/{id}'],
                    'service' => 'CategoryService',
                ],
                [
                    'label' => 'Nhà cung cấp',
                    'route' => 'suppliers.index',
                    'roles' => ['admin'],
                    'uc' => ['UC02.02'],
                    'sprint' => 1,
                    'ready' => false,
                    'api' => ['GET|POST /api/suppliers', 'PUT|DELETE /api/suppliers/{id}', 'PATCH /api/suppliers/{id}/toggle'],
                    'service' => 'SupplierService',
                ],
                [
                    'label' => 'Kho',
                    'route' => 'warehouses.index',
                    'roles' => ['admin'],
                    'uc' => ['UC02.03'],
                    'sprint' => 2,
                    'ready' => false,
                    'api' => ['GET|POST /api/warehouses', 'PUT|DELETE /api/warehouses/{id}', 'PATCH /api/warehouses/{id}/toggle'],
                    'service' => 'WarehouseService',
                ],
                [
                    'label' => 'Sản phẩm',
                    'route' => 'products.index',
                    'roles' => ['admin', 'staff'],
                    'uc' => ['UC02.04', 'UC02.05', 'UC02.06', 'UC02.07'],
                    'sprint' => 1,
                    'ready' => false,
                    'api' => ['GET|POST /api/products', 'GET /api/products/check-sku', 'GET|PUT|DELETE /api/products/{id}', 'PATCH /api/products/{id}/toggle'],
                    'service' => 'ProductService',
                ],
            ],
        ],

        [
            'title' => 'Nghiệp vụ kho',
            'items' => [
                [
                    'label' => 'Phiếu nhập kho',
                    'route' => 'imports.index',
                    'roles' => ['admin', 'staff'],
                    'uc' => ['UC03.01', 'UC03.03', 'UC03.04'],
                    'sprint' => 3,
                    'ready' => false,
                    'api' => ['GET|POST /api/imports', 'POST /api/imports/preview-cost', 'GET|PUT /api/imports/{id}', 'POST /api/imports/{id}/confirm|cancel'],
                    'service' => 'ImportService',
                ],
                [
                    'label' => 'Phiếu xuất kho',
                    'route' => 'exports.index',
                    'roles' => ['admin', 'staff'],
                    'uc' => ['UC03.02', 'UC03.03', 'UC03.04'],
                    'sprint' => 3,
                    'ready' => false,
                    'api' => ['GET|POST /api/exports', 'POST /api/exports/check-availability', 'GET|PUT /api/exports/{id}', 'POST /api/exports/{id}/confirm|cancel'],
                    'service' => 'ExportService',
                ],
                [
                    'label' => 'Kiểm kê / điều chỉnh',
                    'route' => 'adjustments.index',
                    'roles' => ['admin', 'staff'],
                    'uc' => ['UC04.03'],
                    'sprint' => 3,
                    'ready' => false,
                    'api' => ['GET|POST /api/adjustments', 'PUT /api/adjustments/{id}', 'POST /api/adjustments/{id}/refresh|confirm'],
                    'service' => 'StockAdjustmentService',
                ],
            ],
        ],

        [
            'title' => 'Tồn kho',
            'items' => [
                [
                    'label' => 'Tồn kho',
                    'route' => 'inventory.index',
                    'roles' => ['admin', 'staff'],
                    'uc' => ['UC04.01', 'UC04.02'],
                    'sprint' => 3,
                    'ready' => true,
                    'api' => ['GET /api/inventory', 'GET /api/inventory/total-value', 'GET /api/products/{id}/stock-card'],
                    'service' => 'InventoryService',
                ],
                [
                    'label' => 'Cảnh báo tồn thấp',
                    'route' => 'inventory.low-stock',
                    'roles' => ['admin', 'staff'],
                    'uc' => ['UC04.04'],
                    'sprint' => 3,
                    'ready' => false,
                    'api' => ['GET /api/inventory/low-stock'],
                    'service' => 'InventoryService',
                ],
            ],
        ],

        [
            'title' => 'Báo cáo',
            'items' => [
                [
                    'label' => 'Báo cáo Nhập – Xuất – Tồn',
                    'route' => 'reports.stock-summary',
                    'roles' => ['admin'],
                    'uc' => ['UC05.02', 'UC05.04'],
                    'sprint' => 4,
                    'ready' => false,
                    'api' => ['GET /api/reports/stock-summary', 'GET /api/reports/stock-summary/export'],
                    'service' => 'ReportService',
                ],
                [
                    'label' => 'Báo cáo kiểm kê',
                    'route' => 'reports.adjustments',
                    'roles' => ['admin'],
                    'uc' => ['UC05.03', 'UC05.04'],
                    'sprint' => 4,
                    'ready' => false,
                    'api' => ['GET /api/reports/adjustments', 'GET /api/reports/adjustments/export'],
                    'service' => 'ReportService',
                ],
            ],
        ],

        [
            'title' => 'Quản trị',
            'items' => [
                [
                    'label' => 'Tài khoản người dùng',
                    'route' => 'users.index',
                    'roles' => ['admin'],
                    'uc' => ['UC06.01'],
                    'sprint' => 1,
                    'ready' => false,
                    'api' => ['GET|POST /api/users', 'PUT /api/users/{id}', 'PATCH /api/users/{id}/toggle', 'POST /api/users/{id}/reset-password'],
                    'service' => 'UserService',
                ],
                [
                    'label' => 'Nhật ký hoạt động',
                    'route' => 'activity-logs.index',
                    'roles' => ['admin'],
                    'uc' => ['UC06.02'],
                    'sprint' => 4,
                    'ready' => false,
                    'api' => ['GET /api/activity-logs'],
                    'service' => 'ActivityLogService',
                ],
            ],
        ],
    ],
];
