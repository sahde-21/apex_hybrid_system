<?php

namespace App\Support\Api;

final class OpenApiSpec
{
    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/').'/api/v1';

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name').' API',
                'description' => 'Enterprise JSON API for integrations, mobile clients, and partner systems.',
                'version' => config('api.version', 'v1'),
            ],
            'servers' => [
                ['url' => $baseUrl, 'description' => 'API v1'],
            ],
            'security' => [['bearerAuth' => []]],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum',
                    ],
                ],
                'parameters' => [
                    'IdempotencyKey' => [
                        'name' => 'Idempotency-Key',
                        'in' => 'header',
                        'required' => false,
                        'schema' => ['type' => 'string'],
                        'description' => 'Unique key for safely retrying create/post/convert operations.',
                    ],
                    'RequestId' => [
                        'name' => 'X-Request-Id',
                        'in' => 'header',
                        'required' => false,
                        'schema' => ['type' => 'string'],
                        'description' => 'Optional correlation ID echoed in responses and logs.',
                    ],
                ],
                'schemas' => [
                    'ApiSuccess' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => true],
                            'message' => ['type' => 'string'],
                            'data' => ['nullable' => true],
                            'meta' => ['type' => 'object'],
                        ],
                    ],
                    'ApiError' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => false],
                            'message' => ['type' => 'string'],
                            'errors' => ['nullable' => true],
                            'meta' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
            'paths' => array_merge(
                self::publicPaths(),
                self::authPaths(),
                self::resourcePaths('products', 'Products', ['products.read'], ['products.write']),
                self::resourcePaths('customers', 'Customers', ['customers.read'], ['customers.write']),
                self::resourcePaths('suppliers', 'Suppliers', ['suppliers.read'], ['suppliers.write']),
                self::resourcePaths('quotations', 'Quotations', ['sales.read'], ['sales.write']),
                self::resourcePaths('sale-orders', 'Sales Orders', ['sales.read'], ['sales.write']),
                self::resourcePaths('invoices', 'Invoices', ['sales.read', 'accounting.read'], ['sales.write']),
                self::resourcePaths('payments', 'Payments', ['sales.read', 'accounting.read'], ['sales.write']),
                self::resourcePaths('purchase-requests', 'Purchase Requests', ['purchasing.read'], ['purchasing.write']),
                self::resourcePaths('rfqs', 'RFQs', ['purchasing.read'], ['purchasing.write']),
                self::resourcePaths('purchase-orders', 'Purchase Orders', ['purchasing.read'], ['purchasing.write']),
                self::resourcePaths('bills', 'Vendor Bills', ['purchasing.read', 'accounting.read'], ['purchasing.write']),
                self::resourcePaths('vendor-payments', 'Vendor Payments', ['purchasing.read', 'accounting.read'], ['purchasing.write']),
            ),
            'tags' => [
                ['name' => 'Authentication'],
                ['name' => 'Products'],
                ['name' => 'Customers'],
                ['name' => 'Suppliers'],
                ['name' => 'Sales'],
                ['name' => 'Purchasing'],
                ['name' => 'Accounting'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function publicPaths(): array
    {
        return [
            '/health' => [
                'get' => [
                    'summary' => 'Health check',
                    'security' => [],
                    'responses' => ['200' => ['description' => 'Healthy']],
                ],
            ],
            '/docs' => [
                'get' => [
                    'summary' => 'OpenAPI documentation',
                    'security' => [],
                    'responses' => ['200' => ['description' => 'OpenAPI JSON document']],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function authPaths(): array
    {
        return [
            '/auth/login' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Authenticate and receive a Sanctum token',
                    'security' => [],
                    'responses' => ['201' => ['description' => 'Authenticated']],
                ],
            ],
            '/auth/me' => [
                'get' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Current authenticated user',
                    'responses' => ['200' => ['description' => 'User profile']],
                ],
            ],
            '/auth/logout' => [
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Revoke current token',
                    'responses' => ['200' => ['description' => 'Logged out']],
                ],
            ],
            '/tokens' => [
                'get' => [
                    'tags' => ['Authentication'],
                    'summary' => 'List personal access tokens',
                    'responses' => ['200' => ['description' => 'Token list']],
                ],
                'post' => [
                    'tags' => ['Authentication'],
                    'summary' => 'Create a named personal access token',
                    'responses' => ['201' => ['description' => 'Token created']],
                ],
            ],
        ];
    }

    /**
     * @param  list<string>  $readAbilities
     * @param  list<string>  $writeAbilities
     * @return array<string, mixed>
     */
    private static function resourcePaths(string $slug, string $label, array $readAbilities, array $writeAbilities): array
    {
        return [
            '/'.$slug => [
                'get' => [
                    'summary' => "List {$label}",
                    'parameters' => [
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'sort', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'include', 'in' => 'query', 'schema' => ['type' => 'string']],
                    ],
                    'responses' => ['200' => ['description' => 'Paginated list']],
                    'description' => 'Required abilities: '.implode(', ', $readAbilities),
                ],
                'post' => [
                    'summary' => "Create {$label}",
                    'parameters' => [['$ref' => '#/components/parameters/IdempotencyKey']],
                    'responses' => ['201' => ['description' => 'Created']],
                    'description' => 'Required abilities: '.implode(', ', $writeAbilities),
                ],
            ],
            '/'.$slug.'/{id}' => [
                'get' => [
                    'summary' => "Show {$label}",
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Resource'], '404' => ['description' => 'Not found']],
                ],
                'put' => [
                    'summary' => "Update {$label}",
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Updated']],
                ],
                'delete' => [
                    'summary' => "Delete {$label}",
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'responses' => ['200' => ['description' => 'Deleted when business rules allow']],
                ],
            ],
        ];
    }
}
