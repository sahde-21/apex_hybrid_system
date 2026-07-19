<?php

namespace App\Http\Controllers;

use App\Models\PosRegister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PosRegisterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('pos.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:pos_registers,code'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['sometimes', 'boolean'],
            'cash_drawer_enabled' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        PosRegister::query()->create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
            'cash_drawer_enabled' => $request->boolean('cash_drawer_enabled', true),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('pos.registers.index');
    }

    public function update(Request $request, PosRegister $posRegister): RedirectResponse
    {
        abort_unless($request->user()?->can('pos.update'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:pos_registers,code,'.$posRegister->id],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['sometimes', 'boolean'],
            'cash_drawer_enabled' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $posRegister->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', $posRegister->is_active),
            'cash_drawer_enabled' => $request->boolean('cash_drawer_enabled', $posRegister->cash_drawer_enabled),
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('pos.registers.index');
    }

    public function destroy(PosRegister $posRegister): RedirectResponse
    {
        abort_unless(request()->user()?->can('pos.delete'), 403);

        $posRegister->delete();

        return redirect()->route('pos.registers.index');
    }
}
