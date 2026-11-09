<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng products — Sản phẩm (mặt hàng quản lý theo SKU).
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.5) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 30)->unique()->comment('Mã SKU, duy nhất');
            $table->string('name', 200)->comment('Tên sản phẩm');
            $table->unsignedBigInteger('category_id')->comment('Tham chiếu categories.id (RESTRICT)');
            $table->string('unit', 20)->comment('Đơn vị tính (cái, hộp, thùng...)');
            $table->decimal('price', 15, 2)->default(0)->comment('Giá bán tham khảo');
            $table->decimal('avg_cost', 15, 4)->default(0)->comment('Giá vốn bình quân gia quyền, hệ thống tự cập nhật');
            $table->unsignedInteger('min_stock_level')->default(0)->comment('Ngưỡng cảnh báo tồn kho riêng của sản phẩm');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->boolean('is_active')->default(true)->comment('1 = đang kinh doanh, 0 = ngừng kinh doanh');

            $table->foreign('category_id', 'fk_products_category')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
