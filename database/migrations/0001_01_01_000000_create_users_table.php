<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng users — Tài khoản người dùng và vai trò.
 * Báo cáo Nhóm 11, Chương 3 §II.1 (mục 3.II.1.1) — Data Dictionary.
 *
 * Kèm bảng `sessions` của Laravel (SESSION_DRIVER=database). Đây là bảng hạ tầng của framework,
 * cùng nhóm với cache, jobs, personal_access_tokens — không tính vào 15 bảng nghiệp vụ của báo cáo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Họ và tên');
            $table->string('email', 150)->unique()->comment('Email đăng nhập, duy nhất');
            $table->string('password', 255)->comment('Mật khẩu đã băm (bcrypt)');
            $table->enum('role', ['admin', 'staff'])->default('staff')->comment('Vai trò: Admin hoặc Nhân viên kho');
            $table->enum('status', ['active', 'locked'])->default('active')->comment('Trạng thái tài khoản');
            $table->string('phone', 20)->nullable()->comment('Số điện thoại');
            $table->boolean('must_change_password')->default(false)->comment('Buộc đổi mật khẩu ở lần đăng nhập sau');
            $table->timestamp('last_login_at')->nullable()->comment('Lần đăng nhập gần nhất');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
