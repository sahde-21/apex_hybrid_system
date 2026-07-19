<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Invoices = 'invoices';
    case PurchaseOrders = 'purchase_orders';
    case SalesOrders = 'sales_orders';
    case Bills = 'bills';
    case Payments = 'payments';
    case Contracts = 'contracts';
    case Employees = 'employees';
    case Customers = 'customers';
    case Suppliers = 'suppliers';
    case Projects = 'projects';
    case Manufacturing = 'manufacturing';
    case Hr = 'hr';
    case Warehouse = 'warehouse';
    case Reports = 'reports';
    case Receipts = 'receipts';
    case Audit = 'audit';
    case Company = 'company';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Invoices => __('scf.dms.category_invoices'),
            self::PurchaseOrders => __('scf.dms.category_purchase_orders'),
            self::SalesOrders => __('scf.dms.category_sales_orders'),
            self::Bills => __('scf.dms.category_bills'),
            self::Payments => __('scf.dms.category_payments'),
            self::Contracts => __('scf.dms.category_contracts'),
            self::Employees => __('scf.dms.category_employees'),
            self::Customers => __('scf.dms.category_customers'),
            self::Suppliers => __('scf.dms.category_suppliers'),
            self::Projects => __('scf.dms.category_projects'),
            self::Manufacturing => __('scf.dms.category_manufacturing'),
            self::Hr => __('scf.dms.category_hr'),
            self::Warehouse => __('scf.dms.category_warehouse'),
            self::Reports => __('scf.dms.category_reports'),
            self::Receipts => __('scf.dms.category_receipts'),
            self::Audit => __('scf.dms.category_audit'),
            self::Company => __('scf.dms.category_company'),
            self::General => __('scf.dms.category_general'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
