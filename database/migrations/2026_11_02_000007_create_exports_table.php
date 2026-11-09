<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng exports — Phiếu xuất kho (bán hàng hoặc chuyển kho).
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.8) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Mã phiếu (PXyyyymmdd-nnnn)');
            $table->unsignedBigInteger('warehouse_id')->comment('Kho xuất, tham chiếu warehouses.id');
            $table->enum('reason', ['sale', 'transfer'])->default('sale')->comment('Lý do xuất: bán hàng hoặc chuyển kho');
            $table->unsignedBigInteger('dest_warehouse_id')->nullable()->comment('Kho đích (bắt buộc khi reason = transfer)');
            $table->date('export_date')->comment('Ngày xuất');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft')->comment('Trạng thái phiếu');
            $table->decimal('total_cost', 18, 2)->default(0)->comment('Tổng giá vốn hàng xuất');
            $table->string('note', 255)->nullable()->comment('Ghi chú');
            $table->unsignedBigInteger('created_by')->comment('Người lập phiếu');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('Người xác nhận');
            $table->timestamp('confirmed_at')->nullable()->comment('Thời điểm xác nhận (ghi sổ)');

            $table->foreign('warehouse_id', 'fk_export_warehouse')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign('dest_warehouse_id', 'fk_export_dest_warehouse')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign('created_by', 'fk_export_created_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('confirmed_by', 'fk_export_confirmed_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index(['status', 'export_date'], 'idx_exports_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
