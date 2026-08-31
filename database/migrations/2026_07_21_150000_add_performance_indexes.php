<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->indexIfMissing('quotations', function (Blueprint $table): void {
            $table->index(['status', 'quotation_date'], 'quotations_status_date_idx');
            $table->index('created_at', 'quotations_created_at_idx');
        });

        $this->indexIfMissing('sale_orders', function (Blueprint $table): void {
            $table->index(['status', 'order_date'], 'sale_orders_status_date_idx');
            $table->index('created_at', 'sale_orders_created_at_idx');
        });

        $this->indexIfMissing('invoices', function (Blueprint $table): void {
            $table->index(['status', 'invoice_date'], 'invoices_status_date_idx');
            $table->index('due_date', 'invoices_due_date_idx');
            $table->index('created_at', 'invoices_created_at_idx');
        });

        $this->indexIfMissing('contacts', function (Blueprint $table): void {
            $table->index(['type', 'name'], 'contacts_type_name_idx');
            $table->index('email', 'contacts_email_idx');
        });

        $this->indexIfMissing('products', function (Blueprint $table): void {
            $table->index('name', 'products_name_idx');
        });

        $this->indexIfMissing('audit_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_idx');
        });

        $this->indexIfMissing('payments', function (Blueprint $table): void {
            $table->index('contact_id', 'payments_contact_id_idx');
            $table->index('invoice_id', 'payments_invoice_id_idx');
            $table->index('bill_id', 'payments_bill_id_idx');
        });

        $this->indexIfMissing('sales_document_events', function (Blueprint $table): void {
            $table->index(['document_type', 'document_id', 'created_at'], 'sales_doc_events_timeline_idx');
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('quotations', 'quotations_status_date_idx');
        $this->dropIndexIfExists('quotations', 'quotations_created_at_idx');
        $this->dropIndexIfExists('sale_orders', 'sale_orders_status_date_idx');
        $this->dropIndexIfExists('sale_orders', 'sale_orders_created_at_idx');
        $this->dropIndexIfExists('invoices', 'invoices_status_date_idx');
        $this->dropIndexIfExists('invoices', 'invoices_due_date_idx');
        $this->dropIndexIfExists('invoices', 'invoices_created_at_idx');
        $this->dropIndexIfExists('contacts', 'contacts_type_name_idx');
        $this->dropIndexIfExists('contacts', 'contacts_email_idx');
        $this->dropIndexIfExists('products', 'products_name_idx');
        $this->dropIndexIfExists('audit_logs', 'audit_logs_user_created_idx');
        $this->dropIndexIfExists('payments', 'payments_contact_id_idx');
        $this->dropIndexIfExists('payments', 'payments_invoice_id_idx');
        $this->dropIndexIfExists('payments', 'payments_bill_id_idx');
        $this->dropIndexIfExists('sales_document_events', 'sales_doc_events_timeline_idx');
    }

    /**
     * @param  \Closure(Blueprint): void  $callback
     */
    private function indexIfMissing(string $table, \Closure $callback): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, $callback);
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }
};
