<?php $__empty_1 = true; $__currentLoopData = $articles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<div class="col-12 col-sm-6 col-md-4 col-lg-3 article-item">
<div class="card article-card h-100 border-0 shadow-sm position-relative">
<div class="position-relative">
<img src="<?php echo e($item->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo'); ?>?fit=fill&w=300&h=200" alt="<?php echo e($item->titre); ?>" class="card-img-top" loading="lazy">
<span class="badge price-badge"><?php echo e(number_format($item->prix, 0, ',', ' ')); ?> <?php echo e($item->currency->value); ?></span>
<span class="badge position-absolute top-0 end-0 m-2 bg-<?php echo e($item->statut === 'vendu' ? 'danger' : 'success'); ?>"><?php echo e($item->statut === 'vendu' ? 'Vendu' : 'En vente'); ?></span>
</div>
<div class="card-body d-flex flex-column p-3">
<h6 class="title mb-1 text-truncate" title="<?php echo e($item->titre); ?>"><?php echo e($item->titre); ?></h6>
<p class="text-muted small mb-1 flex-grow-1"><?php echo e(Str::limit($item->description, 80)); ?></p>
<span class="small text-muted mb-2"><?php echo e($item->category->libelle ?? ''); ?></span>
<div class="d-flex gap-2 flex-wrap">
<a href="<?php echo e(route('articles.edit', $item->id)); ?>" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i> Modifier</a>
<?php if($item->statut !== 'vendu'): ?>
<form method="POST" action="<?php echo e(route('articles.toggle-status', $item->id)); ?>" class="d-inline">
<?php echo csrf_field(); ?>
<?php echo method_field('POST'); ?>
<button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Marquer comme vendu ?')"><i class="bx bx-check"></i> Vendu</button>
</form>
<?php endif; ?>
<form method="POST" action="<?php echo e(route('articles.destroy', $item->id)); ?>" class="d-inline">
<?php echo csrf_field(); ?>
<?php echo method_field('DELETE'); ?>
<button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette annonce ?')"><i class="bx bx-trash"></i></button>
</form>
</div>
</div>
</div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
<div class="col-12"><div class="alert alert-warning">Aucun article trouvé.</div></div>
<?php endif; ?>
<?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\partials\profile-articles.blade.php ENDPATH**/ ?>