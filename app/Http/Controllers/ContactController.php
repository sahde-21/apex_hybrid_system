<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function __construct(
        protected ContactService $service,
    ) {}

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('contacts.index')
            ->with('status', __('Contact created successfully.'));
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->service->update($contact, $request->validated());

        return redirect()
            ->route('contacts.index')
            ->with('status', __('Contact updated successfully.'));
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->service->destroy($contact);

        return redirect()
            ->route('contacts.index')
            ->with('status', __('Contact deleted successfully.'));
    }
}
