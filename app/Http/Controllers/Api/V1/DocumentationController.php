<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class DocumentationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $baseUrl = rtrim((string) config('app.url'), '/').'/api/v1';

        return ApiResponse::success([
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name').' API',
                'description' => 'Enterprise JSON API for mobile, Flutter, React, Vue, and external integrations.',
                'version' => config('api.version', 'v1'),
            ],
            'servers' => [
                ['url' => $baseUrl, 'description' => 'API v1'],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum',
                    ],
                ],
                'schemas' => [
                    'ApiSuccess' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => true],
                            'message' => ['type' => 'string'],
                            'data' => ['nullable' => true],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'version' => ['type' => 'string'],
                                    'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                                    'pagination' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                    'ApiError' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => false],
                            'message' => ['type' => 'string'],
                            'data' => ['nullable' => true, 'example' => null],
                            'errors' => ['nullable' => true],
                            'meta' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/health' => [
                    'get' => [
                        'summary' => 'Health check',
                        'security' => [],
                        'responses' => [
                            '200' => ['description' => 'Healthy'],
                            '503' => ['description' => 'Degraded'],
                        ],
                    ],
                ],
                '/docs' => [
                    'get' => [
                        'summary' => 'OpenAPI documentation',
                        'security' => [],
                        'responses' => [
                            '200' => ['description' => 'API documentation'],
                        ],
                    ],
                ],
                '/auth/login' => [
                    'post' => [
                        'summary' => 'Authenticate and issue a bearer token',
                        'security' => [],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email', 'password'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'format' => 'email'],
                                            'password' => ['type' => 'string'],
                                            'device_name' => ['type' => 'string'],
                                            'client' => [
                                                'type' => 'string',
                                                'enum' => config('api.clients', []),
                                            ],
                                            'abilities' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                            ],
                                            'expires_at' => ['type' => 'string', 'format' => 'date-time'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Authenticated'],
                            '403' => ['description' => 'Inactive or locked'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/auth/me' => [
                    'get' => [
                        'summary' => 'Current authenticated user',
                        'responses' => [
                            '200' => ['description' => 'User profile'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                ],
                '/auth/logout' => [
                    'post' => [
                        'summary' => 'Revoke current token',
                        'responses' => [
                            '200' => ['description' => 'Logged out'],
                        ],
                    ],
                ],
                '/auth/logout-all' => [
                    'post' => [
                        'summary' => 'Revoke all tokens for the user',
                        'responses' => [
                            '200' => ['description' => 'Logged out everywhere'],
                        ],
                    ],
                ],
                '/tokens' => [
                    'get' => [
                        'summary' => 'List personal access tokens (paginated)',
                        'parameters' => [
                            [
                                'name' => 'per_page',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => (int) config('api.pagination.max_per_page', 100)],
                            ],
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'minimum' => 1],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Token list'],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Create a personal access token',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name'],
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'client' => [
                                                'type' => 'string',
                                                'enum' => config('api.clients', []),
                                            ],
                                            'abilities' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                            ],
                                            'expires_at' => ['type' => 'string', 'format' => 'date-time'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Token created'],
                        ],
                    ],
                ],
                '/tokens/{token}' => [
                    'get' => [
                        'summary' => 'Show a token',
                        'parameters' => [
                            ['name' => 'token', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Token details'],
                            '404' => ['description' => 'Not found'],
                        ],
                    ],
                    'delete' => [
                        'summary' => 'Revoke a token',
                        'parameters' => [
                            ['name' => 'token', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Token revoked'],
                        ],
                    ],
                ],
                '/tokens/others' => [
                    'delete' => [
                        'summary' => 'Revoke all tokens except the current one',
                        'responses' => [
                            '200' => ['description' => 'Other tokens revoked'],
                        ],
                    ],
                ],
            ],
            'clients' => [
                'Authorization' => 'Authorization: Bearer {token}',
                'Accept' => 'application/json',
                'platforms' => config('api.clients', []),
            ],
        ], __('API documentation retrieved.'));
    }
}
