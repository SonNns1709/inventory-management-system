<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/** Nhà cung cấp mẫu (UC02.02) — tên công ty và mã số thuế đều là giả định. Có một nhà cung cấp đã ngừng hợp tác để thử bộ lọc trạng thái. */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['Công ty TNHH Thương mại An Phát', 'Nguyễn Văn An', '0243111001', 'kinhdoanh@anphat.example', 'Số 12 Nguyễn Trãi, Thanh Xuân, Hà Nội', '0100000011', true],
            ['Công ty CP Phân phối Hòa Bình', 'Trần Thị Bình', '0243111002', 'sales@hoabinh.example', 'Số 45 Láng Hạ, Đống Đa, Hà Nội', '0100000022', true],
            ['Công ty TNHH Điện tử Minh Quân', 'Lê Minh Quân', '0283111003', 'lienhe@minhquan.example', '88 Điện Biên Phủ, Quận 3, TP. Hồ Chí Minh', '0300000033', true],
            ['Công ty TNHH Văn phòng phẩm Đại Nam', 'Phạm Thu Hà', '0243111004', 'dainam@vpp.example', 'Số 7 Kim Mã, Ba Đình, Hà Nội', '0100000044', true],
            ['Công ty TNHH Vận tải Sao Mai', 'Hoàng Văn Sao', '0243111005', null, 'Khu công nghiệp Quang Minh, Hà Nội', '0100000055', false],
        ];

        foreach ($suppliers as [$name, $contact, $phone, $email, $address, $taxCode, $active]) {
            Supplier::updateOrCreate(
                ['name' => $name],
                [
                    'contact_person' => $contact,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'tax_code' => $taxCode,
                    'is_active' => $active,
                ],
            );
        }
    }
}
