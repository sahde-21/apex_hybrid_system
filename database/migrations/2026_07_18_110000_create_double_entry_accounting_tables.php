<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 8)->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->date('rate_date');
            $table->decimal('rate', 18, 8);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['currency_id', 'rate_date']);
        });

        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->timestamps();

            $table->unique(['starts_on', 'ends_on']);
        });

        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('period_number');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'period_number']);
            $table->index(['starts_on', 'ends_on']);
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('type');
            $table->string('normal_balance');
            $table->string('currency_code', 3)->default('IQD');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('allow_manual_entry')->default(true);
            $table->string('system_key')->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['parent_id', 'code']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('fiscal_period_id')->nullable()->after('entry_date')->constrained('fiscal_periods')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('fiscal_period_id')->constrained('branches')->nullOnDelete();
            $table->string('currency_code', 3)->default('IQD')->after('branch_id');
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency_code');
            $table->nullableMorphs('reference');
            $table->string('idempotency_key')->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->index(['status', 'entry_date']);
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description')->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('currency_code', 3)->default('IQD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->decimal('base_debit', 18, 2)->default(0);
            $table->decimal('base_credit', 18, 2)->default(0);
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();

            $table->index(['account_id', 'journal_entry_id']);
            $table->index(['contact_id']);
        });

        Schema::create('accounting_postings', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('event');
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'event']);
        });

        Schema::create('accounting_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->nullableMorphs('subject');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_audit_logs');
        Schema::dropIfExists('accounting_postings');
        Schema::dropIfExists('journal_entry_lines');

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fiscal_period_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropMorphs('reference');
            $table->dropColumn([
                'currency_code',
                'exchange_rate',
                'idempotency_key',
                'posted_at',
                'reversed_at',
            ]);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('posted_by');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropConstrainedForeignId('reversal_of_id');
        });

        Schema::dropIfExists('accounts');
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
