<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_demo_documents', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('definition_key')->default('demo-multi-level');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_demo_documents');
    }
};
