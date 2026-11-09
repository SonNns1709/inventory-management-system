<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng imports — Phiếu nhập kho.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.6) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Mã phiếu (PNyyyymmdd-nnnn)');
            $table->unsignedBigInteger('supplier_id')->comment('Tham chiếu suppliers.id');
            $table->unsignedBigInteger('warehouse_id')->comment('Kho nhận hàng, tham chiếu warehouses.id');
            $table->date('import_date')->comment('Ngày nhập');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft')->comment('Trạng thái phiếu');
            $table->decimal('total_amount', 18, 2)->default(0)->comment('Tổng tiền phiếu');
            $table->string('note', 255)->nullable()->comment('Ghi chú');
            $table->unsignedBigInteger('created_by')->comment('Người lập phiếu, tham chiếu users.id');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('Người xác nhận, tham chiếu users.id');
            $table->timestamp('confirmed_at')->nullable()->comment('Thời điểm xác nhận (ghi sổ)');

            $table->foreign('supplier_id', 'fk_import_supplier')
                ->references('id')
                ->on('suppliers')
                ->restrictOnDelete();
            $table->foreign('warehouse_id', 'fk_import_warehouse')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign('created_by', 'fk_import_created_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('confirmed_by', 'fk_import_confirmed_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index(['status', 'import_date'], 'idx_imports_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
