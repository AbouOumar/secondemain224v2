<?php
namespace App\Http\Controllers\Api;
use App\Enums\EmailCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailPreferenceController extends Controller
{
    public function show(Request $request) {
        return response()->json(['data' => $this->preferences($request)]);
    }

    /**
     * Corps attendu : { "categories": { "messages": true, "offres": false, ... } }
     * Les catégories absentes restent inchangées.
     */
    public function update(Request $request) {
        $values = array_column(EmailCategory::cases(), 'value');
        $request->validate([
            'categories' => ['required', 'array', function ($attribute, $value, $fail) use ($values) {
                if (array_diff(array_keys($value), $values)) {
                    $fail('Catégorie inconnue. Valeurs possibles : ' . implode(', ', $values) . '.');
                }
            }],
            'categories.*' => 'boolean',
        ]);

        foreach ($request->input('categories') as $key => $enabled) {
            $request->user()->setEmailPreference(EmailCategory::from($key), (bool) $enabled);
        }

        return response()->json(['data' => $this->preferences($request)]);
    }

    private function preferences(Request $request): array {
        $user = $request->user();
        return [
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
            'categories' => collect(EmailCategory::cases())->map(fn (EmailCategory $category) => [
                'key' => $category->value,
                'label' => $category->label(),
                'description' => $category->description(),
                'enabled' => $user->emailPreferenceEnabled($category),
            ])->all(),
        ];
    }
}
