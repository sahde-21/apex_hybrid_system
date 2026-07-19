<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar_path');
            $table->timestamp('locked_at')->nullable()->after('is_active');
            $table->string('locked_reason')->nullable()->after('locked_at');
            $table->boolean('force_password_reset')->default(false)->after('locked_reason');
            $table->timestamp('last_login_at')->nullable()->after('force_password_reset');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->timestamp('last_activity_at')->nullable()->after('last_login_ip');
            $table->softDeletes();
        });

        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('event')->default('login'); // login, logout, failed, locked
            $table->boolean('successful')->default(true);
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'avatar_path',
                'is_active',
                'locked_at',
                'locked_reason',
                'force_password_reset',
                'last_login_at',
                'last_login_ip',
                'last_activity_at',
                'deleted_at',
            ]);
        });
    }
};
