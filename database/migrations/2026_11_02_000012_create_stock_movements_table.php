<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng stock_movements — Sổ biến động tồn kho (thẻ kho), chỉ ghi thêm, không sửa.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.13) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('Tham chiếu products.id');
            $table->unsignedBigInteger('warehouse_id')->comment('Tham chiếu warehouses.id');
            $table->enum('type', ['import', 'export', 'transfer_in', 'transfer_out', 'adjustment'])->comment('Loại biến động');
            $table->string('reference_type', 30)->comment('Bảng chứng từ gốc: imports, exports, stock_adjustments');
            $table->unsignedBigInteger('reference_id')->comment('Id chứng từ gốc');
            $table->integer('qty_change')->comment('Số lượng thay đổi (+ nhập, - xuất)');
            $table->unsignedInteger('balance_after')->comment('Tồn của kho sau biến động');
            $table->decimal('unit_cost', 15, 4)->comment('Đơn giá của biến động (nhập: giá nhập, còn lại: giá vốn bình quân)');
            $table->decimal('avg_cost_after', 15, 4)->comment('Giá vốn bình quân của sản phẩm sau biến động');
            $table->unsignedBigInteger('created_by')->comment('Người thực hiện');

            $table->foreign('product_id', 'fk_movement_product')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();
            $table->foreign('warehouse_id', 'fk_movement_warehouse')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign('created_by', 'fk_movement_created_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->timestamp('created_at')->useCurrent()->comment('Thời điểm ghi nhận (chỉ ghi thêm, không sửa)');

            $table->index(['product_id', 'warehouse_id', 'created_at'], 'idx_movements_card');
            $table->index(['reference_type', 'reference_id'], 'idx_movements_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
