<?php

use App\Support\Database\SchemaIndexHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bills')) {
            return;
        }

        Schema::table('bills', function (Blueprint $table): void {
            $existing = SchemaIndexHelper::listing(
                Schema::getConnection()->getSchemaBuilder(),
                'bills',
            );

            if (! in_array('bills_contact_date_g2_idx', $existing, true)) {
                try {
                    $table->index(['contact_id', 'bill_date'], 'bills_contact_date_g2_idx');
                } catch (\Throwable) {
                    // Index may already exist under another name.
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bills')) {
            return;
        }

        Schema::table('bills', function (Blueprint $table): void {
            try {
                $table->dropIndex('bills_contact_date_g2_idx');
            } catch (\Throwable) {
                //
            }
        });
    }
};
