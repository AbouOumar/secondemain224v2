@extends('layouts.app')

@php
    $deliveredHours = config('marketplace.escrow_auto_release_delivered_hours');
    $noDeliveryDays = config('marketplace.escrow_auto_release_no_delivery_days');
    $commissionRate = (float) config('marketplace.commission_rate');
@endphp

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-9">
<h1 class="fw-bold mb-2">Conditions générales d'utilisation</h1>
<p class="text-muted mb-4">Dernière mise à jour : {{ config('legal.updated_at') }}</p>

<div class="card border-0 shadow-sm p-4 mb-4 legal" style="border-radius: 18px;">

<h5 class="fw-bold">1. Objet</h5>
<p>Les présentes conditions générales d'utilisation (« CGU ») encadrent l'utilisation de {{ config('app.name') }} (le « Site »), accessible à l'adresse {{ url('/') }} et via ses applications. Le Site est une place de marché qui met en relation des personnes souhaitant vendre et acheter des biens d'occasion en Guinée, et propose des services de paiement sécurisé et de livraison.</p>
<p>Le Site est exploité par {{ config('legal.company_name') }}, {{ config('legal.legal_form') }}, RCCM {{ config('legal.rccm') }}, {{ config('legal.address') }} (« nous »). Contact : {{ config('legal.email') }} · {{ config('legal.phone') }}.</p>
<p>En créant un compte ou en utilisant le Site, vous acceptez les présentes CGU et notre <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.</p>

<h5 class="fw-bold mt-4">2. Rôle du Site</h5>
<p>Le Site est un intermédiaire technique. Les ventes sont conclues directement entre l'acheteur et le vendeur : nous ne sommes ni propriétaire ni vendeur des articles proposés, sauf mention contraire. Le vendeur reste seul responsable de son annonce, de l'article vendu et de sa conformité à la description.</p>

<h5 class="fw-bold mt-4">3. Compte utilisateur</h5>
<ul>
<li>Le Site est réservé aux personnes âgées d'au moins 18 ans, capables de contracter.</li>
<li>Vous vous engagez à fournir des informations exactes (nom, téléphone, e-mail) et à les tenir à jour.</li>
<li>Vous êtes responsable de la confidentialité de votre mot de passe et de toute activité réalisée depuis votre compte. Prévenez-nous immédiatement en cas d'utilisation non autorisée.</li>
<li>Un compte est personnel. Chaque utilisateur choisit un rôle : acheteur, vendeur, revendeur professionnel ou motard (livreur partenaire).</li>
<li>Les vendeurs peuvent demander le badge « vendeur vérifié » en transmettant une pièce d'identité ou un document professionnel. Ce badge atteste d'une vérification d'identité, pas de la qualité des articles.</li>
</ul>

<h5 class="fw-bold mt-4">4. Annonces</h5>
<ul>
<li>L'annonce doit décrire honnêtement l'article (état, défauts, prix) et être illustrée par des photos réelles de l'article.</li>
<li>Les annonces peuvent être vérifiées par notre équipe avant ou après publication. Nous pouvons refuser, dépublier ou supprimer toute annonce contraire aux CGU ou à la loi.</li>
<li>Sont notamment interdits : les articles volés ou contrefaits, les armes et munitions, les stupéfiants et médicaments, les produits dangereux, les animaux protégés, les contenus à caractère pornographique, haineux ou violent, les documents officiels, et plus généralement tout bien dont la vente est interdite en Guinée.</li>
<li>Option payante « Boost » : une annonce peut être mise en avant pour une durée choisie, au tarif de {{ number_format(\App\Services\Article\BoostService::PRIX_PAR_HEURE, 0, ',', ' ') }} GNF par heure, débité du portefeuille. Le boost n'est pas remboursable, sauf si l'annonce est retirée par nos soins pour une raison qui ne vous est pas imputable.</li>
</ul>

<h5 class="fw-bold mt-4">5. Offres et négociation</h5>
<p>L'acheteur peut proposer un prix au vendeur, qui peut l'accepter, la refuser ou faire une contre-offre. Une offre sans réponse expire après {{ \App\Services\Offer\OfferService::EXPIRY_HOURS }} heures. Une offre acceptée engage les deux parties à conclure la vente au prix convenu.</p>

<h5 class="fw-bold mt-4">6. Commande et paiement sécurisé (séquestre)</h5>
<ul>
<li>Le paiement s'effectue sur le Site par les moyens proposés (Orange Money, MTN Mobile Money, carte bancaire via notre prestataire de paiement, ou solde du portefeuille). Les prix sont indiqués en francs guinéens (GNF).</li>
<li>Après paiement, la somme est <strong>conservée en séquestre</strong> : elle n'est pas versée immédiatement au vendeur.</li>
<li>Elle est versée au vendeur lorsque l'acheteur confirme la bonne réception de l'article.</li>
<li>Sans confirmation ni réclamation de l'acheteur, elle est versée automatiquement {{ $deliveredHours }} heures après la livraison effectuée, ou, pour une commande sans livraison, {{ $noDeliveryDays }} jours après le paiement.</li>
<li>En cas de problème (article non reçu, non conforme à l'annonce), l'acheteur doit nous le signaler avant ces délais. Les fonds restent alors bloqués le temps de l'examen du litige. Selon le résultat, ils sont versés au vendeur ou remboursés à l'acheteur.</li>
@if($commissionRate > 0)
<li>Une commission de {{ rtrim(rtrim(number_format($commissionRate * 100, 2, ',', ' '), '0'), ',') }} % du prix de l'article est prélevée sur le montant versé au vendeur.</li>
@else
<li>Aucune commission n'est actuellement prélevée sur les ventes. Toute commission future sera annoncée à l'avance et ne s'appliquera qu'aux commandes passées après son entrée en vigueur.</li>
@endif
</ul>
<p>Pour votre sécurité, ne payez jamais un vendeur en dehors du Site : un paiement hors plateforme n'est pas couvert par le séquestre.</p>

<h5 class="fw-bold mt-4">7. Livraison</h5>
<ul>
<li>Lorsque l'acheteur choisit la livraison, celle-ci est assurée par un motard partenaire. Les frais de livraison sont indiqués avant la commande.</li>
<li>Le vendeur s'engage à remettre l'article au motard en bon état et correctement emballé. L'acheteur s'engage à être joignable et présent à l'adresse indiquée.</li>
<li>Les motards partenaires s'engagent à transporter l'article avec soin, à respecter le code de la route et à partager leur position pendant la livraison pour permettre le suivi.</li>
</ul>

<h5 class="fw-bold mt-4">8. Abonnements professionnels</h5>
<p>Les revendeurs professionnels peuvent souscrire un abonnement donnant accès à des fonctionnalités supplémentaires. Le contenu, le prix et la durée de chaque formule sont indiqués avant la souscription.</p>

<h5 class="fw-bold mt-4">9. Règles de conduite</h5>
<p>Il est interdit d'utiliser le Site pour : tromper ou escroquer d'autres utilisateurs ; publier des contenus illicites, injurieux ou mensongers ; harceler un autre utilisateur ; contourner le paiement sécurisé ; collecter les données d'autres utilisateurs ; créer de faux comptes ou de faux avis ; perturber le fonctionnement du Site.</p>
<p>Tout utilisateur peut signaler une annonce ou un compte. Nous examinons les signalements et pouvons, selon la gravité : avertir l'utilisateur, retirer un contenu, suspendre ou supprimer un compte.</p>

<h5 class="fw-bold mt-4">10. Avis</h5>
<p>Après une transaction, acheteur et vendeur peuvent se noter. Les avis doivent être honnêtes et porter sur la transaction. Nous pouvons supprimer un avis injurieux, hors sujet ou manifestement faux.</p>

<h5 class="fw-bold mt-4">11. Communications par e-mail</h5>
<p>Nous vous envoyons les e-mails nécessaires au fonctionnement de votre compte (confirmation d'adresse, mot de passe). Les e-mails d'activité (offres, commandes, messages, livraisons, alertes) peuvent être désactivés dans <a href="{{ route('profile.email-preferences') }}">vos préférences e-mail</a>. La newsletter n'est envoyée qu'avec votre accord et vous pouvez vous désinscrire à tout moment.</p>

<h5 class="fw-bold mt-4">12. Responsabilité</h5>
<ul>
<li>Nous mettons tout en œuvre pour assurer le bon fonctionnement du Site, sans pouvoir garantir une disponibilité permanente. Des interruptions peuvent survenir pour maintenance ou en cas de panne.</li>
<li>Nous ne sommes pas responsables du contenu des annonces, de la qualité ou de la conformité des articles, ni des échanges entre utilisateurs, sous réserve de nos obligations relatives au séquestre et à la gestion des litiges décrites à l'article 6.</li>
<li>Vous êtes responsable des contenus que vous publiez et vous nous garantissez contre toute réclamation de tiers liée à ces contenus.</li>
</ul>

<h5 class="fw-bold mt-4">13. Propriété intellectuelle</h5>
<p>Le Site, sa marque, son logo et ses éléments graphiques nous appartiennent. En publiant une annonce, vous nous autorisez à afficher gratuitement vos textes et photos sur le Site et ses applications, pour la durée de publication de l'annonce. Vous garantissez détenir les droits sur ces contenus.</p>

<h5 class="fw-bold mt-4">14. Suspension et fermeture du compte</h5>
<p>Vous pouvez demander la fermeture de votre compte à tout moment en nous écrivant à {{ config('legal.email') }}, une fois vos commandes en cours terminées. Nous pouvons suspendre ou fermer un compte en cas de manquement aux CGU, après vous en avoir informé sauf urgence (fraude, danger pour d'autres utilisateurs). Le solde éventuel de votre portefeuille vous sera restitué, sauf s'il fait l'objet d'un litige en cours.</p>

<h5 class="fw-bold mt-4">15. Modification des CGU</h5>
<p>Nous pouvons modifier les CGU. Les utilisateurs sont informés de tout changement important par e-mail ou par un message sur le Site, avant son entrée en vigueur. Continuer à utiliser le Site après cette date vaut acceptation des nouvelles CGU.</p>

<h5 class="fw-bold mt-4">16. Droit applicable et litiges</h5>
<p>Les présentes CGU sont soumises au droit guinéen. En cas de différend, nous vous invitons à nous contacter d'abord à {{ config('legal.email') }} pour rechercher une solution amiable. À défaut, le litige sera porté devant les tribunaux compétents de Conakry.</p>

<p class="mt-4 mb-0">Voir aussi notre <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.</p>
</div>
</div>
</div>
</div>
@endsection
