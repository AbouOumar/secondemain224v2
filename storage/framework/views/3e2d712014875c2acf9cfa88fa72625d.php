<?php $__env->startSection('content'); ?>
<div class="container py-4">
<?php echo $__env->make('profile.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<h2 class="mb-4">Mon profil</h2>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body p-4">

<?php if(session('success')): ?>
<div class="alert alert-success py-2"><?php echo e(session('success')); ?></div>
<?php endif; ?>

<?php if($errors->any()): ?>
<div class="alert alert-danger py-2"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('profile.update')); ?>" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<?php echo method_field('PUT'); ?>

<h5 class="fw-bold mb-3">Informations générales</h5>

<div class="mb-3 d-flex align-items-center gap-3">
<div>
<?php if($user->avatar): ?>
<img src="<?php echo e(asset('storage/' . $user->avatar)); ?>" width="80" height="80" style="border-radius: 50%; object-fit: cover;">
<?php else: ?>
<div class="d-flex align-items-center justify-content-center bg-light" style="width: 80px; height: 80px; border-radius: 50%;">
<i class="bx bx-user" style="font-size: 2.5rem; color: #94a3b8;"></i>
</div>
<?php endif; ?>
</div>
<div>
<input type="file" name="avatar" class="form-control">
<small class="text-muted">PNG, JPG. Max 2 Mo.</small>
</div>
</div>

<div class="mb-3">
<label class="form-label">Nom complet</label>
<input type="text" name="name" class="form-control form-control-lg" value="<?php echo e(old('name', $user->name)); ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control form-control-lg" value="<?php echo e(old('email', $user->email)); ?>" required>
</div>

<div class="mb-4">
<label class="form-label">Téléphone</label>
<input type="text" name="phone" class="form-control form-control-lg" value="<?php echo e(old('phone', $user->phone)); ?>" required>
</div>

<hr>

<h5 class="fw-bold mb-3">Changer le mot de passe</h5>
<p class="text-muted small">Laissez vide pour conserver le mot de passe actuel.</p>

<div class="mb-3">
<label class="form-label">Mot de passe actuel</label>
<input type="password" name="current_password" class="form-control form-control-lg">
</div>

<div class="mb-3">
<label class="form-label">Nouveau mot de passe</label>
<input type="password" name="new_password" class="form-control form-control-lg">
</div>

<div class="mb-4">
<label class="form-label">Confirmer le nouveau mot de passe</label>
<input type="password" name="new_password_confirmation" class="form-control form-control-lg">
</div>

<button type="submit" class="btn btn-primary px-5">Enregistrer</button>
</form>

</div>
</div>
</div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\profile\edit.blade.php ENDPATH**/ ?>