<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variants')->restrictOnDelete();
            $table->integer('on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->decimal('average_cost', 18, 4)->nullable();
            $table->integer('minimum_level')->nullable();
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id']);
            $table->index(['product_id', 'variant_id']);
            $table->index(['warehouse_id', 'on_hand']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE stock_levels ADD CONSTRAINT stock_levels_reserved_non_negative CHECK (reserved >= 0)');
            DB::statement('ALTER TABLE stock_levels ADD CONSTRAINT stock_levels_on_hand_covers_reserved CHECK (on_hand >= reserved)');
            DB::statement('CREATE UNIQUE INDEX stock_levels_identity_unique ON stock_levels (warehouse_id, product_id, variant_id) NULLS NOT DISTINCT');
        } else {
            // SQLite (tests): expression unique approximates NULLS NOT DISTINCT (variant ids start at 1).
            DB::statement('CREATE UNIQUE INDEX stock_levels_identity_unique ON stock_levels (warehouse_id, product_id, COALESCE(variant_id, 0))');
        }

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variants')->restrictOnDelete();
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('reserved_delta')->default(0);
            $table->string('movement_type');
            $table->string('reason_code')->nullable();
            $table->timestamp('occurred_at');
            $table->nullableMorphs('reference');
            $table->unsignedBigInteger('reference_line_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['warehouse_id', 'product_id', 'variant_id', 'occurred_at'], 'stock_movements_identity_occurred_idx');
            $table->index(['movement_type', 'occurred_at']);
            $table->index('occurred_at');
        });

        // nullableMorphs already adds reference_type + reference_id index named reference_type_reference_id_index
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
    }
};
