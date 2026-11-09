<?php

namespace App\Enums;

/**
 * Giá trị cột activity_logs.action (VARCHAR(50)) — quy ước <đối_tượng>_<hành_động> theo ví dụ của báo cáo:
 * login_success, product_created, import_confirmed, report_exported...
 *
 * Cột là chuỗi tự do nên có thể thêm hành động mới bất kỳ lúc nào; enum này chỉ gom các giá trị đã biết
 * để tránh gõ sai chuỗi. ActivityLogService::record() nhận cả enum lẫn chuỗi.
 */
enum ActivityAction: string
{
    // UC01 — Xác thực và hồ sơ cá nhân
    case LoginSuccess = 'login_success';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
    case PasswordChanged = 'password_changed';
    case ProfileUpdated = 'profile_updated';

    // UC02.01 — Danh mục
    case CategoryCreated = 'category_created';
    case CategoryUpdated = 'category_updated';
    case CategoryDeleted = 'category_deleted';

    // UC02.02 — Nhà cung cấp
    case SupplierCreated = 'supplier_created';
    case SupplierUpdated = 'supplier_updated';
    case SupplierDeleted = 'supplier_deleted';
    case SupplierActivated = 'supplier_activated';
    case SupplierDeactivated = 'supplier_deactivated';

    // UC02.03 — Kho
    case WarehouseCreated = 'warehouse_created';
    case WarehouseUpdated = 'warehouse_updated';
    case WarehouseDeleted = 'warehouse_deleted';
    case WarehouseActivated = 'warehouse_activated';
    case WarehouseDeactivated = 'warehouse_deactivated';

    // UC02.05 – UC02.07 — Sản phẩm
    case ProductCreated = 'product_created';
    case ProductUpdated = 'product_updated';
    case ProductDeleted = 'product_deleted';
    case ProductActivated = 'product_activated';
    case ProductDeactivated = 'product_deactivated';

    // UC03 — Nhập / xuất kho
    case ImportCreated = 'import_created';
    case ImportUpdated = 'import_updated';
    case ImportConfirmed = 'import_confirmed';
    case ImportCancelled = 'import_cancelled';
    case ExportCreated = 'export_created';
    case ExportUpdated = 'export_updated';
    case ExportConfirmed = 'export_confirmed';
    case ExportCancelled = 'export_cancelled';

    // UC04.03 — Kiểm kê và điều chỉnh
    case AdjustmentCreated = 'adjustment_created';
    case AdjustmentUpdated = 'adjustment_updated';
    case AdjustmentRefreshed = 'adjustment_refreshed';
    case AdjustmentConfirmed = 'adjustment_confirmed';
    case AdjustmentCancelled = 'adjustment_cancelled';

    // UC05.04 — Xuất báo cáo (BR05.04-7)
    case ReportExported = 'report_exported';

    // UC06.01 — Quản lý tài khoản
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case UserLocked = 'user_locked';
    case UserUnlocked = 'user_unlocked';
    case UserPasswordReset = 'user_password_reset';
}
