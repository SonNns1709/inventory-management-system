<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng inventories — Tồn kho hiện tại theo sản phẩm và kho.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.12) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('Tham chiếu products.id');
            $table->unsignedBigInteger('warehouse_id')->comment('Tham chiếu warehouses.id');
            $table->unsignedInteger('quantity')->default(0)->comment('Số lượng tồn hiện tại (CHECK >= 0)');

            $table->foreign('product_id', 'fk_inventory_product')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();
            $table->foreign('warehouse_id', 'fk_inventory_warehouse')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id'], 'uq_inventories_product_warehouse');
        });

        // CHECK chỉ áp dụng trên MySQL 8 / MariaDB (SQLite dùng khi chạy test không hỗ trợ ADD CONSTRAINT).
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE inventories ADD CONSTRAINT chk_inventories_quantity CHECK (quantity >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
