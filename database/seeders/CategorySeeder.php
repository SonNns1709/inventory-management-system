<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/** Danh mục sản phẩm mẫu (UC02.01). Tên danh mục là duy nhất nên dùng làm khóa của updateOrCreate. */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Điện tử' => 'Thiết bị điện tử, phụ kiện máy tính',
            'Gia dụng' => 'Đồ dùng điện gia đình',
            'Văn phòng phẩm' => 'Giấy, bút, sổ và vật tư văn phòng',
            'Thực phẩm - Đồ uống' => 'Hàng tiêu dùng nhanh, đóng gói',
            'Dụng cụ - Thiết bị' => 'Dụng cụ cầm tay, thiết bị sửa chữa',
        ];

        foreach ($categories as $name => $description) {
            Category::updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
