@extends('layouts.app')

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-9">
<h1 class="fw-bold mb-2">Supprimer mon compte</h1>
<p class="text-muted mb-4">{{ config('app.name') }} · Dernière mise à jour : {{ config('legal.updated_at') }}</p>

<div class="card border-0 shadow-sm p-4 mb-4 legal" style="border-radius: 18px;">

<h5 class="fw-bold">Depuis l'application</h5>
<ol>
<li>Ouvrez l'application et connectez-vous.</li>
<li>Allez dans <strong>Compte &gt; Supprimer mon compte</strong>.</li>
<li>Lisez le récapitulatif, saisissez votre mot de passe (inutile si vous vous êtes connecté avec Google) puis confirmez.</li>
</ol>
<p>La suppression est immédiate et définitive.</p>

<h5 class="fw-bold mt-4">Si vous n'avez plus accès à l'application</h5>
<p>Écrivez-nous depuis la <a href="{{ route('contact') }}">page de contact</a> ou à {{ config('legal.email') }}, en indiquant l'adresse e-mail ou le numéro de téléphone de votre compte. Nous pourrons vous demander de justifier de votre identité, et nous répondons dans un délai d'un mois.</p>

<h5 class="fw-bold mt-4">Ce qui est supprimé</h5>
<ul>
<li>votre nom, votre adresse e-mail, votre numéro de téléphone, votre photo et votre localisation ;</li>
<li>votre mot de passe et vos connexions (y compris Google) sur tous vos appareils, ainsi que les notifications associées ;</li>
<li>vos pièces d'identité et selfies de vérification ;</li>
<li>votre boutique, vos favoris, vos alertes de recherche et vos préférences ;</li>
<li>vos annonces, qui sont retirées du Site.</li>
</ul>

<h5 class="fw-bold mt-4">Ce qui est conservé</h5>
<ul>
<li>l'historique des commandes, paiements et livraisons terminés, de façon anonyme (« Compte supprimé »), car nous devons le conserver pour nos obligations comptables et pour l'autre partie à la transaction ;</li>
<li>les messages échangés avec d'autres utilisateurs, qui n'affichent plus que « Compte supprimé » ;</li>
<li>les avis laissés ou reçus, rattachés à ce compte anonymisé.</li>
</ul>

<h5 class="fw-bold mt-4">Quand la suppression est refusée</h5>
<p>Pour protéger les autres utilisateurs, le compte ne peut pas être supprimé tant que :</p>
<ul>
<li>une commande payée est en cours (réception à confirmer) ;</li>
<li>une livraison que vous avez acceptée n'est pas terminée ;</li>
<li>votre portefeuille contient de l'argent : contactez-nous pour le récupérer.</li>
</ul>
<p>Les commandes non payées et les offres en cours sont annulées automatiquement.</p>

<p class="mt-4 mb-0">Voir aussi notre <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.</p>

</div>
</div>
</div>
</div>
@endsection
