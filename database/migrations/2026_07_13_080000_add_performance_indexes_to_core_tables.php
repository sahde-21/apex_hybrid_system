<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('products', ['sku'], 'products_sku_perf_idx');
        $this->addIndexIfMissing('products', ['name'], 'products_name_perf_idx');
        $this->addIndexIfMissing('contacts', ['type'], 'contacts_type_perf_idx');
        $this->addIndexIfMissing('contacts', ['name'], 'contacts_name_perf_idx');
        $this->addIndexIfMissing('invoices', ['status'], 'invoices_status_perf_idx');
        $this->addIndexIfMissing('invoices', ['invoice_date'], 'invoices_date_perf_idx');
        $this->addIndexIfMissing('sale_orders', ['status'], 'sale_orders_status_perf_idx');
        $this->addIndexIfMissing('sale_orders', ['order_date'], 'sale_orders_date_perf_idx');
        $this->addIndexIfMissing('purchase_orders', ['status'], 'purchase_orders_status_perf_idx');
        $this->addIndexIfMissing('payments', ['payment_date'], 'payments_date_perf_idx');
        $this->addIndexIfMissing('payments', ['type'], 'payments_type_perf_idx');
        $this->addIndexIfMissing('expenses', ['expense_date'], 'expenses_date_perf_idx');
        $this->addIndexIfMissing('expenses', ['category'], 'expenses_category_perf_idx');
        $this->addIndexIfMissing('bills', ['status'], 'bills_status_perf_idx');
        $this->addIndexIfMissing('quotations', ['status'], 'quotations_status_perf_idx');
        $this->addIndexIfMissing('employees', ['is_active'], 'employees_active_perf_idx');
        $this->addIndexIfMissing('tickets', ['status'], 'tickets_status_perf_idx');
        $this->addIndexIfMissing('leads', ['status'], 'leads_status_perf_idx');
        $this->addIndexIfMissing('stock_transfers', ['status'], 'stock_transfers_status_perf_idx');
        $this->addIndexIfMissing('production_orders', ['status'], 'production_orders_status_perf_idx');
    }

    public function down(): void
    {
        $drops = [
            'products' => ['products_sku_perf_idx', 'products_name_perf_idx'],
            'contacts' => ['contacts_type_perf_idx', 'contacts_name_perf_idx'],
            'invoices' => ['invoices_status_perf_idx', 'invoices_date_perf_idx'],
            'sale_orders' => ['sale_orders_status_perf_idx', 'sale_orders_date_perf_idx'],
            'purchase_orders' => ['purchase_orders_status_perf_idx'],
            'payments' => ['payments_date_perf_idx', 'payments_type_perf_idx'],
            'expenses' => ['expenses_date_perf_idx', 'expenses_category_perf_idx'],
            'bills' => ['bills_status_perf_idx'],
            'quotations' => ['quotations_status_perf_idx'],
            'employees' => ['employees_active_perf_idx'],
            'tickets' => ['tickets_status_perf_idx'],
            'leads' => ['leads_status_perf_idx'],
            'stock_transfers' => ['stock_transfers_status_perf_idx'],
            'production_orders' => ['production_orders_status_perf_idx'],
        ];

        foreach ($drops as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($indexes): void {
                foreach ($indexes as $index) {
                    try {
                        $blueprint->dropIndex($index);
                    } catch (\Throwable) {
                        // Ignore missing indexes on rollback.
                    }
                }
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    protected function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $sm = Schema::getConnection()->getSchemaBuilder();
            $existing = method_exists($sm, 'getIndexListing')
                ? $sm->getIndexListing($blueprint->getTable())
                : [];

            if (in_array($indexName, $existing, true)) {
                return;
            }

            try {
                $blueprint->index($columns, $indexName);
            } catch (\Throwable) {
                // Index may already exist under another name.
            }
        });
    }
};
