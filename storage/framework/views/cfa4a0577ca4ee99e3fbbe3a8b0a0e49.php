<?php $__env->startSection('content'); ?>
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-8">
<h2 class="fw-bold mb-4">Contactez-nous</h2>

<?php if(session('success')): ?>
<div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body p-5">
<form method="POST" action="<?php echo e(route('contact.send')); ?>">
<?php echo csrf_field(); ?>
<div class="mb-3">
<label class="form-label fw-semibold">Nom complet</label>
<input type="text" name="name" class="form-control" style="border-radius: 10px; height: 48px;" required>
</div>
<div class="mb-3">
<label class="form-label fw-semibold">Email</label>
<input type="email" name="email" class="form-control" style="border-radius: 10px; height: 48px;" required>
</div>
<div class="mb-3">
<label class="form-label fw-semibold">Message</label>
<textarea name="message" rows="5" class="form-control" style="border-radius: 10px;" required></textarea>
</div>
<button type="submit" class="btn btn-primary px-5" style="border-radius: 10px;">Envoyer</button>
</form>
</div>
</div>
</div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\contact.blade.php ENDPATH**/ ?>