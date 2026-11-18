<?php

namespace App\Rules;

use App\Services\Apporteur\ApporteurService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Le code saisi doit être celui d'un apporteur d'affaires actif. */
class ActiveApporteurCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(ApporteurService::class)->findActiveByCode((string) $value)) {
            $fail('Ce code apporteur est inconnu ou n\'est plus actif. Vérifiez-le ou laissez le champ vide.');
        }
    }
}
