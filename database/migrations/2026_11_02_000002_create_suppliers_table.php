<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng suppliers — Nhà cung cấp.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.3) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique()->comment('Tên nhà cung cấp');
            $table->string('contact_person', 100)->nullable()->comment('Người liên hệ');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại');
            $table->string('email', 150)->nullable()->comment('Email');
            $table->string('address', 255)->nullable()->comment('Địa chỉ');
            $table->string('tax_code', 20)->nullable()->comment('Mã số thuế');
            $table->boolean('is_active')->default(true)->comment('1 = đang hợp tác, 0 = ngừng hợp tác');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
