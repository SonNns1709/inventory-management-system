<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng import_items — Chi tiết phiếu nhập.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.7) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id')->comment('Tham chiếu imports.id (CASCADE)');
            $table->unsignedBigInteger('product_id')->comment('Tham chiếu products.id');
            $table->unsignedInteger('quantity')->comment('Số lượng nhập (CHECK > 0)');
            $table->decimal('unit_cost', 15, 4)->comment('Đơn giá nhập');
            $table->decimal('line_total', 18, 2)->comment('Thành tiền = quantity x unit_cost');

            $table->foreign('import_id', 'fk_import_item_import')
                ->references('id')
                ->on('imports')
                ->cascadeOnDelete();
            $table->foreign('product_id', 'fk_import_item_product')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->timestamps();
        });

        // CHECK chỉ áp dụng trên MySQL 8 / MariaDB (SQLite dùng khi chạy test không hỗ trợ ADD CONSTRAINT).
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE import_items ADD CONSTRAINT chk_import_items_quantity CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_items');
    }
};
