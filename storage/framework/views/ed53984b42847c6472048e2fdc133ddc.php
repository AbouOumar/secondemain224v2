<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Mes favoris</h2>
    </div>

    <?php if($articles->isEmpty()): ?>
        <div class="text-center py-5">
            <i class='bx bx-bookmark' style="font-size:4rem;color:var(--gray-400);"></i>
            <p class="text-muted mt-3">Vous n'avez encore aucun article en favori.</p>
            <a href="<?php echo e(url('/')); ?>" class="btn btn-primary">Parcourir les annonces</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php $__currentLoopData = $articles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 article-item">
                    <div class="card article-card h-100 border-0 shadow-sm position-relative w-100 d-flex flex-column">
                        <div class="position-relative">
                            <a href="<?php echo e(route('articles.show', $item->slug)); ?>">
                                <img src="<?php echo e($item->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo'); ?>?fit=fill&w=300&h=200" class="card-img-top" loading="lazy" alt="<?php echo e($item->titre); ?>">
                            </a>
                            <span class="badge price-badge"><?php echo e(number_format($item->prix, 0, ',', ' ')); ?> <?php echo e($item->currency->value); ?></span>
                        </div>
                        <div class="card-body d-flex flex-column p-3 flex-grow-1">
                            <h6 class="title mb-1 text-truncate" title="<?php echo e($item->titre); ?>"><?php echo e($item->titre); ?></h6>
                            <p class="text-muted small mb-1 flex-grow-1"><?php echo e(Str::limit($item->description, 80)); ?></p>
                            <span class="small text-muted mb-2"><?php echo e($item->category->libelle ?? ''); ?></span>
                            <div class="d-flex gap-2 mt-auto">
                                <a href="<?php echo e(route('articles.show', $item->slug)); ?>" class="btn btn-sm btn-primary w-100">Voir</a>
                                <button class="btn btn-sm btn-outline-danger" onclick="unsave(<?php echo e($item->id); ?>, this)"><i class="bx bx-bookmark-minus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="mt-4">
            <?php echo e($articles->links()); ?>

        </div>
    <?php endif; ?>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function unsave(id, btn) {
    fetch('/saved/' + id, { method: 'POST', headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' } })
        .then(r => r.json())
        .then(d => { if (!d.saved) btn.closest('.article-item').remove(); });
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\profile\saved.blade.php ENDPATH**/ ?>