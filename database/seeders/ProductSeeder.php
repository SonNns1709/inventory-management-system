<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Sản phẩm mẫu (UC02.04 – UC02.07). SKU là duy nhất nên dùng làm khóa của updateOrCreate.
 *
 * `avg_cost` thường do hệ thống tự cập nhật khi xác nhận phiếu nhập (WAC); ở dữ liệu mẫu được đặt sẵn
 * để Dashboard có "Giá trị tồn kho" ngay sau khi nạp (xem InventorySeeder — tồn đầu kỳ nhập theo đúng giá vốn này).
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // [SKU, tên, danh mục, đơn vị, giá bán, giá vốn, ngưỡng cảnh báo, đang kinh doanh]
        $products = [
            ['DT-0001', 'Chuột không dây', 'Điện tử', 'cái', 250000, 180000, 10, true],
            ['DT-0002', 'Bàn phím cơ', 'Điện tử', 'cái', 890000, 650000, 5, true],
            ['DT-0003', 'Cáp sạc USB-C 1m', 'Điện tử', 'cái', 120000, 70000, 30, true],
            ['DT-0004', 'Tai nghe chụp tai', 'Điện tử', 'cái', 550000, 380000, 8, true],
            ['DT-0099', 'Ổ cứng HDD 500GB (ngừng kinh doanh)', 'Điện tử', 'cái', 700000, 520000, 5, false],
            ['GD-0001', 'Ấm đun siêu tốc 1.8L', 'Gia dụng', 'cái', 320000, 230000, 6, true],
            ['GD-0002', 'Nồi cơm điện 1.2L', 'Gia dụng', 'cái', 750000, 540000, 4, true],
            ['VP-0001', 'Giấy in A4 (ream 500 tờ)', 'Văn phòng phẩm', 'ream', 85000, 65000, 50, true],
            ['VP-0002', 'Bút bi xanh (hộp 20 cây)', 'Văn phòng phẩm', 'hộp', 60000, 42000, 20, true],
            ['VP-0003', 'Sổ tay bìa cứng A5', 'Văn phòng phẩm', 'cuốn', 35000, 22000, 25, true],
            ['TP-0001', 'Nước khoáng 500ml (thùng 24 chai)', 'Thực phẩm - Đồ uống', 'thùng', 110000, 85000, 15, true],
            ['TP-0002', 'Mì ăn liền (thùng 30 gói)', 'Thực phẩm - Đồ uống', 'thùng', 135000, 105000, 12, true],
            ['DC-0001', 'Bộ tua vít 24 chi tiết', 'Dụng cụ - Thiết bị', 'bộ', 180000, 120000, 8, true],
        ];

        $categoryIds = Category::query()->pluck('id', 'name');

        foreach ($products as [$sku, $name, $category, $unit, $price, $avgCost, $minStock, $active]) {
            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'category_id' => $categoryIds[$category],
                    'unit' => $unit,
                    'price' => $price,
                    'avg_cost' => $avgCost,
                    'min_stock_level' => $minStock,
                    'is_active' => $active,
                ],
            );
        }
    }
}
