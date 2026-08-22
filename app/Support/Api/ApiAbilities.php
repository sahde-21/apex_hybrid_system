<?php

namespace App\Support\Api;

final class ApiAbilities
{
    public const READ_ALL = '*';

    public const PRODUCTS_READ = 'products.read';
    public const PRODUCTS_WRITE = 'products.write';
    public const WAREHOUSES_READ = 'warehouses.read';
    public const WAREHOUSES_WRITE = 'warehouses.write';
    public const EMPLOYEES_READ = 'employees.read';
    public const EMPLOYEES_WRITE = 'employees.write';
    public const POS_REGISTERS_READ = 'pos_registers.read';
    public const POS_REGISTERS_WRITE = 'pos_registers.write';
    public const CUSTOMERS_READ = 'customers.read';
    public const CUSTOMERS_WRITE = 'customers.write';
    public const SUPPLIERS_READ = 'suppliers.read';
    public const SUPPLIERS_WRITE = 'suppliers.write';
    public const SALES_READ = 'sales.read';
    public const SALES_WRITE = 'sales.write';
    public const PURCHASING_READ = 'purchasing.read';
    public const PURCHASING_WRITE = 'purchasing.write';
    public const ACCOUNTING_READ = 'accounting.read';
    public const ACCOUNTING_WRITE = 'accounting.write';

    public const INTELLIGENCE_READ = 'intelligence.read';

    public const INTELLIGENCE_MANAGE = 'intelligence.manage';

    /**
     * @return list<string>
     */
    public static function catalog(): array
    {
        return [
            self::PRODUCTS_READ,
            self::PRODUCTS_WRITE,
            self::WAREHOUSES_READ,
            self::WAREHOUSES_WRITE,
            self::EMPLOYEES_READ,
            self::EMPLOYEES_WRITE,
            self::POS_REGISTERS_READ,
            self::POS_REGISTERS_WRITE,
            self::CUSTOMERS_READ,
            self::CUSTOMERS_WRITE,
            self::SUPPLIERS_READ,
            self::SUPPLIERS_WRITE,
            self::SALES_READ,
            self::SALES_WRITE,
            self::PURCHASING_READ,
            self::PURCHASING_WRITE,
            self::ACCOUNTING_READ,
            self::ACCOUNTING_WRITE,
            self::INTELLIGENCE_READ,
            self::INTELLIGENCE_MANAGE,
        ];
    }
}
