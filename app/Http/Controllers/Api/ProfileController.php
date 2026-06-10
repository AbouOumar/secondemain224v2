<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request) {
        $user = $request->user()->load('wallet');
        return new UserResource($user);
    }

    public function update(UpdateProfileRequest $request) {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        if (isset($data['new_password'])) {
            $data['password'] = bcrypt($data['new_password']);
            unset($data['new_password'], $data['current_password']);
        }

        $user->update($data);
        return new UserResource($user);
    }

    public function avatar(Request $request) {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048']);
        $user = $request->user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);
        return response()->json(['avatar' => asset('storage/'.$path)]);
    }

    public function saved(Request $request) {
        $articles = $request->user()->savedArticles()
            ->with(['images', 'category', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return ArticleResource::collection($articles);
    }
}
