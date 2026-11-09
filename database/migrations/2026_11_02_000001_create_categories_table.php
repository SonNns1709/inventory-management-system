<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng categories — Danh mục (nhóm) sản phẩm.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.2) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('Tên danh mục, duy nhất');
            $table->string('description', 255)->nullable()->comment('Mô tả');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
