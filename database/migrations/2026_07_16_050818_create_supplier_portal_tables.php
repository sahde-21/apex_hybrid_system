<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('locale', 10)->default('en');
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('portal_supplier_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('portal_supplier_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_supplier_id')->constrained('portal_suppliers')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['portal_supplier_id', 'read_at']);
        });

        Schema::create('supplier_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'status']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplier_response')->nullable()->after('status');
            $table->text('supplier_comment')->nullable()->after('supplier_response');
            $table->timestamp('supplier_responded_at')->nullable()->after('supplier_comment');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['supplier_response', 'supplier_comment', 'supplier_responded_at']);
        });

        Schema::dropIfExists('supplier_shipments');
        Schema::dropIfExists('portal_supplier_notifications');
        Schema::dropIfExists('portal_supplier_password_reset_tokens');
        Schema::dropIfExists('portal_suppliers');
    }
};
