<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('department')->nullable();
            $table->date('request_date');
            $table->date('needed_by')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('subtotal_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('currency_code', 3)->default('IQD');
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('converted_rfq_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'request_date']);
        });

        Schema::create('purchase_request_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['purchase_request_id', 'line_number']);
        });

        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
            $table->date('rfq_date');
            $table->date('valid_until')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('subtotal_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('currency_code', 3)->default('IQD');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('selected_vendor_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('converted_purchase_order_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'rfq_date']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreign('converted_rfq_id')->references('id')->on('rfqs')->nullOnDelete();
        });

        Schema::create('rfq_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_request_line_id')->nullable()->constrained('purchase_request_lines')->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['rfq_id', 'line_number']);
        });

        Schema::create('rfq_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('status')->default('invited');
            $table->decimal('quoted_total', 18, 2)->nullable();
            $table->decimal('quoted_tax', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->boolean('is_selected')->default(false);
            $table->timestamps();

            $table->unique(['rfq_id', 'contact_id']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rfq_line_id')->nullable()->constrained('rfq_lines')->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->decimal('quantity_billed', 18, 4)->default(0);
            $table->timestamps();

            $table->index(['purchase_order_id', 'line_number']);
        });

        Schema::create('bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('description');
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['bill_id', 'line_number']);
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->foreign('converted_purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('rfq_id')->nullable()->after('contact_id')->constrained('rfqs')->nullOnDelete();
            $table->foreignId('purchase_request_id')->nullable()->after('rfq_id')->constrained('purchase_requests')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('warehouse_id')->constrained('branches')->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
            $table->decimal('subtotal_amount', 18, 2)->default(0)->after('status');
            $table->decimal('discount_amount', 18, 2)->default(0)->after('subtotal_amount');
            $table->decimal('tax_amount', 18, 2)->default(0)->after('discount_amount');
            $table->string('currency_code', 3)->default('IQD')->after('tax_amount');
            $table->text('terms')->nullable()->after('notes');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('contact_id')->constrained('purchase_orders')->nullOnDelete();
            $table->decimal('subtotal_amount', 18, 2)->default(0)->after('status');
            $table->decimal('discount_amount', 18, 2)->default(0)->after('subtotal_amount');
            $table->decimal('paid_amount', 18, 2)->default(0)->after('tax_amount');
            $table->string('currency_code', 3)->default('IQD')->after('paid_amount');
            $table->timestamp('issued_at')->nullable()->after('currency_code');
            $table->timestamp('voided_at')->nullable()->after('issued_at');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bill_id')->nullable()->after('invoice_id')->constrained('bills')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bill_id');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_id');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn([
                'subtotal_amount', 'discount_amount', 'paid_amount', 'currency_code',
                'issued_at', 'voided_at', 'void_reason',
            ]);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rfq_id');
            $table->dropConstrainedForeignId('purchase_request_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('buyer_id');
            $table->dropColumn([
                'subtotal_amount', 'discount_amount', 'tax_amount', 'currency_code', 'terms',
            ]);
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_purchase_order_id');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_rfq_id');
        });

        Schema::dropIfExists('bill_lines');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('rfq_vendors');
        Schema::dropIfExists('rfq_lines');
        Schema::dropIfExists('rfqs');
        Schema::dropIfExists('purchase_request_lines');
        Schema::dropIfExists('purchase_requests');
    }
};
