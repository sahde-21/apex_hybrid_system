<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Audit logs are read-only and displayed via Livewire.
     */
    public function index(Request $request): void
    {
        //
    }
}
