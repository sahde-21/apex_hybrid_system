<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->nullOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->nullOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->string('unit')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('product_id');
            $table->index('component_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_of_materials');
    }
};
