<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng warehouses — Kho / địa điểm lưu trữ.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.4) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Mã kho, duy nhất');
            $table->string('name', 100)->comment('Tên kho');
            $table->string('address', 255)->nullable()->comment('Địa chỉ');
            $table->boolean('is_active')->default(true)->comment('1 = đang hoạt động, 0 = ngừng');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
