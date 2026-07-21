<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContactType;
use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreSupplierRequest;
use App\Http\Requests\Api\V1\UpdateSupplierRequest;
use App\Http\Resources\V1\ContactResource;
use App\Http\Responses\ApiResponse;
use App\Models\Contact;
use App\Services\ContactService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class SupplierController extends ApiController
{
    public function __construct(
        protected ContactService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::SUPPLIERS_READ);
        $this->authorize('viewAny', Contact::class);

        $query = Contact::query()->whereIn('type', [ContactType::Supplier, ContactType::Both]);

        $query = (new ApiIndexQuery(
            $query,
            sortable: ['id', 'name', 'created_at', 'updated_at'],
            searchable: ['name', 'company_name', 'email', 'phone'],
        ))->apply($request);

        $suppliers = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            ContactResource::collection($suppliers),
            __('scf.api.suppliers.listed'),
            $this->meta($request),
        );
    }

    public function show(Contact $supplier): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::SUPPLIERS_READ);
        $this->authorize('view', $supplier);
        abort_unless(in_array($supplier->type, [ContactType::Supplier, ContactType::Both], true), 404);

        return $this->respond(new ContactResource($supplier), __('scf.api.suppliers.retrieved'));
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SUPPLIERS_WRITE);

        $supplier = $this->service->store($request->validated());
        $this->logCreated($this->actor($request), $supplier);

        return $this->respondCreated(new ContactResource($supplier), __('scf.api.suppliers.created'), $request);
    }

    public function update(UpdateSupplierRequest $request, Contact $supplier): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SUPPLIERS_WRITE);
        abort_unless(in_array($supplier->type, [ContactType::Supplier, ContactType::Both], true), 404);

        $supplier = $this->service->update($supplier, $request->validated());
        $this->logUpdated($this->actor($request), $supplier);

        return $this->respond(new ContactResource($supplier), __('scf.api.suppliers.updated'), $request);
    }

    public function destroy(Contact $supplier): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SUPPLIERS_WRITE);
        $this->authorize('delete', $supplier);
        abort_unless(in_array($supplier->type, [ContactType::Supplier, ContactType::Both], true), 404);

        $this->service->destroy($supplier);
        $this->logDeleted($this->actor(request()), $supplier);

        return $this->respondDeleted(__('scf.api.suppliers.deleted'));
    }
}
