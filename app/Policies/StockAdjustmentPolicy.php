<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\StockAdjustment;
use App\Models\User;

/**
 * UC04.03 — phiếu kiểm kê và điều chỉnh tồn kho (BR04.03-1).
 *
 *  - tra cứu: Admin và Nhân viên kho (BR03.03-1);
 *  - lập / sửa / xác nhận: người được phép thao tác kho — User::canOperateWarehouse(), xem INVENTORY_ADMIN_CAN_OPERATE
 *    trong config/inventory.php (báo cáo: BR "chỉ Nhân viên kho" so với Bảng 3 "Admin làm được mọi nghiệp vụ của Nhân viên kho");
 *  - hủy: người lập phiếu hoặc Admin (BR03.04-5). Trạng thái draft do Service kiểm tra (409), không nằm ở Policy.
 */
class StockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::Staff);
    }

    public function view(User $user, StockAdjustment $adjustment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canOperateWarehouse();
    }

    public function update(User $user, StockAdjustment $adjustment): bool
    {
        return $user->canOperateWarehouse();
    }

    public function confirm(User $user, StockAdjustment $adjustment): bool
    {
        return $user->canOperateWarehouse();
    }

    public function cancel(User $user, StockAdjustment $adjustment): bool
    {
        return $user->isAdmin() || (int) $adjustment->created_by === (int) $user->getKey();
    }
}
