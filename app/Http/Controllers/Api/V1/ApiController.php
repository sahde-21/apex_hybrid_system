<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\AuthorizesApiAbilities;
use App\Concerns\ResolvesApiPagination;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Api\ApiAuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    use AuthorizesApiAbilities, ResolvesApiPagination;

    protected function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    protected function audit(): ApiAuditService
    {
        return app(ApiAuditService::class);
    }

    protected function meta(Request $request, array $extra = []): array
    {
        return array_merge([
            'request_id' => $request->attributes->get('request_id'),
        ], $extra);
    }

    protected function respond(mixed $data, string $message, ?Request $request = null, int $status = 200): JsonResponse
    {
        return ApiResponse::success(
            data: $data,
            message: $message,
            status: $status,
            meta: $this->meta($request ?? request()),
        );
    }

    protected function respondCreated(mixed $data, string $message, ?Request $request = null): JsonResponse
    {
        return $this->respond($data, $message, $request, 201);
    }

    protected function respondDeleted(string $message = null): JsonResponse
    {
        return ApiResponse::success(null, $message ?? __('scf.api.deleted_successfully'));
    }

    protected function logCreated(User $user, Model $model): void
    {
        $this->audit()->recordCreated($user, $model);
    }

    protected function logUpdated(User $user, Model $model): void
    {
        $this->audit()->recordUpdated($user, $model);
    }

    protected function logDeleted(User $user, Model $model): void
    {
        $this->audit()->recordDeleted($user, $model);
    }

    protected function logWorkflow(User $user, Model $model, string $action): void
    {
        $this->audit()->workflowTransition($user, $model, $action);
    }

    protected function logFinancial(User $user, Model $model, string $action): void
    {
        $this->audit()->financialPosting($user, $model, $action);
    }
}
