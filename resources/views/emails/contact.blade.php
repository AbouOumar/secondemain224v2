@component('mail::message')
# Nouveau message de contact

**De :** {{ $senderName }} ({{ $senderEmail }})

{{ $body }}

Répondez directement à cet e-mail pour écrire à l'expéditeur.
@endcomponent
