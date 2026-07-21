<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intelligence_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);
            $table->string('status', 32)->default('running');
            $table->unsignedInteger('records_generated')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'started_at']);
        });

        Schema::create('intelligence_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);
            $table->string('category', 64)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->json('payload');
            $table->json('meta')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'generated_at']);
            $table->index(['expires_at']);
        });

        Schema::create('intelligence_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('rule_key', 128);
            $table->string('category', 64);
            $table->string('severity', 32);
            $table->string('status', 32)->default('active');
            $table->string('title');
            $table->text('summary');
            $table->text('explanation')->nullable();
            $table->json('metrics')->nullable();
            $table->json('source_references')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['category', 'detected_at']);
            $table->index(['rule_key', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('intelligence_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('rule_key', 128);
            $table->string('category', 64);
            $table->string('severity', 32);
            $table->string('priority', 32)->default('medium');
            $table->string('status', 32)->default('active');
            $table->string('title');
            $table->text('description');
            $table->text('reason')->nullable();
            $table->text('suggested_action')->nullable();
            $table->string('action_route')->nullable();
            $table->json('metrics')->nullable();
            $table->json('source_references')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['category', 'generated_at']);
            $table->index(['rule_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intelligence_recommendations');
        Schema::dropIfExists('intelligence_alerts');
        Schema::dropIfExists('intelligence_snapshots');
        Schema::dropIfExists('intelligence_runs');
    }
};
