<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng activity_logs — Nhật ký hoạt động (audit log).
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.14) — Data Dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Người thực hiện (rỗng nếu đăng nhập sai email lạ)');
            $table->string('action', 50)->comment('Hành động: login_success, product_created, import_confirmed...');
            $table->string('subject_type', 50)->nullable()->comment('Đối tượng tác động (tên bảng)');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('Id đối tượng tác động');
            $table->string('description', 255)->nullable()->comment('Mô tả ngắn');
            $table->json('properties')->nullable()->comment('Giá trị cũ và mới');
            $table->string('ip_address', 45)->nullable()->comment('Địa chỉ IP');

            $table->foreign('user_id', 'fk_activity_user')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->timestamp('created_at')->useCurrent()->comment('Thời điểm ghi nhận (chỉ ghi thêm, không sửa)');

            $table->index('action', 'idx_activity_action');
            $table->index('created_at', 'idx_activity_created');
            $table->index(['subject_type', 'subject_id'], 'idx_activity_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
