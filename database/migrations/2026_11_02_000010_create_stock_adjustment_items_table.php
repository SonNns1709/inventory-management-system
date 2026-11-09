<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng stock_adjustment_items — Chi tiết phiếu kiểm kê.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.11) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adjustment_id')->comment('Tham chiếu stock_adjustments.id (CASCADE)');
            $table->unsignedBigInteger('product_id')->comment('Tham chiếu products.id');
            $table->unsignedInteger('system_qty')->comment('Tồn theo hệ thống lúc lập phiếu (snapshot)');
            $table->unsignedInteger('counted_qty')->nullable()->comment('Số lượng thực đếm');
            $table->integer('diff_qty')->nullable()->comment('Chênh lệch = counted_qty - system_qty');
            $table->enum('reason', ['loss', 'damaged', 'shrinkage', 'other'])->nullable()->comment('Lý do chênh lệch (bắt buộc nếu diff_qty <> 0)');
            $table->decimal('unit_cost', 15, 4)->nullable()->comment('Giá vốn bình quân lúc xác nhận');
            $table->decimal('diff_value', 18, 2)->nullable()->comment('Giá trị chênh lệch = diff_qty x unit_cost');
            $table->string('note', 255)->nullable()->comment('Ghi chú');

            $table->foreign('adjustment_id', 'fk_adjustment_item_adjustment')
                ->references('id')
                ->on('stock_adjustments')
                ->cascadeOnDelete();
            $table->foreign('product_id', 'fk_adjustment_item_product')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
