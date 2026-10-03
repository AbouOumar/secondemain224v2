<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleRequest;
use App\Services\Account\RoleRequestService;
use Illuminate\Http\Request;

/** Examen des demandes de compte livreur / revendeur pro. */
class RoleRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', RoleRequest::PENDING);

        $query = RoleRequest::with(['user.latestIdentityVerification', 'processor']);
        if (in_array($status, [RoleRequest::PENDING, RoleRequest::APPROVED, RoleRequest::REJECTED], true)) {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('created_at')->paginate(20)->withQueryString();

        return view('admin.role-requests.index', compact('requests', 'status'));
    }

    public function approve(Request $request, RoleRequest $roleRequest, RoleRequestService $service)
    {
        if (! $service->approve($roleRequest, $request->user())) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        return back()->with('success', "Compte {$roleRequest->role} accordé à {$roleRequest->user->name}.");
    }

    public function reject(Request $request, RoleRequest $roleRequest, RoleRequestService $service)
    {
        $note = $request->validate(['note' => 'nullable|string|max:191'])['note'] ?? null;

        if (! $service->reject($roleRequest, $request->user(), $note)) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        return back()->with('success', 'Demande refusée.');
    }
}
