<?php
    $currentRoute = Route::currentRouteName();
?>
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="<?php echo e(route('profile.dashboard')); ?>" class="btn <?php echo e(str_starts_with($currentRoute, 'profile.dashboard') ? 'btn-primary' : 'btn-outline-secondary'); ?> px-4">
        <i class="bx bx-dashboard"></i> Tableau de bord
    </a>
    <a href="<?php echo e(route('profile.listings')); ?>" class="btn <?php echo e(str_starts_with($currentRoute, 'profile.listings') ? 'btn-primary' : 'btn-outline-secondary'); ?> px-4">
        <i class="bx bx-list-ul"></i> Mes annonces
    </a>
    <a href="<?php echo e(route('profile.saved')); ?>" class="btn <?php echo e(str_starts_with($currentRoute, 'profile.saved') ? 'btn-primary' : 'btn-outline-secondary'); ?> px-4">
        <i class="bx bx-bookmark"></i> Mes favoris
    </a>
    <a href="<?php echo e(route('profile.edit')); ?>" class="btn <?php echo e(str_starts_with($currentRoute, 'profile.edit') ? 'btn-primary' : 'btn-outline-secondary'); ?> px-4">
        <i class="bx bx-user"></i> Mon profil
    </a>
</div>
<?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views/profile/nav.blade.php ENDPATH**/ ?>