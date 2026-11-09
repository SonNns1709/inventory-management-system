<?php

namespace Tests\Unit;

use App\Services\CostingService;
use Tests\TestCase;

/**
 * Giá vốn bình quân gia quyền (WAC) — UC03.01 (BR03.01-5), BR03.02-5/6.
 *
 * Đây là hàm thuần túy, không đụng CSDL; test kế thừa Tests\TestCase chỉ để dùng helper config().
 * Các ca đều có kết quả rõ ràng (không nằm đúng ranh giới làm tròn) nên chạy giống nhau dù PHP có ext-bcmath hay không.
 */
class CostingServiceTest extends TestCase
{
    private CostingService $costing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->costing = new CostingService;
    }

    public function test_nhap_lan_dau_gia_von_bang_don_gia_nhap(): void
    {
        // Tồn 0 -> (0 × 0 + 10 × 50 000) / 10
        $this->assertSame('50000.0000', $this->costing->weightedAverage(0, '0.0000', 10, '50000'));
    }

    public function test_gia_von_binh_quan_gia_quyen_ket_hop_ton_cu_va_lo_moi(): void
    {
        // (100 × 10 000 + 50 × 13 000) / 150 = 11 000
        $this->assertSame('11000.0000', $this->costing->weightedAverage(100, '10000.0000', 50, '13000'));
    }

    public function test_nhap_cung_gia_von_thi_gia_von_khong_doi(): void
    {
        $this->assertSame('12500.0000', $this->costing->weightedAverage(40, '12500.0000', 60, '12500'));
    }

    public function test_ket_qua_lam_tron_den_4_chu_so_thap_phan(): void
    {
        // (1 × 10 + 2 × 11) / 3 = 10,666666… -> 10,6667
        $this->assertSame('10.6667', $this->costing->weightedAverage(1, '10', 2, '11'));
        // (2 × 10 + 1 × 11) / 3 = 10,333333… -> 10,3333
        $this->assertSame('10.3333', $this->costing->weightedAverage(2, '10', 1, '11'));
    }

    public function test_khong_co_hang_de_tinh_thi_giu_nguyen_gia_von_cu(): void
    {
        // Tồn 0 và số lượng nhập 0: không chia cho 0, giữ giá vốn cũ
        $this->assertSame('12.5000', $this->costing->weightedAverage(0, '12.5', 0, '99'));
    }

    public function test_chuan_hoa_gia_ve_4_chu_so_thap_phan(): void
    {
        $this->assertSame('7.0000', $this->costing->cost(7));
        $this->assertSame('1234.5000', $this->costing->cost('1234.5'));
    }
}
