<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng stock_adjustments — Phiếu kiểm kê và điều chỉnh tồn kho.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.10) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Mã phiếu (KKyyyymmdd-nnnn)');
            $table->unsignedBigInteger('warehouse_id')->comment('Kho được kiểm kê');
            $table->date('adjustment_date')->comment('Ngày kiểm kê');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft')->comment('Trạng thái phiếu');
            $table->string('note', 255)->nullable()->comment('Ghi chú');
            $table->unsignedBigInteger('created_by')->comment('Người lập phiếu');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('Người xác nhận');
            $table->timestamp('confirmed_at')->nullable()->comment('Thời điểm xác nhận');

            $table->foreign('warehouse_id', 'fk_adjustment_warehouse')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign('created_by', 'fk_adjustment_created_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('confirmed_by', 'fk_adjustment_confirmed_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index(['status', 'adjustment_date'], 'idx_adjustments_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
