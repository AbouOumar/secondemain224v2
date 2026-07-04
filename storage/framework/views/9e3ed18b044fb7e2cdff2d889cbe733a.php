<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-4">
                    <h4 class="card-title mb-0">Gestion de l'abonnement</h4>
                </div>
                <div class="card-body p-4">
                    <?php if(auth()->user()->subscription): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">Votre abonnement actuel</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <h6 class="mb-2"><?php echo e(ucfirst(auth()->user()->subscription->plan_type)); ?></h6>
                                        <p class="mb-2"><strong>Statut :</strong> 
                                            <span class="badge bg-<?php echo e(auth()->user()->subscription->isActive() ? 'success' : 'danger'); ?>">
                                                <?php echo e(ucfirst(auth()->user()->subscription->status)); ?>

                                            </span>
                                        </p>
                                        <p class="mb-1"><strong>Période :</strong> 
                                            <?php echo e(auth()->user()->subscription->starts_at->format('d/m/Y')); ?> - 
                                            <?php echo e(auth()->user()->subscription->ends_at->format('d/m/Y')); ?>

                                        </p>
                                        <?php if(auth()->user()->subscription->isActive()): ?>
                                            <form method="POST" action="<?php echo e(route('api.subscription.cancel')); ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    Annuler l'abonnement
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Expiré</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <h6 class="mb-2">Avantages de votre plan</h6>
                                        <ul class="list-unstyled mb-0">
                                            <?php if(auth()->user()->subscription->plan_type === 'basic'): ?>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Annonces illimitées</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Gestion de base des commandes</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Support standard</li>
                                            <?php elseif(auth()->user()->subscription->plan_type === 'pro'): ?>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Toutes les fonctionnalités de base</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Badge revendeur vérifié</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Outils de promotion avancés</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Statistiques détaillées</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Gestion de stock</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Support prioritaire</li>
                                            <?php elseif(auth()->user()->subscription->plan_type === 'enterprise'): ?>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Toutes les fonctionnalités Pro</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Compte dédié gestionnaire</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> API personnelle</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Support 24/7</li>
                                                <li><i class='bx bx-check-circle text-success me-2'></i> Formation personnalisée</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class='bx bx-wallet fs-1 text-muted mb-4'></i>
                            <h5 class="mb-4">Choisissez votre plan d'abonnement</h5>
                            <p class="text-muted mb-5">Accédez à des fonctionnalités professionnelles pour développer votre activité</p>
                        </div>
                    <?php endif; ?>

                    <!-- Subscription Plans -->
                    <div class="row g-4">
                        <!-- Basic Plan -->
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100 border-<?php echo e(auth()->user()->subscription?->plan_type === 'basic' ? 'border-primary' : ''); ?>">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3">Basique</h6>
                                    <p class="text-muted mb-3">Parfait pour commencer</p>
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="h2 fw-bold me-2">0</span>
                                        <span class="text-muted">GNF/mois</span>
                                    </div>
                                    <ul class="list-unstyled mb-4">
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Annonces illimitées</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Gestion de base des commandes</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Support standard</li>
                                    </ul>
                                    <?php if(!auth()->user()->subscription || auth()->user()->subscription->plan_type !== 'basic'): ?>
                                        <button type="button" class="btn btn-primary w-100 select-plan" data-plan="basic">Sélectionner ce plan</button>
                                    <?php else: ?>
                                        <span class="btn btn-outline-primary w-100">Actif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pro Plan -->
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100 border-<?php echo e(auth()->user()->subscription?->plan_type === 'pro' ? 'border-primary' : ''); ?>">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3">Pro</h6>
                                    <p class="text-muted mb-3">Pour les vendeurs sérieux</p>
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="h2 fw-bold me-2">5000</span>
                                        <span class="text-muted">GNF/mois</span>
                                    </div>
                                    <ul class="list-unstyled mb-4">
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Toutes les fonctionnalités de base</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Badge revendeur vérifié</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Outils de promotion avancés</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Statistiques détaillées</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Gestion de stock</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Support prioritaire</li>
                                    </ul>
                                    <?php if(!auth()->user()->subscription || auth()->user()->subscription->plan_type !== 'pro'): ?>
                                        <button type="button" class="btn btn-primary w-100 select-plan" data-plan="pro">Sélectionner ce plan</button>
                                    <?php else: ?>
                                        <span class="btn btn-outline-primary w-100">Actif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Enterprise Plan -->
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100 border-<?php echo e(auth()->user()->subscription?->plan_type === 'enterprise' ? 'border-primary' : ''); ?>">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3">Entreprise</h6>
                                    <p class="text-muted mb-3">Pour les grandes opérations</p>
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="h2 fw-bold me-2">15000</span>
                                        <span class="text-muted">GNF/mois</span>
                                    </div>
                                    <ul class="list-unstyled mb-4">
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Toutes les fonctionnalités Pro</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Compte dédié gestionnaire</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> API personnelle</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Support 24/7</li>
                                        <li><i class='bx bx-check-circle text-success me-2'></i> Formation personnalisée</li>
                                    </ul>
                                    <?php if(!auth()->user()->subscription || auth()->user()->subscription->plan_type !== 'enterprise'): ?>
                                        <button type="button" class="btn btn-primary w-100 select-plan" data-plan="enterprise">Sélectionner ce plan</button>
                                    <?php else: ?>
                                        <span class="btn btn-outline-primary w-100">Actif</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    document.querySelectorAll('.select-plan').forEach(btn => {
        btn.addEventListener('click', function() {
            const plan = this.getAttribute('data-plan');
            if (!confirm(`Êtes-vous sûr de vouloir souscrire au plan ${plan.toUpperCase()} ?`)) return;

            const submitBtn = this;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Traitement...';

            fetch('<?php echo e(route("api.subscription.store")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    plan_type: plan,
                    payment_method: 'wallet',
                }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.subscription) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de la souscription');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Sélectionner ce plan';
                }
            })
            .catch(() => {
                alert('Erreur réseau. Veuillez réessayer.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sélectionner ce plan';
            });
        });
    });

    const cancelForm = document.querySelector('form[action*="subscription.cancel"]');
    if (cancelForm) {
        cancelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')) return;

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Annulation...';

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.message) {
                    location.reload();
                }
            })
            .catch(() => {
                alert('Erreur réseau. Veuillez réessayer.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Annuler l\'abonnement';
            });
        });
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\seller\pro\subscription.blade.php ENDPATH**/ ?>