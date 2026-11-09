<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng export_items — Chi tiết phiếu xuất.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.9) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('export_id')->comment('Tham chiếu exports.id (CASCADE)');
            $table->unsignedBigInteger('product_id')->comment('Tham chiếu products.id');
            $table->unsignedInteger('quantity')->comment('Số lượng xuất (CHECK > 0)');
            $table->decimal('unit_cost', 15, 4)->nullable()->comment('Giá vốn bình quân tại thời điểm xác nhận');
            $table->decimal('line_total', 18, 2)->nullable()->comment('Giá trị = quantity x unit_cost');

            $table->foreign('export_id', 'fk_export_item_export')
                ->references('id')
                ->on('exports')
                ->cascadeOnDelete();
            $table->foreign('product_id', 'fk_export_item_product')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->timestamps();
        });

        // CHECK chỉ áp dụng trên MySQL 8 / MariaDB (SQLite dùng khi chạy test không hỗ trợ ADD CONSTRAINT).
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE export_items ADD CONSTRAINT chk_export_items_quantity CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('export_items');
    }
};
