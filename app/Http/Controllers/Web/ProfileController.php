<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    private function getEarnings($userId)
    {
        $now = now();
        $completedStatuses = ['paye', 'livre'];

        $base = Order::where('seller_id', $userId)->whereIn('status', $completedStatuses);

        return [
            'aujourd_hui' => (clone $base)->whereDate('created_at', $now->today())->sum('total'),
            'cette_semaine' => (clone $base)->whereBetween('created_at', [$now->startOfWeek(), $now->copy()->endOfWeek()])->sum('total'),
            'ce_mois' => (clone $base)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total'),
            'cette_annee' => (clone $base)->whereYear('created_at', $now->year)->sum('total'),
        ];
    }

    public function dashboard()
    {
        $user = Auth::user();

        $stats = Article::where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_published = true THEN 1 ELSE 0 END) as en_vente')
            ->selectRaw('SUM(CASE WHEN id IN (SELECT article_id FROM orders WHERE status = "termine") THEN 1 ELSE 0 END) as vendus')
            ->first();

        $total = $stats->total ?? 0;
        $enVente = $stats->en_vente ?? 0;
        $vendus = $stats->vendus ?? 0;
        $taux = $total ? round(($vendus / $total) * 100) : 0;

        $recentOrders = Order::where('seller_id', $user->id)
            ->orWhere('buyer_id', $user->id)
            ->with(['article', 'buyer', 'seller'])
            ->latest()
            ->take(5)
            ->get();

        $earnings = $this->getEarnings($user->id);

        return view('profile.dashboard', compact('total', 'enVente', 'vendus', 'taux', 'recentOrders', 'user', 'earnings'));
    }

    /**
     * Show professional seller dashboard
     */
    public function proDashboard()
    {
        $user = Auth::user();

        $stats = Article::where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_published = true THEN 1 ELSE 0 END) as en_vente')
            ->selectRaw('SUM(CASE WHEN id IN (SELECT article_id FROM orders WHERE status = "termine") THEN 1 ELSE 0 END) as vendus')
            ->first();

        $total = $stats->total ?? 0;
        $enVente = $stats->en_vente ?? 0;
        $vendus = $stats->vendus ?? 0;
        $taux = $total ? round(($vendus / $total) * 100) : 0;

        $recentOrders = Order::where('seller_id', $user->id)
            ->with(['article', 'buyer'])
            ->latest()
            ->take(5)
            ->get();

        $earnings = $this->getEarnings($user->id);

        return view('seller.pro.dashboard', compact('total', 'enVente', 'vendus', 'taux', 'recentOrders', 'user', 'earnings'));
    }

    /**
     * Show subscription management page
     */
    public function subscription()
    {
        return view('seller.pro.subscription');
    }

    /**
     * Show verification page
     */
    public function verification()
    {
        return view('seller.pro.verification');
    }

    public function listings(Request $request)
    {
        $query = Article::where('user_id', Auth::id());

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('statut', 'en_vente');
            } elseif ($request->status === 'sold') {
                $query->where('statut', 'vendu');
            }
        }

        if ($request->filled('search')) {
            $query->where('titre', 'like', "%{$request->search}%");
        }

        $articles = $query->orderBy('created_at', 'desc')->paginate(12);

        $isAjaxRequest = $request->ajax()
            || $request->boolean('ajax')
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($isAjaxRequest) {
            return response()->json([
                'html' => view('partials.profile-articles', compact('articles'))->render(),
                'hasMore' => $articles->hasMorePages()
            ]);
        }

        return view('profile.listings', compact('articles'));
    }

    public function saved()
    {
        $articles = Auth::user()->savedArticles()
            ->with(['images', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('profile.saved', compact('articles'));
    }

    public function toggleSave(Request $request, Article $article)
    {
        if ($request->boolean('check')) {
            $saved = $request->user()->savedArticles()->where('article_id', $article->id)->exists();
            return response()->json(['saved' => $saved]);
        }
        $user = $request->user();
        if ($user->savedArticles()->where('article_id', $article->id)->exists()) {
            $user->savedArticles()->detach($article->id);
            $saved = false;
        } else {
            $user->savedArticles()->attach($article->id);
            $saved = true;
        }
        return response()->json(['saved' => $saved]);
    }

    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
        ]);

        $user->update($request->only('name', 'email', 'phone'));

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $path]);
        }

        if ($request->filled('current_password') && $request->filled('new_password')) {
            $request->validate([
                'current_password' => 'required|current_password',
                'new_password' => 'required|string|min:8|confirmed',
            ]);
            $user->update(['password' => Hash::make($request->new_password)]);
        }

        return redirect()->route('profile.edit')->with('success', 'Profil mis à jour.');
    }

    public function avatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048']);
        $user = Auth::user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'avatar' => asset('storage/'.$path)]);
        }
        return back()->with('success', 'Photo mise à jour.');
    }
}
