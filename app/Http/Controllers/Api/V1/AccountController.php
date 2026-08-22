<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreAccountRequest;
use App\Http\Requests\Api\V1\UpdateAccountRequest;
use App\Http\Resources\V1\AccountResource;
use App\Http\Responses\ApiResponse;
use App\Models\Account;
use App\Services\Accounting\ChartOfAccountsService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class AccountController extends ApiController
{
    public function __construct(
        protected ChartOfAccountsService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::ACCOUNTING_READ);
        $this->authorize('viewAny', Account::class);

        $query = (new ApiIndexQuery(
            Account::query(),
            sortable: ['id', 'code', 'name', 'type', 'created_at', 'updated_at', 'is_active'],
            searchable: ['code', 'name', 'description', 'system_key'],
            includes: ['parent', 'branch'],
        ))->apply($request);

        $accounts = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            AccountResource::collection($accounts),
            __('scf.api.accounts.listed'),
            $this->meta($request),
        );
    }

    public function show(Account $account): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::ACCOUNTING_READ);
        $this->authorize('view', $account);

        return $this->respond(new AccountResource($account), __('scf.api.accounts.retrieved'));
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::ACCOUNTING_WRITE);

        $account = $this->service->create($this->actor($request), $request->validated());
        $this->logCreated($this->actor($request), $account);

        return $this->respondCreated(
            new AccountResource($account->fresh()),
            __('scf.api.accounts.created'),
            $request,
        );
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::ACCOUNTING_WRITE);

        $account = $this->service->update($account, $this->actor($request), $request->validated());
        $this->logUpdated($this->actor($request), $account);

        return $this->respond(
            new AccountResource($account->fresh()),
            __('scf.api.accounts.updated'),
            $request,
        );
    }

    public function destroy(Account $account): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::ACCOUNTING_WRITE);
        $this->authorize('delete', $account);

        $this->service->archive($account, $this->actor(request()));
        $this->logDeleted($this->actor(request()), $account);

        return $this->respondDeleted(__('scf.api.accounts.deleted'));
    }
}
