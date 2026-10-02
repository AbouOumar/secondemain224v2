@extends('layouts.app')

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-9">
<h1 class="fw-bold mb-2">Politique de confidentialité</h1>
<p class="text-muted mb-4">Dernière mise à jour : {{ config('legal.updated_at') }}</p>

<div class="card border-0 shadow-sm p-4 mb-4 legal" style="border-radius: 18px;">

<p>Cette politique explique quelles données personnelles {{ config('app.name') }} (le « Site », accessible à l'adresse {{ url('/') }} et via ses applications) collecte, pourquoi, avec qui elles sont partagées, combien de temps elles sont conservées et quels sont vos droits.</p>

<h5 class="fw-bold mt-4">1. Responsable du traitement</h5>
<p>
{{ config('legal.company_name') }}, {{ config('legal.legal_form') }}, RCCM {{ config('legal.rccm') }}<br>
{{ config('legal.address') }}<br>
E-mail : {{ config('legal.email') }} · Téléphone : {{ config('legal.phone') }}
</p>

<h5 class="fw-bold mt-4">2. Données que nous collectons</h5>
<ul>
<li><strong>Compte :</strong> nom, numéro de téléphone, adresse e-mail (facultative), mot de passe (stocké chiffré, jamais lisible), rôle (acheteur, vendeur, revendeur, motard), photo de profil.</li>
<li><strong>Connexion avec Google :</strong> si vous choisissez cette option, Google nous transmet votre nom, votre adresse e-mail, votre photo de profil et un identifiant de compte Google. Nous ne recevons jamais votre mot de passe Google.</li>
<li><strong>Annonces :</strong> titre, description, prix, photos, catégorie, état, localisation de l'article.</li>
<li><strong>Échanges :</strong> messages envoyés aux autres utilisateurs, offres et contre-offres, avis et notes, signalements.</li>
<li><strong>Commandes et paiements :</strong> articles achetés ou vendus, montants, mode de paiement choisi, statut des paiements, historique du portefeuille. Les numéros de carte bancaire ou codes Mobile Money sont saisis chez le prestataire de paiement et ne sont pas conservés par le Site.</li>
<li><strong>Livraisons et localisation :</strong> adresse ou position de livraison ; pour les motards, leur position GPS pendant une livraison en cours, afin de permettre le suivi par l'acheteur et le vendeur.</li>
<li><strong>Vérification d'identité (vendeurs) :</strong> type et photo de la pièce d'identité ou du document professionnel, et photo de vous (selfie), afin d'attribuer le badge « vendeur vérifié ».</li>
<li><strong>Préférences :</strong> alertes de recherche, favoris, choix des e-mails reçus, inscription à la newsletter.</li>
<li><strong>Données techniques :</strong> adresse IP, type de navigateur, date de dernière connexion, journaux techniques nécessaires à la sécurité du Site.</li>
</ul>

<h5 class="fw-bold mt-4">3. Pourquoi nous utilisons ces données</h5>
<div class="table-responsive">
<table class="table table-sm">
<thead><tr><th>Finalité</th><th>Fondement</th></tr></thead>
<tbody>
<tr><td>Créer et gérer votre compte, vous connecter (y compris avec Google)</td><td>Exécution du contrat (CGU)</td></tr>
<tr><td>Publier les annonces, permettre la messagerie, les offres et les commandes</td><td>Exécution du contrat</td></tr>
<tr><td>Traiter les paiements, le séquestre et les livraisons</td><td>Exécution du contrat</td></tr>
<tr><td>Vous envoyer les e-mails liés à votre compte (bienvenue, confirmation d'adresse, mot de passe) et à votre activité (offres, commandes, messages, livraisons, alertes)</td><td>Exécution du contrat ; vous pouvez désactiver les e-mails d'activité à tout moment</td></tr>
<tr><td>Vous envoyer la newsletter</td><td>Votre consentement (case à cocher), retirable à tout moment</td></tr>
<tr><td>Vérifier l'identité des vendeurs, lutter contre la fraude, modérer les annonces et traiter les signalements</td><td>Intérêt légitime à assurer la sécurité de la plateforme</td></tr>
<tr><td>Conserver les justificatifs de transactions</td><td>Obligations légales comptables et fiscales</td></tr>
</tbody>
</table>
</div>
<p>Nous ne vendons pas vos données et ne les utilisons pas pour de la publicité ciblée.</p>

<h5 class="fw-bold mt-4">4. Qui peut voir vos données</h5>
<ul>
<li><strong>Les autres utilisateurs :</strong> votre nom, votre photo, vos annonces, vos avis et votre badge « vérifié » sont publics. Votre numéro de téléphone et votre adresse ne sont communiqués qu'aux personnes concernées par une commande ou une livraison.</li>
<li><strong>Les motards partenaires :</strong> les informations nécessaires à la livraison (nom, téléphone, adresses de retrait et de livraison).</li>
<li><strong>Notre équipe d'administration :</strong> pour la modération, la vérification d'identité, le support et la gestion des litiges.</li>
<li><strong>Nos prestataires techniques</strong>, qui n'utilisent les données que pour nous fournir leur service :
<ul>
<li>{{ config('legal.host') }} : hébergement du Site, de la base de données et envoi des e-mails ;</li>
<li>Google : connexion avec Google (si vous la choisissez) et polices d'écriture du Site ;</li>
<li>les prestataires de paiement (Djomy, Orange Money, MTN Mobile Money) : traitement des paiements ;</li>
<li>OpenStreetMap / Leaflet : affichage des cartes de suivi de livraison.</li>
</ul>
</li>
<li><strong>Les autorités</strong>, uniquement lorsque la loi nous y oblige.</li>
</ul>
<p>Certains de ces prestataires sont situés hors de Guinée (notamment en France et aux États-Unis). Nous ne leur transmettons que les données nécessaires à leur service.</p>

<h5 class="fw-bold mt-4">5. Durée de conservation</h5>
<ul>
<li><strong>Compte :</strong> tant que votre compte est actif. Après suppression du compte, les données sont effacées ou anonymisées, sauf celles que nous devons conserver ci-dessous.</li>
<li><strong>Commandes, paiements, transactions :</strong> pendant la durée imposée par les obligations comptables et fiscales.</li>
<li><strong>Documents de vérification d'identité :</strong> le temps de la vérification, puis tant que le badge « vendeur vérifié » est maintenu.</li>
<li><strong>Positions GPS des motards :</strong> le temps de la livraison et du traitement d'un éventuel litige.</li>
<li><strong>Newsletter :</strong> jusqu'à votre désinscription.</li>
<li><strong>Journaux techniques :</strong> durée limitée, nécessaire à la sécurité du Site.</li>
</ul>

<h5 class="fw-bold mt-4">6. Cookies et stockage local</h5>
<p>Le Site utilise uniquement des cookies nécessaires à son fonctionnement : cookie de session (rester connecté), jeton de sécurité contre les attaques CSRF et, si vous cochez « Se souvenir de moi », un cookie de connexion persistante. L'application web peut aussi enregistrer votre jeton de connexion dans le stockage local du navigateur et des fichiers pour le fonctionnement hors ligne. Nous n'utilisons ni cookies publicitaires ni outils de mesure d'audience.</p>

<h5 class="fw-bold mt-4">7. Sécurité</h5>
<p>Les mots de passe sont chiffrés, les échanges avec le Site passent par une connexion sécurisée (HTTPS) l'accès aux outils d'administration est réservé aux personnes habilitées, et les documents de vérification d'identité sont conservés dans un espace privé, consultable uniquement par l'équipe d'administration. Aucun système n'étant infaillible, nous vous recommandons d'utiliser un mot de passe unique et de ne jamais le communiquer.</p>

<h5 class="fw-bold mt-4">8. Vos droits</h5>
<p>Conformément à la législation de la République de Guinée relative à la protection des données à caractère personnel, vous pouvez :</p>
<ul>
<li>accéder à vos données et en obtenir une copie ;</li>
<li>les faire rectifier (la plupart sont modifiables directement depuis <a href="{{ route('profile.edit') }}">votre profil</a>) ;</li>
<li>demander leur suppression, ainsi que celle de votre compte ;</li>
<li>vous opposer à certains traitements ou retirer votre consentement, notamment pour la newsletter et les e-mails d'activité, depuis <a href="{{ route('profile.email-preferences') }}">vos préférences e-mail</a> ou le lien présent dans chaque e-mail.</li>
</ul>
<p>Pour exercer ces droits, écrivez-nous à {{ config('legal.email') }}. Nous pourrons vous demander de justifier de votre identité. Nous répondons dans un délai d'un mois.</p>

<h5 class="fw-bold mt-4">9. Mineurs</h5>
<p>Le Site est réservé aux personnes âgées d'au moins 18 ans. Nous ne collectons pas sciemment de données concernant des mineurs.</p>

<h5 class="fw-bold mt-4">10. Modifications</h5>
<p>Nous pouvons modifier cette politique. En cas de changement important, nous vous en informerons par e-mail ou par un message sur le Site. La date de mise à jour figure en haut de cette page.</p>

<p class="mt-4 mb-0">Voir aussi nos <a href="{{ route('legal.terms') }}">conditions générales d'utilisation</a>.</p>
</div>
</div>
</div>
</div>
@endsection
