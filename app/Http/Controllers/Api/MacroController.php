<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMacroRequest;
use App\Models\Macro;
use App\Models\Workspace;

class MacroController extends Controller
{
    public function index(Workspace $workspace)
    {
        return $workspace->macros()->latest()->get();
    }

    public function store(StoreMacroRequest $request, Workspace $workspace)
    {
        return $workspace->macros()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);
    }

    public function update(StoreMacroRequest $request, Workspace $workspace, Macro $macro)
    {
        $this->authorize('update', $macro);
        $macro->update($request->validated());

        return $macro;
    }

    public function destroy(Workspace $workspace, Macro $macro)
    {
        $this->authorize('delete', $macro);
        $macro->delete();

        return response()->noContent();
    }
}
