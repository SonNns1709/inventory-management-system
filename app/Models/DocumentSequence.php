<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bộ đếm số chứng từ theo ngày (PN, PX, KK) (bảng `document_sequences`).
 *
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.15).
 */
class DocumentSequence extends Model
{
    public $timestamps = false;

    protected $table = 'document_sequences';

    protected $fillable = [
        'prefix',
        'seq_date',
        'last_number',
    ];

    /**
     * Ép kiểu khi đọc từ CSDL (tiền/giá vốn giữ dạng chuỗi decimal để không mất độ chính xác).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seq_date' => 'date',
            'last_number' => 'integer',
        ];
    }
}
