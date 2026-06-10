<?php $__env->startSection('content'); ?>
<div class="container py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2 class="mb-0">Mes annonces</h2>
<a href="<?php echo e(route('articles.create')); ?>" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Nouvelle annonce</a>
</div>

<div class="mb-4">
<div class="search-wrapper w-100" style="max-width: 400px;">
<i class='bx bx-search'></i>
<input id="listingsSearch" class="form-control form-control-lg" type="search" placeholder="Rechercher dans mes annonces...">
</div>
</div>

<div id="listingsGrid" class="row g-4">
<?php echo $__env->make('partials.profile-articles', ['articles' => $articles], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const listingsGrid = document.getElementById('listingsGrid');
const listingsSearch = document.getElementById('listingsSearch');

listingsSearch.addEventListener('keyup', () => {
const q = listingsSearch.value.trim();
fetch(`/profile/listings?search=${encodeURIComponent(q)}&ajax=1`)
.then(res => res.json())
.then(data => {
listingsGrid.innerHTML = data.html;
});
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\profile\listings.blade.php ENDPATH**/ ?>