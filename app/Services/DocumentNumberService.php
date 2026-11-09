<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Sinh số chứng từ: <tiền tố><yyyymmdd>-<nnnn>, ví dụ PN20261104-0001 (báo cáo Chương 3 §II.1.15, bảng document_sequences).
 * Tiền tố theo loại: import = PN (phiếu nhập), export = PX (phiếu xuất), adjustment = KK (phiếu kiểm kê) — xem config/inventory.php.
 *
 * Bộ đếm được đặt lại mỗi ngày (mỗi cặp tiền tố + ngày một dòng). Phải gọi bên trong DB::transaction() của nghiệp vụ lập phiếu
 * để khóa dòng bộ đếm (lockForUpdate) — hai người lập phiếu cùng lúc không bao giờ nhận trùng số.
 */
class DocumentNumberService
{
    /**
     * @param  string  $type  import | export | adjustment
     */
    public function next(string $type, ?CarbonInterface $date = null): string
    {
        $prefix = config("inventory.documents.prefixes.{$type}");

        if (! is_string($prefix) || $prefix === '') {
            throw new InvalidArgumentException("Loại chứng từ không hợp lệ: {$type}");
        }

        $date ??= now();
        $day = $date->toDateString();

        $sequence = DocumentSequence::query()
            ->where('prefix', $prefix)
            ->whereDate('seq_date', $day)
            ->lockForUpdate()
            ->first()
            ?? DocumentSequence::create(['prefix' => $prefix, 'seq_date' => $day, 'last_number' => 0]);

        $sequence->increment('last_number');

        return $prefix.$date->format('Ymd').'-'.str_pad(
            (string) $sequence->last_number,
            (int) config('inventory.documents.sequence_digits', 4),
            '0',
            STR_PAD_LEFT,
        );
    }
}
