<x-mail::message>
# Bonjour {{ $user->name }},

{!! $campaign->content !!}

<x-slot:subcopy>
Vous recevez cet e-mail car vous êtes inscrit(e) à la newsletter {{ config('app.name') }}.
[Se désinscrire]({{ $unsubscribeUrl }}) · [Gérer mes préférences]({{ route('profile.email-preferences') }})
</x-slot:subcopy>
</x-mail::message>
