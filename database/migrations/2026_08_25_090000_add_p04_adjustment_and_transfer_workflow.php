<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('notes');
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('variants')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->after('posted_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('cancelled_by');
            $table->timestamp('posted_at')->nullable()->after('approved_at');
            $table->timestamp('cancelled_at')->nullable()->after('posted_at');

            $table->index('status');
            $table->index(['warehouse_id', 'product_id', 'variant_id'], 'inventory_adjustments_identity_idx');
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('variants')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->foreignId('shipped_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->after('shipped_by')->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->after('received_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('cancelled_by');
            $table->timestamp('shipped_at')->nullable()->after('approved_at');
            $table->timestamp('received_at')->nullable()->after('shipped_at');
            $table->timestamp('cancelled_at')->nullable()->after('received_at');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropIndex('inventory_adjustments_identity_idx');
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('variant_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('posted_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['status', 'approved_at', 'posted_at', 'cancelled_at']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('shipped_by');
            $table->dropConstrainedForeignId('received_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['approved_at', 'shipped_at', 'received_at', 'cancelled_at']);
        });
    }
};
