<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-3">Tableau de bord Motard</h2>
            <p class="text-muted">Gérez vos livraisons et votre disponibilité</p>
        </div>
    </div>

    <!-- Status Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Statut de disponibilité</h5>
                        <form id="statusForm">
                            <select class="form-select form-select-sm" name="status" id="statusSelect">
                                <option value="en_ligne" <?php echo e(auth()->user()->rider_status === 'en_ligne' ? 'selected' : ''); ?>>En ligne</option>
                                <option value="occupe" <?php echo e(auth()->user()->rider_status === 'occupe' ? 'selected' : ''); ?>>Occupé</option>
                                <option value="hors_ligne" <?php echo e(auth()->user()->rider_status === 'hors_ligne' ? 'selected' : ''); ?>>Hors ligne</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm ms-2">Mettre à jour</button>
                        </form>
                    </div>
                    
                    <div class="alert alert-info small" id="statusMessage">
                        Votre statut actuel : <strong><?php echo e(ucfirst(auth()->user()->rider_status)); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($earnings)): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 18px;">
                <h6 class="text-muted mb-1">Aujourd'hui</h6>
                <h4 class="fw-bold mb-0" style="color:var(--primary);"><?php echo e(number_format($earnings['aujourd_hui'], 0, ',', ' ')); ?> GNF</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 18px;">
                <h6 class="text-muted mb-1">Cette semaine</h6>
                <h4 class="fw-bold mb-0" style="color:var(--primary);"><?php echo e(number_format($earnings['cette_semaine'], 0, ',', ' ')); ?> GNF</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 18px;">
                <h6 class="text-muted mb-1">Ce mois</h6>
                <h4 class="fw-bold mb-0" style="color:var(--primary);"><?php echo e(number_format($earnings['ce_mois'], 0, ',', ' ')); ?> GNF</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 18px;">
                <h6 class="text-muted mb-1">Cette année</h6>
                <h4 class="fw-bold mb-0" style="color:var(--primary);"><?php echo e(number_format($earnings['cette_annee'], 0, ',', ' ')); ?> GNF</h4>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pending Deliveries -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Livraisons en attente</h5>
                </div>
                <div class="card-body p-0">
                    <?php if($pendingDeliveries->isEmpty()): ?>
                        <div class="text-center py-4">
                            <i class='bx bx-truck text-muted' style='font-size: 3rem;'></i>
                            <p class="mt-3 text-muted">Aucune livraison en attente pour le moment</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php $__currentLoopData = $pendingDeliveries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="list-group-item border-0 pb-3 pt-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold"><?php echo e($delivery->order->article->titre); ?></h6>
                                            <p class="mb-1 text-muted small">
                                                De : <?php echo e($delivery->order->buyer->name ?? 'Client'); ?><br>
                                                À : <?php echo e($delivery->order->seller->name ?? 'Vendeur'); ?>

                                            </p>
                                            <?php if($delivery->order->article->delivery_prix): ?>
                                                <span class="badge bg-info text-white fs-6">
                                                    Livraison : <?php echo e(number_format($delivery->order->article->delivery_prix, 0, ',', ' ')); ?> GNF
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <form method="POST" action="<?php echo e(route('deliveries.accept', $delivery->id)); ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    Accepter
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <small class="text-muted"><?php echo e($delivery->created_at->diffForHumans()); ?></small>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Deliveries -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Mes livraisons en cours</h5>
                </div>
                <div class="card-body p-0">
                    <?php if($currentDeliveries->isEmpty()): ?>
                        <div class="text-center py-4">
                            <i class='bx bx-check-circle text-muted' style='font-size: 3rem;'></i>
                            <p class="mt-3 text-muted">Aucune livraison en cours</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php $__currentLoopData = $currentDeliveries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="list-group-item border-0 pb-3 pt-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold"><?php echo e($delivery->order->article->titre); ?></h6>
                                            <p class="mb-1 text-muted small">
                                                De : <?php echo e($delivery->order->buyer->name ?? 'Client'); ?><br>
                                                À : <?php echo e($delivery->order->seller->name ?? 'Vendeur'); ?>

                                            </p>
                                            <?php if($delivery->status === 'acceptee'): ?>
                                                <span class="badge bg-warning text-dark fs-6">Acceptée</span>
                                            <?php elseif($delivery->status === 'en_cours'): ?>
                                                <span class="badge bg-primary text-white fs-6">En cours</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <?php if($delivery->status === 'acceptee'): ?>
                                                <form method="POST" action="<?php echo e(route('deliveries.pickup', $delivery->id)); ?>" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                                        Marquer comme récupéré
                                                    </button>
                                                </form>
                                            <?php elseif($delivery->status === 'en_cours'): ?>
                                                <form method="POST" action="<?php echo e(route('deliveries.complete', $delivery->id)); ?>" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        Terminer la livraison
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <small class="text-muted"><?php echo e($delivery->updated_at->diffForHumans()); ?></small>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery History -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Historique des livraisons</h5>
                </div>
                <div class="card-body p-0">
                    <?php if($historyDeliveries->isEmpty()): ?>
                        <div class="text-center py-4">
                            <i class='bx bx-history text-muted' style='font-size: 3rem;'></i>
                            <p class="mt-3 text-muted">Aucune livraison effectuée pour le moment</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" class="ps-4">Article</th>
                                        <th scope="col">Client</th>
                                        <th scope="col">Vendeur</th>
                                        <th scope="col">Date</th>
                                        <th scope="col" class="pe-4">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $historyDeliveries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $delivery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="ps-4 align-middle">
                                                <div class="d-flex align-items-center">
                                                    <?php if($delivery->order->article->images->count() > 0): ?>
                                                        <img src="<?php echo e($delivery->order->article->images->first()->url); ?>" alt="<?php echo e($delivery->order->article->titre); ?>" width="40" height="40" class="me-2 rounded">
                                                    <?php endif; ?>
                                                    <div>
                                                        <small class="fw-bold d-block"><?php echo e($delivery->order->article->titre); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle"><?php echo e($delivery->order->buyer->name ?? 'Client'); ?></td>
                                            <td class="align-middle"><?php echo e($delivery->order->seller->name ?? 'Vendeur'); ?></td>
                                            <td class="align-middle"><?php echo e($delivery->completed_at->format('d/m/Y H:i')); ?></td>
                                            <td class="align-middle pe-4">
                                                <span class="badge bg-success">Effectuée</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center pt-3">
                            <?php echo e($historyDeliveries->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusForm = document.getElementById('statusForm');
    const statusSelect = document.getElementById('statusSelect');
    const statusMessage = document.getElementById('statusMessage');
    
    if (statusForm) {
        statusForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const status = statusSelect.value;
            
            fetch('<?php echo e(route('deliveries.setStatus')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    statusMessage.innerHTML = '<div class="alert alert-success small">'+data.message+'</div>';
                    setTimeout(() => {
                        statusMessage.innerHTML = '<div class="alert alert-info small">Votre statut actuel : <strong>'+ ucfirst(status) +'</strong></div>';
                    }, 3000);
                }
            })
            .catch(error => {
                statusMessage.innerHTML = '<div class="alert alert-danger small">Erreur lors de la mise à jour du statut</div>';
            });
        });
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\motard\dashboard.blade.php ENDPATH**/ ?>