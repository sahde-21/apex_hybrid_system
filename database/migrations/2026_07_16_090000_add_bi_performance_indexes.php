<?php

use App\Support\Database\SchemaIndexHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('payrolls', ['pay_period_start'], 'payrolls_period_bi_idx');
        $this->addIndexIfMissing('payrolls', ['net_amount'], 'payrolls_net_bi_idx');
        $this->addIndexIfMissing('attendance', ['attendance_date'], 'attendance_date_bi_idx');
        $this->addIndexIfMissing('attendance', ['branch_id', 'attendance_date'], 'attendance_branch_date_bi_idx');
        $this->addIndexIfMissing('pos_sales', ['status', 'created_at'], 'pos_sales_status_created_bi_idx');
        $this->addIndexIfMissing('pos_sale_items', ['product_id'], 'pos_sale_items_product_bi_idx');
        $this->addIndexIfMissing('products', ['stock_quantity', 'minimum_stock_level'], 'products_stock_levels_bi_idx');
        $this->addIndexIfMissing('products', ['category_id'], 'products_category_bi_idx');
        $this->addIndexIfMissing('invoices', ['contact_id', 'invoice_date'], 'invoices_contact_date_bi_idx');
        $this->addIndexIfMissing('purchase_orders', ['order_date'], 'purchase_orders_date_bi_idx');
        $this->addIndexIfMissing('purchase_orders', ['contact_id', 'order_date'], 'purchase_orders_contact_date_bi_idx');
        $this->addIndexIfMissing('sale_orders', ['contact_id', 'order_date'], 'sale_orders_contact_date_bi_idx');
        $this->addIndexIfMissing('bills', ['bill_date'], 'bills_date_bi_idx');
        $this->addIndexIfMissing('quality_controls', ['status'], 'quality_controls_status_bi_idx');
    }

    public function down(): void
    {
        $drops = [
            'payrolls' => ['payrolls_period_bi_idx', 'payrolls_net_bi_idx'],
            'attendance' => ['attendance_date_bi_idx', 'attendance_branch_date_bi_idx'],
            'pos_sales' => ['pos_sales_status_created_bi_idx'],
            'pos_sale_items' => ['pos_sale_items_product_bi_idx'],
            'products' => ['products_stock_levels_bi_idx', 'products_category_bi_idx'],
            'invoices' => ['invoices_contact_date_bi_idx'],
            'purchase_orders' => ['purchase_orders_date_bi_idx', 'purchase_orders_contact_date_bi_idx'],
            'sale_orders' => ['sale_orders_contact_date_bi_idx'],
            'bills' => ['bills_date_bi_idx'],
            'quality_controls' => ['quality_controls_status_bi_idx'],
        ];

        foreach ($drops as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($indexes): void {
                foreach ($indexes as $index) {
                    try {
                        $blueprint->dropIndex($index);
                    } catch (Throwable) {
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
            $existing = SchemaIndexHelper::listing(
                Schema::getConnection()->getSchemaBuilder(),
                $blueprint->getTable(),
            );

            if (in_array($indexName, $existing, true)) {
                return;
            }

            try {
                $blueprint->index($columns, $indexName);
            } catch (Throwable) {
                // Index may already exist under another name.
            }
        });
    }
};
