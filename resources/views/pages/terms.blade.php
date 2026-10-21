@php
    $deliveredHours = config('marketplace.escrow_auto_release_delivered_hours');
    $noDeliveryDays = config('marketplace.escrow_auto_release_no_delivery_days');
    $commissionRate = (float) config('marketplace.commission_rate');
@endphp
<h5 class="fw-bold">1. Objet</h5>
<p>Les présentes conditions générales d'utilisation (« CGU ») encadrent l'utilisation de {{ config('app.name') }} (le « Site »), accessible à l'adresse {{ url('/') }} et via ses applications. Le Site est une place de marché qui met en relation des personnes souhaitant vendre et acheter des biens d'occasion en Guinée, et propose des services de paiement sécurisé et de livraison. Il permet la vente et l'achat de biens de seconde main entre particuliers et vendeurs professionnels partenaires, la logistique et la livraison par des motards partenaires, la promotion des annonces, des outils de gestion pour les vendeurs et un service client. L'acceptation des CGU est obligatoire pour toute utilisation du Site.</p>
<p>Le Site est exploité par {{ collect([config('legal.company_name'), config('legal.legal_form'), config('legal.rccm') ? 'RCCM '.config('legal.rccm') : null, config('legal.address')])->filter()->implode(', ') }} (« nous »). Contact : {{ config('legal.email') }} · {{ config('legal.phone') }}.</p>
<p>En créant un compte ou en utilisant le Site, vous acceptez les présentes CGU et notre <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.</p>

<h5 class="fw-bold mt-4">2. Rôle du Site</h5>
<p>Le Site est un intermédiaire technique. Les ventes sont conclues directement entre l'acheteur et le vendeur : nous ne sommes ni propriétaire ni vendeur des articles proposés, sauf mention contraire. Le vendeur reste seul responsable de son annonce, de l'article vendu et de sa conformité à la description.</p>

<h5 class="fw-bold mt-4">3. Compte utilisateur</h5>
<ul>
<li>Le Site est destiné aux personnes âgées d'au moins 16 ans. Les mineurs doivent être encadrés par un parent ou un tuteur légal.</li>
<li>Vous vous engagez à fournir des informations exactes (nom, téléphone, e-mail) et à les tenir à jour.</li>
<li>Vous êtes responsable de la confidentialité de votre mot de passe et de toute activité réalisée depuis votre compte. Prévenez-nous immédiatement en cas d'utilisation non autorisée.</li>
<li>L'inscription est gratuite et nécessaire pour accéder à certaines fonctionnalités ; la simple consultation du Site est libre.</li>
<li>Un compte est personnel. Chaque utilisateur choisit un rôle : acheteur, vendeur, revendeur professionnel ou motard (livreur partenaire).</li>
<li>Les vendeurs peuvent demander le badge « vendeur vérifié » en transmettant une pièce d'identité ou un document professionnel. Ce badge atteste d'une vérification d'identité, pas de la qualité des articles.</li>
</ul>

<h5 class="fw-bold mt-4">4. Annonces</h5>
<ul>
<li>L'annonce doit décrire honnêtement l'article (état, défauts, prix) et être illustrée par des photos réelles de l'article. Le vendeur respecte les règles de prix et conditions de vente, garantit la disponibilité des biens, informe le Site de toute modification de stock et répond en temps utile aux demandes des acheteurs et du Site.</li>
<li>Les annonces peuvent être vérifiées par notre équipe avant ou après publication. Nous pouvons refuser, dépublier ou supprimer toute annonce contraire aux CGU ou à la loi.</li>
<li>Sont notamment interdits : les articles volés, illégaux ou contrefaits, la nourriture, les armes et munitions, les stupéfiants et médicaments, les produits reconnus nocifs pour l'être humain, les cheveux et organes humains ou artificiels, les animaux protégés, les contenus à caractère pornographique, haineux ou violent, les documents officiels, et plus généralement tout bien dont la vente est interdite en Guinée.</li>
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
<li>En cas de problème (article non reçu, non conforme à l'annonce), l'acheteur doit nous le signaler avant ces délais. Les fonds restent alors bloqués le temps de l'examen du litige. Selon le résultat, ils sont versés au vendeur ou remboursés à l'acheteur (voir l'article 7).</li>
<li>La commande devient ferme dès la confirmation du paiement. Un récapitulatif est alors communiqué à l'acheteur. L'acheteur fournit des informations exactes lors de l'achat et du paiement.</li>
<li>En confirmant la réception, l'acheteur atteste avoir reçu un article conforme : aucun retour ni remboursement n'est possible ensuite.</li>
@if($commissionRate > 0)
<li>Une commission de {{ rtrim(rtrim(number_format($commissionRate * 100, 2, ',', ' '), '0'), ',') }} % du prix de l'article est prélevée sur le montant versé au vendeur.</li>
@else
<li>Aucune commission n'est actuellement prélevée sur les ventes. Toute commission future (5 % du prix de l'article, taux négocié dans leur convention pour les magasins partenaires) sera annoncée à l'avance et ne s'appliquera qu'aux commandes passées après son entrée en vigueur.</li>
@endif
</ul>
<p>Le prix affiché à l'acheteur ne change pas : la commission éventuelle est supportée par le vendeur.</p>
<p>Pour votre sécurité, ne payez jamais un vendeur en dehors du Site : toute vente payée en dehors (espèces, transfert direct) n'est couverte ni par le séquestre ni par aucune garantie, et notre responsabilité n'est pas engagée. Payez sur le Site pour être protégé : votre argent n'est versé au vendeur qu'après réception d'un article conforme.</p>

<h5 class="fw-bold mt-4">7. Garantie de conformité, réclamations et rétractation</h5>
<ul>
<li>Nous agissons comme tiers de confiance : de la confirmation du paiement jusqu'à la confirmation de réception par l'acheteur, nous garantissons la conformité de l'article. Pendant ce délai, l'acheteur peut signaler tout défaut. Si l'article est conforme, le paiement est validé ; sinon nous organisons un retour, un échange ou un remboursement.</li>
<li><strong>Motifs de réclamation acceptés :</strong> article non reçu ; article différent de l'annonce (modèle, couleur, taille) ; défaut ou panne non mentionné dans l'annonce ; article incomplet. <strong>Motifs refusés :</strong> changement d'avis, usure normale décrite dans l'annonce, défaut visible sur les photos de l'annonce.</li>
<li>La réclamation doit être ouverte avant la confirmation de réception, avec des preuves (photos ou vidéo de l'article à la réception, description du problème). Le vendeur fournit les photos de l'annonce, ses photos avant envoi et sa version des faits ; le livreur peut être sollicité.</li>
<li>Notre équipe compare l'annonce et les preuves et tranche dans un délai de 72 heures : réclamation rejetée (vente confirmée, vendeur payé), réclamation acceptée (article retourné au vendeur, acheteur remboursé) ou accord amiable (réduction de prix acceptée par les deux parties).</li>
<li><strong>Rétractation :</strong> pendant la période de garantie, l'acheteur peut annuler la transaction et retourner l'article dans le même état, avec son emballage d'origine. Les frais de retour peuvent être à sa charge, sauf défaut confirmé. Celui qui est responsable du problème supporte les frais : si la réclamation est acceptée, le vendeur paie le retour et rembourse la livraison aller.</li>
<li>L'annulation par l'acheteur avant la remise de l'article, ou le défaut de remise de l'article par le vendeur dans le délai prévu, donne lieu à un remboursement intégral sur le moyen de paiement utilisé.</li>
</ul>

<h5 class="fw-bold mt-4">8. Livraison</h5>
<ul>
<li>Lorsque l'acheteur choisit la livraison, celle-ci est assurée par un motard partenaire. Les frais de livraison sont indiqués avant la commande.</li>
<li>Le vendeur s'engage à remettre l'article au motard en bon état et correctement emballé. L'acheteur s'engage à être joignable et présent à l'adresse indiquée.</li>
<li>Nous ne sommes pas responsables d'un retard raisonnable de livraison, qui peut varier selon la localisation. Les motards partenaires s'engagent à transporter l'article avec soin, à respecter le code de la route et à partager leur position pendant la livraison pour permettre le suivi.</li>
</ul>

<h5 class="fw-bold mt-4">9. Abonnements professionnels</h5>
<p>Les revendeurs professionnels peuvent souscrire un abonnement donnant accès à des fonctionnalités supplémentaires. Le contenu, le prix et la durée de chaque formule sont indiqués avant la souscription.</p>

<h5 class="fw-bold mt-4">10. Règles de conduite</h5>
<p>Il est interdit d'utiliser le Site pour : tromper ou escroquer d'autres utilisateurs ; publier des contenus illicites, injurieux ou mensongers ; harceler un autre utilisateur ; contourner le paiement sécurisé ; collecter les données d'autres utilisateurs ; créer de faux comptes ou de faux avis ; perturber le fonctionnement du Site.</p>
<p>Tout utilisateur peut signaler une annonce ou un compte. Nous examinons les signalements et pouvons, selon la gravité : avertir l'utilisateur, retirer un contenu, suspendre ou supprimer un compte.</p>
<p>Les sanctions montent par paliers : pour un article non conforme confirmé, avertissement, puis suspension de 30 jours, puis exclusion ; pour un article non remis à temps, avertissement, puis annonces masquées 15 jours, puis suspension ; pour une réclamation d'acheteur rejetée comme abusive, avertissement, puis paiement en ligne bloqué 30 jours, puis exclusion. Une fraude avérée (fausses preuves, article truqué) entraîne l'exclusion immédiate.</p>

<h5 class="fw-bold mt-4">11. Avis</h5>
<p>Après une transaction, acheteur et vendeur peuvent se noter. Les avis doivent être honnêtes et porter sur la transaction. Nous pouvons supprimer un avis injurieux, hors sujet ou manifestement faux.</p>

<h5 class="fw-bold mt-4">12. Communications par e-mail</h5>
<p>Nous vous envoyons les e-mails nécessaires au fonctionnement de votre compte (confirmation d'adresse, mot de passe). Les e-mails d'activité (offres, commandes, messages, livraisons, alertes) peuvent être désactivés dans <a href="{{ route('profile.email-preferences') }}">vos préférences e-mail</a>. La newsletter n'est envoyée qu'avec votre accord et vous pouvez vous désinscrire à tout moment.</p>

<h5 class="fw-bold mt-4">13. Responsabilité</h5>
<ul>
<li>Nous mettons tout en œuvre pour assurer le bon fonctionnement du Site, sans pouvoir garantir une disponibilité permanente. Des interruptions peuvent survenir pour maintenance ou en cas de panne.</li>
<li>Notre responsabilité est limitée aux dommages directs liés à une mauvaise exécution de nos obligations ; nous ne répondons pas des dommages indirects ni des litiges entre utilisateurs. Nous pouvons modifier, suspendre ou interrompre les services sans que notre responsabilité soit engagée. Nous ne sommes pas responsables du contenu des annonces, de la qualité ou de la conformité des articles, ni des échanges entre utilisateurs, sous réserve de nos obligations relatives au séquestre et à la gestion des litiges décrites aux articles 6 et 7.</li>
<li>Vous êtes responsable des contenus que vous publiez et vous nous garantissez contre toute réclamation de tiers liée à ces contenus.</li>
</ul>

<h5 class="fw-bold mt-4">14. Propriété intellectuelle</h5>
<p>Le Site, sa marque, son logo, son design, ses textes, ses images et son code nous appartiennent et sont protégés par le droit d'auteur ; toute reproduction ou utilisation non autorisée est interdite. En publiant une annonce, vous nous autorisez à afficher gratuitement vos textes et photos sur le Site et ses applications, pour la durée de publication de l'annonce. Vous garantissez détenir les droits sur ces contenus.</p>

<h5 class="fw-bold mt-4">15. Suspension et fermeture du compte</h5>
<p>Vous pouvez demander la fermeture de votre compte à tout moment en nous écrivant à {{ config('legal.email') }}, une fois vos commandes en cours terminées. Nous pouvons suspendre ou fermer un compte en cas de manquement aux CGU, après vous en avoir informé sauf urgence (fraude, danger pour d'autres utilisateurs). Le solde éventuel de votre portefeuille vous sera restitué, sauf s'il fait l'objet d'un litige en cours.</p>

<h5 class="fw-bold mt-4">16. Modification des CGU</h5>
<p>Nous pouvons modifier les CGU. Les utilisateurs sont informés de tout changement important par e-mail ou par un message sur le Site, avant son entrée en vigueur. Continuer à utiliser le Site après cette date vaut acceptation des nouvelles CGU.</p>

<h5 class="fw-bold mt-4">17. Droit applicable et litiges</h5>
<p>Les présentes CGU sont soumises au droit guinéen. En cas de différend, nous vous invitons à nous contacter d'abord à {{ config('legal.email') }} pour rechercher une solution amiable. À défaut, le litige sera porté devant les tribunaux compétents de Conakry.</p>

<p class="mt-4 mb-0">Voir aussi notre <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.</p>
