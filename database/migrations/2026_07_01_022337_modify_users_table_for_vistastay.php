<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('uuid', 36)->unique()->after('id');
            $table->string('phone')->nullable()->after('email_verified_at');
            $table->enum('role', ['customer', 'provider', 'admin'])->default('customer')->after('password');
            $table->enum('status', ['active', 'pending', 'suspended'])->default('active')->after('role');
            $table->string('avatar_url')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('avatar_url');
            $table->string('provider')->nullable()->after('last_login_at');
            $table->string('provider_id')->nullable()->after('provider');
            $table->softDeletes();

            $table->index(['role', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
            
            $table->dropSoftDeletes();
            $table->dropColumn([
                'uuid',
                'phone',
                'role',
                'status',
                'avatar_url',
                'last_login_at',
                'provider',
                'provider_id',
            ]);
        });
    }
};
