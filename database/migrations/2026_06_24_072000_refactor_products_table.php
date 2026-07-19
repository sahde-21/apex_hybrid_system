<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (! Schema::hasColumn('products', 'code')) {
            return;
        }

        Schema::create('products_new', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('minimum_stock_level')->default(0);
            $table->timestamps();
        });

        DB::table('products')->orderBy('id')->each(function ($product) {
            DB::table('products_new')->insert([
                'id' => $product->id,
                'name' => $product->name,
                'sku' => filled($product->sku) ? $product->sku : $product->code,
                'description' => null,
                'purchase_price' => $product->cost_price,
                'sale_price' => $product->selling_price,
                'stock_quantity' => $product->stock_level,
                'minimum_stock_level' => $product->alert_quantity,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ]);
        });

        Schema::drop('products');
        Schema::rename('products_new', 'products');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('products') || Schema::hasColumn('products', 'code')) {
            return;
        }

        Schema::create('products_old', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('sku')->nullable()->unique();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->unsignedInteger('stock_level')->default(0);
            $table->unsignedInteger('alert_quantity')->default(0);
            $table->string('type')->default('goods');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        DB::table('products')->orderBy('id')->each(function ($product) {
            DB::table('products_old')->insert([
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->sku,
                'sku' => $product->sku,
                'cost_price' => $product->purchase_price,
                'selling_price' => $product->sale_price,
                'stock_level' => $product->stock_quantity,
                'alert_quantity' => $product->minimum_stock_level,
                'type' => 'goods',
                'status' => 'active',
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ]);
        });

        Schema::drop('products');
        Schema::rename('products_old', 'products');
    }
};
