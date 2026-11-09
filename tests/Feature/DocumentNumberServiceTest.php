<?php

namespace Tests\Feature;

use App\Models\DocumentSequence;
use App\Services\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Số chứng từ <tiền tố><yyyymmdd>-<nnnn> (bảng document_sequences, báo cáo Chương 3 §II.1.15).
 * TestCase cố định "bây giờ" là 10:00 ngày 02/11/2026 nên số sinh ra luôn có dạng PN20261102-nnnn.
 */
class DocumentNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentNumberService $numbers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->numbers = app(DocumentNumberService::class);
    }

    public function test_so_chung_tu_dau_tien_trong_ngay_la_0001(): void
    {
        $this->assertSame('PN20261102-0001', $this->numbers->next('import'));
    }

    public function test_so_tang_dan_trong_cung_ngay_va_cung_loai(): void
    {
        $this->assertSame('PN20261102-0001', $this->numbers->next('import'));
        $this->assertSame('PN20261102-0002', $this->numbers->next('import'));
        $this->assertSame('PN20261102-0003', $this->numbers->next('import'));
    }

    public function test_moi_loai_chung_tu_co_bo_dem_rieng(): void
    {
        $this->assertSame('PN20261102-0001', $this->numbers->next('import'));
        $this->assertSame('PX20261102-0001', $this->numbers->next('export'));
        $this->assertSame('KK20261102-0001', $this->numbers->next('adjustment'));
        $this->assertSame('PN20261102-0002', $this->numbers->next('import'));
    }

    public function test_sang_ngay_moi_bo_dem_ve_0001_va_ngay_cu_van_di_tiep(): void
    {
        $this->assertSame('PN20261102-0001', $this->numbers->next('import'));
        $this->assertSame('PN20261102-0002', $this->numbers->next('import'));

        $this->assertSame('PN20261103-0001', $this->numbers->next('import', Carbon::parse('2026-11-03')));
        // Mỗi cặp (tiền tố, ngày) một dòng trong document_sequences
        $this->assertSame(2, DocumentSequence::query()->where('prefix', 'PN')->count());

        // Quay lại ngày cũ vẫn tiếp tục từ số đã dùng
        $this->assertSame('PN20261102-0003', $this->numbers->next('import', Carbon::parse('2026-11-02')));
    }

    public function test_loai_chung_tu_khong_hop_le_nem_loi(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->numbers->next('invoice');
    }
}
