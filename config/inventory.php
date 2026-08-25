<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inventory Ledger
    |--------------------------------------------------------------------------
    |
    | The warehouse stock ledger (stock_levels / stock_movements) is infrastructure
    | for future cutover. Existing POS / sales / purchasing stock behaviour is
    | unchanged while ledger_enabled is false.
    |
    */

    'ledger_enabled' => (bool) env('INVENTORY_LEDGER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Negative Stock Policy
    |--------------------------------------------------------------------------
    |
    | When false, posts that would make available (on_hand - reserved) negative
    | are rejected. Per-warehouse overrides may be added in a later phase.
    |
    */

    'allow_negative_stock' => (bool) env('INVENTORY_ALLOW_NEGATIVE_STOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Opening Stock Import (P0.2)
    |--------------------------------------------------------------------------
    |
    | Initializes ledger balances from products/variants.stock_quantity.
    | Does not enable ledger_enabled and does not mutate source columns.
    |
    */

    'opening_stock' => [
        'import_version' => env('INVENTORY_OPENING_IMPORT_VERSION', 'v1'),
        'warehouse_id' => env('INVENTORY_OPENING_WAREHOUSE_ID'),
        'warehouse_code' => env('INVENTORY_OPENING_WAREHOUSE_CODE'),
        'default_warehouse_code' => env('INVENTORY_OPENING_DEFAULT_WAREHOUSE_CODE', 'MAIN'),
        'default_warehouse_name' => env('INVENTORY_OPENING_DEFAULT_WAREHOUSE_NAME', 'Main Warehouse'),
    ],

];
