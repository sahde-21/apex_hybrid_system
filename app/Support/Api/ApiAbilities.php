<?php

namespace App\Support\Api;

final class ApiAbilities
{
    public const READ_ALL = '*';

    public const PRODUCTS_READ = 'products.read';
    public const PRODUCTS_WRITE = 'products.write';
    public const CUSTOMERS_READ = 'customers.read';
    public const CUSTOMERS_WRITE = 'customers.write';
    public const SUPPLIERS_READ = 'suppliers.read';
    public const SUPPLIERS_WRITE = 'suppliers.write';
    public const SALES_READ = 'sales.read';
    public const SALES_WRITE = 'sales.write';
    public const PURCHASING_READ = 'purchasing.read';
    public const PURCHASING_WRITE = 'purchasing.write';
    public const ACCOUNTING_READ = 'accounting.read';

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
            self::CUSTOMERS_READ,
            self::CUSTOMERS_WRITE,
            self::SUPPLIERS_READ,
            self::SUPPLIERS_WRITE,
            self::SALES_READ,
            self::SALES_WRITE,
            self::PURCHASING_READ,
            self::PURCHASING_WRITE,
            self::ACCOUNTING_READ,
            self::INTELLIGENCE_READ,
            self::INTELLIGENCE_MANAGE,
        ];
    }
}
