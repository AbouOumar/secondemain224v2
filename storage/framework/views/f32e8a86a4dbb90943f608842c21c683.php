<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <?php if(session('error')): ?>
        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
    <?php endif; ?>
    <?php if(session('info')): ?>
        <div class="alert alert-info"><?php echo e(session('info')); ?></div>
    <?php endif; ?>
    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0">Récapitulatif de la commande</h4>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex gap-3 mb-4">
                        <?php if($order->article->images->count() > 0): ?>
                            <img src="<?php echo e($order->article->images->first()->url); ?>?fit=fill&w=120&h=120" class="rounded" style="width:100px;height:100px;object-fit:cover;">
                        <?php endif; ?>
                        <div>
                            <h5 class="fw-bold mb-1"><?php echo e($order->article->titre); ?></h5>
                            <p class="text-muted small mb-2">Réf: <?php echo e($order->reference); ?></p>
                            <p class="mb-1"><strong>Prix article :</strong> <?php echo e(number_format($order->prix_article, 0, ',', ' ')); ?> GNF</p>
                            <?php if($order->with_delivery): ?>
                                <p class="mb-1"><strong>Livraison :</strong> <?php echo e(number_format($order->delivery_prix, 0, ',', ' ')); ?> GNF</p>
                            <?php endif; ?>
                            <hr>
                            <p class="fs-5 fw-bold mb-0" style="color:var(--primary);">
                                Total : <?php echo e(number_format($order->total, 0, ',', ' ')); ?> GNF
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0">Moyen de paiement</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">Sélectionnez votre moyen de paiement et entrez votre numéro de téléphone.</p>

                    <form method="POST" action="<?php echo e(route('payment.process.djomy', $order)); ?>" id="paymentForm">
                        <?php echo csrf_field(); ?>

                        <div class="mb-4">
                            <label class="form-label fw-medium">Numéro de téléphone *</label>
                            <input type="tel" name="phone" class="form-control form-control-lg"
                                   value="<?php echo e(old('phone', auth()->user()->phone)); ?>"
                                   placeholder="Ex: 622 30 00 01" required>
                            <small class="text-muted">Vous recevrez une notification sur ce numéro pour confirmer le paiement.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">Méthode de paiement</label>
                            <div class="row g-3" id="paymentMethods">
                                <div class="col-md-4">
                                    <div class="card border-2 payment-method selected" data-method="djomy_om" onclick="selectMethod(this, 'djomy_om')">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2" style="font-size:2rem;">📱</div>
                                            <h6 class="mb-1">Orange Money</h6>
                                            <small class="text-muted">Via Djomy</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-2 payment-method" data-method="djomy_momo" onclick="selectMethod(this, 'djomy_momo')">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2" style="font-size:2rem;">📱</div>
                                            <h6 class="mb-1">MTN MoMo</h6>
                                            <small class="text-muted">Via Djomy</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-2 payment-method" data-method="djomy_card" onclick="selectMethod(this, 'djomy_card')">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2" style="font-size:2rem;">💳</div>
                                            <h6 class="mb-1">Carte bancaire</h6>
                                            <small class="text-muted">Visa, MasterCard</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class='bx bx-lock'></i> Payer <?php echo e(number_format($order->total, 0, ',', ' ')); ?> GNF
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="<?php echo e(route('articles.show', $order->article->slug)); ?>" class="text-muted small">Annuler et retourner à l'annonce</a>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
.payment-method { cursor: pointer; transition: all 0.2s; }
.payment-method:hover { border-color: var(--primary) !important; transform: translateY(-2px); }
.payment-method.selected { border-color: var(--primary) !important; background: #f0fdf4; }
.payment-method.selected .card-body { color: var(--primary); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function selectMethod(el, method) {
    document.querySelectorAll('.payment-method').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\payment\show.blade.php ENDPATH**/ ?>