<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng document_sequences — Bộ đếm số chứng từ theo ngày (PN, PX, KK).
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.15) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 5)->comment('Tiền tố chứng từ: PN, PX, KK');
            $table->date('seq_date')->comment('Ngày phát sinh');
            $table->unsignedInteger('last_number')->default(0)->comment('Số thứ tự đã cấp gần nhất trong ngày');

            $table->unique(['prefix', 'seq_date'], 'uq_document_sequences_prefix_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
