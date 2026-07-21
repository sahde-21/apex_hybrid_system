<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->string('definition_key');
            $table->morphs('document');
            $table->string('current_status');
            $table->unsignedSmallInteger('current_approval_level')->default(0);
            $table->string('approval_mode')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['document_type', 'document_id']);
            $table->index(['definition_key', 'current_status']);
        });

        Schema::create('workflow_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedSmallInteger('approval_level')->nullable();
            $table->string('approval_level_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['workflow_instance_id', 'created_at']);
        });

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('action');
            $table->unsignedSmallInteger('level');
            $table->string('level_name');
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['workflow_instance_id', 'action', 'level'], 'workflow_approvals_unique_level');
            $table->index(['workflow_instance_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_histories');
        Schema::dropIfExists('workflow_instances');
    }
};
