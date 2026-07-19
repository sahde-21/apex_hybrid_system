<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    /**
     * @param  array<string, mixed>|JsonResource|ResourceCollection|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => (object) array_merge([
                'version' => config('api.version', 'v1'),
                'timestamp' => now()->toIso8601String(),
            ], $meta),
        ], $status);
    }

    /**
     * @param  array<string, mixed>|list<string>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        string $message,
        int $status = 400,
        mixed $errors = null,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'meta' => (object) array_merge([
                'version' => config('api.version', 'v1'),
                'timestamp' => now()->toIso8601String(),
            ], $meta),
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function paginated(
        ResourceCollection $collection,
        string $message = 'OK',
        array $meta = [],
    ): JsonResponse {
        $resource = $collection->response()->getData(true);
        $pagination = [];

        if (isset($resource['meta'], $resource['links'])) {
            $pagination = [
                'pagination' => [
                    'current_page' => $resource['meta']['current_page'] ?? null,
                    'from' => $resource['meta']['from'] ?? null,
                    'last_page' => $resource['meta']['last_page'] ?? null,
                    'path' => $resource['meta']['path'] ?? null,
                    'per_page' => $resource['meta']['per_page'] ?? null,
                    'to' => $resource['meta']['to'] ?? null,
                    'total' => $resource['meta']['total'] ?? null,
                    'links' => $resource['links'] ?? null,
                ],
            ];
        } elseif ($collection->resource instanceof AbstractPaginator) {
            $paginator = $collection->resource;
            $pagination = [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'path' => $paginator->path(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'links' => [
                        'first' => $paginator->url(1),
                        'last' => $paginator->url($paginator->lastPage()),
                        'prev' => $paginator->previousPageUrl(),
                        'next' => $paginator->nextPageUrl(),
                    ],
                ],
            ];
        }

        return self::success(
            data: $resource['data'] ?? $collection,
            message: $message,
            meta: array_merge($pagination, $meta),
        );
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
