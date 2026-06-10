<?php $__env->startSection('content'); ?>
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-8 col-lg-6">
<div class="card shadow-sm border-0" style="border-radius: 18px;">
<div class="card-body p-5">
<div class="text-center mb-4">
<img src="<?php echo e(asset('assets/img/icon.png')); ?>" width="72" style="border-radius: 10px;">
<h3 class="mt-3 fw-bold">Créer un compte</h3>
</div>

<?php if($errors->any()): ?>
<div class="alert alert-danger py-2"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('register')); ?>">
<?php echo csrf_field(); ?>
<div class="mb-3 position-relative">
<i class="bx bx-user position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="text" name="name" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Nom complet *" required value="<?php echo e(old('name')); ?>">
</div>
<div class="mb-3 position-relative">
<i class="bx bx-envelope position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="email" name="email" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Adresse email *" required value="<?php echo e(old('email')); ?>">
</div>
<div class="mb-3 position-relative">
<i class="bx bx-phone position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="text" name="phone" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Numéro de téléphone *" required value="<?php echo e(old('phone')); ?>">
</div>
<div class="mb-3 position-relative">
<i class="bx bx-lock position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="password" name="password" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Mot de passe *" required>
</div>
<div class="mb-3 position-relative">
<i class="bx bx-lock-alt position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="password" name="password_confirmation" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Confirmer le mot de passe *" required>
</div>
<div class="mb-4 position-relative">
<i class="bx bx-badge-check position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<select name="role" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" required>
<option value="">Sélectionnez un rôle *</option>
<option value="acheteur" <?php echo e(old('role') === 'acheteur' ? 'selected' : ''); ?>>Acheteur</option>
<option value="vendeur" <?php echo e(old('role') === 'vendeur' ? 'selected' : ''); ?>>Vendeur</option>
<option value="revendeur" <?php echo e(old('role') === 'revendeur' ? 'selected' : ''); ?>>Revendeur</option>
<option value="motard" <?php echo e(old('role') === 'motard' ? 'selected' : ''); ?>>Motard</option>
</select>
</div>
<button type="submit" class="btn btn-primary w-100" style="border-radius: 25px; padding: 12px; font-weight: 600;">S'inscrire</button>
<hr class="my-4">
<div class="text-center small">
<a href="<?php echo e(route('login')); ?>">Déjà un compte ? Connectez-vous</a>
</div>
</form>
</div>
</div>
</div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views/auth/register.blade.php ENDPATH**/ ?>