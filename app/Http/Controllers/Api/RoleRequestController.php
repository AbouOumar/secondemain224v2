<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\RoleRequestException;
use App\Http\Controllers\Controller;
use App\Models\RoleRequest;
use App\Services\Account\RoleRequestService;
use Illuminate\Http\Request;

/** Demande pour devenir livreur ou revendeur pro (examinée par l'administration). */
class RoleRequestController extends Controller
{
    public function __construct(private RoleRequestService $requests) {}

    /** Dernière demande du compte, ou null. */
    public function show(Request $request) {
        $latest = RoleRequest::where('user_id', $request->user()->id)->latest('id')->first();

        return response()->json(['data' => $latest ? $this->format($latest) : null]);
    }

    public function store(Request $request) {
        $request->validate(['role' => 'required|string|in:' . implode(',', RoleRequest::REQUESTABLE)]);
        $data = $request->validate(array_merge(['details' => 'required|array'], RoleRequestService::detailRules($request->role)));

        try {
            $roleRequest = $this->requests->request($request->user(), $request->role, $data['details']);
        } catch (RoleRequestException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['data' => $this->format($roleRequest)], 201);
    }

    public function destroy(Request $request) {
        $this->requests->withdraw($request->user());

        return response()->json(null, 204);
    }

    private function format(RoleRequest $roleRequest): array {
        return [
            'id' => $roleRequest->id,
            'role' => $roleRequest->role,
            'status' => $roleRequest->status,
            'details' => $roleRequest->details,
            'note' => $roleRequest->note,
            'created_at' => $roleRequest->created_at,
            'processed_at' => $roleRequest->processed_at,
        ];
    }
}
