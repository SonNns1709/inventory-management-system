<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Concerns\Searchable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Tài khoản người dùng và vai trò (bảng `users`) — báo cáo Chương 3 §II.1.1.
 */
#[Fillable(['name', 'email', 'password', 'role', 'status', 'phone', 'must_change_password', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Searchable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /** Người dùng có một trong các vai trò truyền vào không? Ví dụ: $user->hasRole('admin', Role::Staff) */
    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $role = $role instanceof Role ? $role : Role::tryFrom($role);

            if ($role !== null && $this->role === $role) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === Role::Staff;
    }

    /** Tài khoản đang hoạt động (chưa bị khóa)? */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Được lập/sửa/xác nhận phiếu nhập, xuất, kiểm kê?
     *
     * Báo cáo Bảng 3 nói Admin "thực hiện được mọi nghiệp vụ của Nhân viên kho", nhưng BR03.01-1,
     * BR03.02-1, BR04.03-1 ghi "chỉ Nhân viên kho". Khung dự án cho phép cả hai và để nhóm chốt bằng
     * cấu hình: INVENTORY_ADMIN_CAN_OPERATE=false để chỉ Nhân viên kho được thao tác (config/inventory.php).
     */
    public function canOperateWarehouse(): bool
    {
        return $this->isStaff() || ($this->isAdmin() && (bool) config('inventory.admin_can_operate', true));
    }

    /** Phiếu nhập do tài khoản này lập (imports.created_by). */
    public function createdImports(): HasMany
    {
        return $this->hasMany(Import::class, 'created_by');
    }

    /** Phiếu xuất do tài khoản này lập (exports.created_by). */
    public function createdExports(): HasMany
    {
        return $this->hasMany(Export::class, 'created_by');
    }

    /** Phiếu kiểm kê do tài khoản này lập (stock_adjustments.created_by). */
    public function createdAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'created_by');
    }

    /** Nhật ký hoạt động của tài khoản (activity_logs.user_id). */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
