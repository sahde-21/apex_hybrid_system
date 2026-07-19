<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['quotation_id', 'line_number']);
        });

        Schema::create('sale_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_line_id')->nullable()->constrained('quotation_lines')->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->decimal('quantity_invoiced', 18, 4)->default(0);
            $table->decimal('quantity_fulfilled', 18, 4)->default(0);
            $table->timestamps();

            $table->index(['sale_order_id', 'line_number']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_order_line_id')->nullable()->constrained('sale_order_lines')->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['invoice_id', 'line_number']);
        });

        Schema::create('sales_document_events', function (Blueprint $table) {
            $table->id();
            $table->morphs('document');
            $table->string('event');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->nullableMorphs('related');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 18, 2)->default(0)->after('status');
            $table->decimal('discount_amount', 18, 2)->default(0)->after('subtotal_amount');
            $table->decimal('tax_amount', 18, 2)->default(0)->after('discount_amount');
            $table->string('currency_code', 3)->default('IQD')->after('tax_amount');
            $table->text('terms')->nullable()->after('notes');
            $table->foreignId('converted_sale_order_id')->nullable()->after('terms')->constrained('sale_orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable()->after('converted_sale_order_id');
            $table->foreignId('salesperson_id')->nullable()->after('converted_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->foreignId('quotation_id')->nullable()->after('contact_id')->constrained('quotations')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('warehouse_id')->constrained('branches')->nullOnDelete();
            $table->foreignId('salesperson_id')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
            $table->decimal('subtotal_amount', 18, 2)->default(0)->after('status');
            $table->decimal('discount_amount', 18, 2)->default(0)->after('subtotal_amount');
            $table->decimal('tax_amount', 18, 2)->default(0)->after('discount_amount');
            $table->string('currency_code', 3)->default('IQD')->after('tax_amount');
            $table->string('billing_address')->nullable()->after('notes');
            $table->string('shipping_address')->nullable()->after('billing_address');
            $table->text('terms')->nullable()->after('shipping_address');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 18, 2)->default(0)->after('discount_amount');
            $table->string('currency_code', 3)->default('IQD')->after('paid_amount');
            $table->string('payment_terms')->nullable()->after('currency_code');
            $table->timestamp('issued_at')->nullable()->after('payment_terms');
            $table->timestamp('voided_at')->nullable()->after('issued_at');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('posted')->after('type');
            $table->string('account_label')->nullable()->after('payment_method');
            $table->timestamp('posted_at')->nullable()->after('notes');
            $table->foreignId('posted_by')->nullable()->after('posted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('posted_by');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->after('reversed_by')->constrained('payments')->nullOnDelete();
            $table->string('reversal_reason')->nullable()->after('reversal_of_id');
            $table->index(['status', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posted_by');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropConstrainedForeignId('reversal_of_id');
            $table->dropColumn([
                'status', 'account_label', 'posted_at', 'reversed_at', 'reversal_reason',
            ]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn([
                'paid_amount', 'currency_code', 'payment_terms', 'issued_at', 'voided_at', 'void_reason',
            ]);
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quotation_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('salesperson_id');
            $table->dropColumn([
                'subtotal_amount', 'discount_amount', 'tax_amount', 'currency_code',
                'billing_address', 'shipping_address', 'terms',
            ]);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_sale_order_id');
            $table->dropConstrainedForeignId('salesperson_id');
            $table->dropColumn([
                'subtotal_amount', 'discount_amount', 'tax_amount', 'currency_code',
                'terms', 'converted_at',
            ]);
        });

        Schema::dropIfExists('sales_document_events');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('sale_order_lines');
        Schema::dropIfExists('quotation_lines');
    }
};
