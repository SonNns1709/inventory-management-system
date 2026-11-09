<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/** Kho mẫu (UC02.03). Mã kho là duy nhất nên dùng làm khóa của updateOrCreate. Kho Đà Nẵng ngừng hoạt động để thử trạng thái. */
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['KHO-HN', 'Kho Hà Nội', 'Số 55 Giải Phóng, Hai Bà Trưng, Hà Nội', true],
            ['KHO-HCM', 'Kho TP. Hồ Chí Minh', '120 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh', true],
            ['KHO-DN', 'Kho Đà Nẵng', '15 Nguyễn Hữu Thọ, Hải Châu, Đà Nẵng', false],
        ];

        foreach ($warehouses as [$code, $name, $address, $active]) {
            Warehouse::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'address' => $address, 'is_active' => $active],
            );
        }
    }
}
