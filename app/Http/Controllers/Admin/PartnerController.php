<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Http\Resources\PartnerResource;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request) {
        $query = Partner::with('user');
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }
        $partners = $query->orderBy('created_at', 'desc')->paginate(15);
        return PartnerResource::collection($partners);
    }

    public function show(Partner $partner) {
        $partner->load('user');
        return new PartnerResource($partner);
    }

    public function verify(Partner $partner) {
        $partner->update(['is_verified' => true]);
        return new PartnerResource($partner);
    }

    public function unverify(Partner $partner) {
        $partner->update(['is_verified' => false]);
        return new PartnerResource($partner);
    }

    public function destroy(Partner $partner) {
        $partner->delete();
        return response()->json(null, 204);
    }
}
