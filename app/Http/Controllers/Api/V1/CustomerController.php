<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContactType;
use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\V1\ContactResource;
use App\Http\Responses\ApiResponse;
use App\Models\Contact;
use App\Services\ContactService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class CustomerController extends ApiController
{
    public function __construct(
        protected ContactService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::CUSTOMERS_READ);
        $this->authorize('viewAny', Contact::class);

        $query = Contact::query()->whereIn('type', [ContactType::Customer, ContactType::Both]);

        $query = (new ApiIndexQuery(
            $query,
            sortable: ['id', 'name', 'created_at', 'updated_at'],
            searchable: ['name', 'company_name', 'email', 'phone'],
        ))->apply($request);

        $customers = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            ContactResource::collection($customers),
            __('scf.api.customers.listed'),
            $this->meta($request),
        );
    }

    public function show(Contact $customer): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::CUSTOMERS_READ);
        $this->authorize('view', $customer);
        abort_unless(in_array($customer->type, [ContactType::Customer, ContactType::Both], true), 404);

        return $this->respond(new ContactResource($customer), __('scf.api.customers.retrieved'));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::CUSTOMERS_WRITE);

        $customer = $this->service->store($request->validated());
        $this->logCreated($this->actor($request), $customer);

        return $this->respondCreated(new ContactResource($customer), __('scf.api.customers.created'), $request);
    }

    public function update(UpdateCustomerRequest $request, Contact $customer): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::CUSTOMERS_WRITE);
        abort_unless(in_array($customer->type, [ContactType::Customer, ContactType::Both], true), 404);

        $customer = $this->service->update($customer, $request->validated());
        $this->logUpdated($this->actor($request), $customer);

        return $this->respond(new ContactResource($customer), __('scf.api.customers.updated'), $request);
    }

    public function destroy(Contact $customer): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::CUSTOMERS_WRITE);
        $this->authorize('delete', $customer);
        abort_unless(in_array($customer->type, [ContactType::Customer, ContactType::Both], true), 404);

        $this->service->destroy($customer);
        $this->logDeleted($this->actor(request()), $customer);

        return $this->respondDeleted(__('scf.api.customers.deleted'));
    }
}
