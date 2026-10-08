<?php
return [
    // Firebase Cloud Messaging, API HTTP v1 (l'ancienne « clé serveur » est arrêtée par Google).
    'project_id' => env('FIREBASE_PROJECT_ID'),
    // Fichier JSON du compte de service : secret, hors Git (storage/app/private est ignoré).
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/private/firebase-service-account.json')),
];
