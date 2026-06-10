<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Rating;
use App\Models\User;
use App\Http\Resources\RatingResource;

class RatingController extends Controller
{
    public function store(StoreRatingRequest $request) {
        $rating = Rating::create([
            'rater_id' => $request->user()->id,
            'rated_id' => $request->rated_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'role_type' => $request->role_type,
            'order_id' => $request->order_id,
        ]);
        $rating->load('rater');
        return new RatingResource($rating, 201);
    }

    public function userRatings(User $user) {
        $ratings = Rating::where('rated_id', $user->id)
            ->with('rater')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('role_type');
        return response()->json([
            'data' => $ratings->map(function($items, $roleType) {
                return [
                    'role_type' => $roleType,
                    'average' => $items->avg('rating'),
                    'count' => $items->count(),
                    'ratings' => RatingResource::collection($items),
                ];
            })->values(),
        ]);
    }
}
