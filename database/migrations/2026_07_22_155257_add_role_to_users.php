<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 18 · Bug 4：后台用户管理 + 角色
     *  - role: admin / editor / user (3 档)
     *  - is_admin: 保留做 boolean 兼容旧代码
     *  - last_login_at / last_login_ip: admin 看活跃度
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->index()->after('is_admin');
            $table->timestamp('last_login_at')->nullable()->after('preferences');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'last_login_at', 'last_login_ip']);
        });
    }
};
