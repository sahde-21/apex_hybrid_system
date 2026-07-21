<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('type', 40);
            $table->string('event_key', 80)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('visibility', 20)->default('public'); // public|internal
            $table->json('metadata')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->nullableMorphs('related');
            $table->foreignId('parent_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignId('managed_document_id')->nullable()->constrained('managed_documents')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_type', 'subject_id', 'created_at'], 'activities_subject_timeline_idx');
            $table->index(['type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['visibility', 'created_at']);
        });

        Schema::create('activity_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_mentions');
        Schema::dropIfExists('activities');
    }
};
