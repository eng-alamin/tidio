<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Workspace $workspace, Request $request)
    {
        $contacts = $workspace->contacts()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(25);

        return ContactResource::collection($contacts);
    }

    public function store(StoreContactRequest $request, Workspace $workspace)
    {
        $contact = $workspace->contacts()->create($request->validated());

        return new ContactResource($contact);
    }

    public function show(Workspace $workspace, Contact $contact)
    {
        $this->authorize('view', $contact);

        return new ContactResource($contact->load('tags'));
    }

    public function update(StoreContactRequest $request, Workspace $workspace, Contact $contact)
    {
        $this->authorize('update', $contact);
        $contact->update($request->validated());

        return new ContactResource($contact);
    }

    public function destroy(Workspace $workspace, Contact $contact)
    {
        $this->authorize('delete', $contact);
        $contact->delete();

        return response()->noContent();
    }
}
