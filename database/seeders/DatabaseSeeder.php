<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Nạp dữ liệu mẫu. Thứ tự quan trọng (giống demo1: tài khoản trước, rồi tới các bảng phụ thuộc):
     *   tài khoản -> danh mục -> nhà cung cấp -> kho -> sản phẩm -> tồn đầu kỳ (cần đủ 4 bảng trước đó).
     * Mọi seeder đều chạy lại được nhiều lần (updateOrCreate / kiểm tra đã nạp chưa).
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            WarehouseSeeder::class,
            ProductSeeder::class,
            InventorySeeder::class,
        ]);
    }
}
