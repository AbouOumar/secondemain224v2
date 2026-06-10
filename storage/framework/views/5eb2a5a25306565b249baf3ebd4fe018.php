<?php $__env->startSection('content'); ?>
<div class="container py-4">
<h2 class="mb-4">Tableau de bord</h2>

<?php if(auth()->user()->role === 'revendeur_pro'): ?>
<div class="row g-4 mb-4">
<div class="col-6 col-md-3">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body text-center p-4">
<i class="bx bx-box" style="font-size: 2rem; color: #6366f1;"></i>
<h3 class="fw-bold mt-2 mb-0" id="totalArticles"><?php echo e($total); ?></h3>
<small class="text-muted">Total articles</small>
</div>
</div>
</div>
<div class="col-6 col-md-3">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body text-center p-4">
<i class="bx bx-store" style="font-size: 2rem; color: #10b981;"></i>
<h3 class="fw-bold mt-2 mb-0" id="articlesEnVente"><?php echo e($enVente); ?></h3>
<small class="text-muted">En vente</small>
</div>
</div>
</div>
<div class="col-6 col-md-3">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body text-center p-4">
<i class="bx bx-check-circle" style="font-size: 2rem; color: #ef4444;"></i>
<h3 class="fw-bold mt-2 mb-0" id="articlesVendus"><?php echo e($vendus); ?></h3>
<small class="text-muted">Vendus</small>
</div>
</div>
</div>
<div class="col-6 col-md-3">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body text-center p-4">
<i class="bx bx-line-chart" style="font-size: 2rem; color: #f59e0b;"></i>
<h3 class="fw-bold mt-2 mb-0" id="tauxVente"><?php echo e($taux); ?>%</h3>
<small class="text-muted">Taux de vente</small>
</div>
</div>
</div>
</div>

<!-- Enhanced stats for revendeur pro -->
<?php if(auth()->user()->role === 'revendeur_pro'): ?>
<div class="row g-4 mb-4">
<div class="col-6 col-md-3">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body text-center p-4">
<i class="bx bx-wallet" style="font-size: 2rem; color: #8b5cf6;"></i>
<h3 class="fw-bold mt-2 mb-0" id="revenusTotal">0</h3>
<small class="text-muted">Revenus (GNF)</small>
</div>
</div>
</div>
<div class="col-6 col-md-3">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body text-center p-4">
<i class="bx bx-truck" style="font-size: 2rem; color: #06b6d4;"></i>
<h3 class="fw-bold mt-2 mb-0" id="articlesAvecLivraison">0</h3>
<small class="text-muted">Avec livraison</small>
</div>
</div>
</div>
<div class="col-6 col-md-3">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body text-center p-4">
<i class="bx bx-cart-alt" style="font-size: 2rem; color: #f97316;"></i>
<h3 class="fw-bold mt-2 mb-0" id="articlesSansLivraison">0</h3>
<small class="text-muted">Sans livraison</small>
</div>
</div>
</div>
<div class="col-6 col-md-3">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body text-center p-4">
<i class="bx bx-boxes" style="font-size: 2rem; color: #10b981;"></i>
<h3 class="fw-bold mt-2 mb-0" id="articlesEnStock">0</h3>
<small class="text-muted">En stock</small>
</div>
</div>
</div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="d-flex gap-2 mb-4">
<a href="<?php echo e(route('articles.create')); ?>" class="btn btn-primary px-4"><i class="bx bx-plus-circle"></i> Nouvelle annonce</a>
<a href="<?php echo e(route('profile.listings')); ?>" class="btn btn-outline-secondary px-4">Mes annonces</a>
<a href="<?php echo e(route('profile.saved')); ?>" class="btn btn-outline-secondary px-4">Mes favoris</a>
<a href="<?php echo e(route('profile.edit')); ?>" class="btn btn-outline-secondary px-4">Mon profil</a>
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

<?php if($recentOrders->count() > 0): ?>
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-header bg-white border-0 pt-4 px-4">
<h5 class="fw-bold mb-0">Commandes récentes</h5>
</div>
<div class="card-body p-4">
<div class="table-responsive">
<table class="table">
<thead><tr><th>Réf.</th><th>Article</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
<tbody>
<?php $__currentLoopData = $recentOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<tr>
<td><?php echo e($order->reference); ?></td>
<td><?php echo e($order->article->titre ?? '—'); ?></td>
<td><span class="badge bg-<?php echo e($order->status === 'livre' ? 'success' : ($order->status === 'paye' ? 'warning' : 'secondary')); ?>"><?php echo e($order->status); ?></span></td>
<td><?php echo e(number_format($order->total, 0, ',', ' ')); ?> GNF</td>
<td><?php echo e($order->created_at->format('d/m/Y')); ?></td>
</tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load enhanced stats for revendeur pro users
    if ('<?php echo e(auth()->user()->role); ?>' === 'revendeur_pro') {
        fetch('/api/v1/seller/stats')
            .then(response => response.json())
            .then(data => {
                if (data.revenus !== undefined) {
                    document.getElementById('revenusTotal').textContent = new Intl.NumberFormat('de-DE').format(data.revenus) + ' GNF';
                }
                if (data.articles_avec_livraison !== undefined) {
                    document.getElementById('articlesAvecLivraison').textContent = data.articles_avec_livraison;
                }
                if (data.articles_sans_livraison !== undefined) {
                    document.getElementById('articlesSansLivraison').textContent = data.articles_sans_livraison;
                }
                if (data.articles_en_stock !== undefined) {
                    document.getElementById('articlesEnStock').textContent = data.articles_en_stock;
                }
                if (data.articles_rupture_stock !== undefined) {
                    // We could add a stock alert badge here
                    const stockBadge = document.createElement('span');
                    stockBadge.className = 'badge bg-danger text-white ms-2';
                    stockBadge.textContent = data.articles_rupture_stock + ' en rupture';
                    const enStockElement = document.getElementById('articlesEnStock');
                    enStockElement.parentNode.insertBefore(stockBadge, enStockElement.nextSibling);
                }
            })
            .catch(error => {
                console.error('Error loading enhanced stats:', error);
            });
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\profile\dashboard.blade.php ENDPATH**/ ?>