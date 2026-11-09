<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Exceptions\BusinessConflictException;
use App\Http\Filters\SupplierFilter;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * UC02.02 — Quản lý nhà cung cấp (khuôn giống CategoryService, thêm "ngừng / kích hoạt hợp tác").
 * BR02.02-1 tên duy nhất (Form Request + UNIQUE), BR02.02-3/4 đã có phiếu nhập thì không xóa — chỉ ngừng hợp tác,
 * BR02.02-5 ngừng / kích hoạt chỉ đổi is_active, BR02.02-6 chỉ Admin (Policy), BR02.02-7 ghi Activity Log.
 */
class SupplierService
{
    private const FIELDS = ['name', 'contact_person', 'phone', 'email', 'address', 'tax_code'];

    public function __construct(private readonly ActivityLogService $logs) {}

    /** @param  array<string, mixed>  $params  keyword, is_active, sort, direction, per_page */
    public function list(array $params = [], ?int $perPage = null): LengthAwarePaginator
    {
        $filters = SupplierFilter::valid($params);

        return SupplierFilter::apply(Supplier::query(), $filters)->paginate($perPage ?? SupplierFilter::perPage($filters));
    }

    public function find(int|string $id): Supplier
    {
        return Supplier::query()->findOrFail($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Supplier
    {
        $supplier = Supplier::create($this->payload($data) + ['is_active' => true]);

        $this->logs->record(
            ActivityAction::SupplierCreated,
            $supplier,
            "Thêm nhà cung cấp \"{$supplier->name}\"",
            ['new' => $supplier->only(self::FIELDS)],
        );

        return $supplier;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $old = $supplier->only(self::FIELDS);

        $supplier->fill($this->payload($data))->save();

        $this->logs->record(
            ActivityAction::SupplierUpdated,
            $supplier,
            "Cập nhật nhà cung cấp \"{$supplier->name}\"",
            ['old' => $old, 'new' => $supplier->only(self::FIELDS)],
        );

        return $supplier;
    }

    /** BR02.02-3 / BR02.02-4: đã phát sinh phiếu nhập thì chỉ được ngừng hợp tác -> HTTP 409. */
    public function delete(Supplier $supplier): void
    {
        if ($supplier->imports()->exists()) {
            throw new BusinessConflictException('Nhà cung cấp đã phát sinh phiếu nhập, không thể xóa — hãy chuyển sang "Ngừng hợp tác"');
        }

        $old = $supplier->only(self::FIELDS);
        $supplier->delete();

        $this->logs->record(ActivityAction::SupplierDeleted, $supplier, "Xóa nhà cung cấp \"{$old['name']}\"", ['old' => $old]);
    }

    /** BR02.02-5: đổi is_active (đang hợp tác <-> ngừng hợp tác), giữ nguyên dữ liệu lịch sử. */
    public function toggle(Supplier $supplier): Supplier
    {
        $supplier->is_active = ! $supplier->is_active;
        $supplier->save();

        $this->logs->record(
            $supplier->is_active ? ActivityAction::SupplierActivated : ActivityAction::SupplierDeactivated,
            $supplier,
            ($supplier->is_active ? 'Kích hoạt hợp tác' : 'Ngừng hợp tác')." với nhà cung cấp \"{$supplier->name}\"",
        );

        return $supplier;
    }

    /**
     * PUT thay thế toàn bộ: trường tùy chọn không gửi lên được đặt về null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return array_merge(array_fill_keys(self::FIELDS, null), array_intersect_key($data, array_flip(self::FIELDS)));
    }
}
