<?php $__env->startSection('content'); ?>
<div class="container py-4">
<?php echo $__env->make('profile.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

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

<!-- Boost Modal -->
<div class="modal fade" id="boostModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content border-0 shadow" style="border-radius: 20px;">
<form method="POST" action="" id="boostForm">
<?php echo csrf_field(); ?>
<input type="hidden" name="duree_heures" id="boostHeures" value="24">

<div class="modal-body p-4 text-center">
<div class="mb-3">
<div class="mx-auto bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:72px;height:72px;">
<i class='bx bx-rocket' style="font-size:2.2rem;color:#e66a00;"></i>
</div>
</div>
<h4 class="fw-bold mb-1">Booster votre annonce</h4>
<p class="text-muted small mb-3">Choisissez la durée de mise en avant</p>

<div class="row g-2 mb-3" id="boostPresets">
<div class="col-3">
<button type="button" class="btn btn-outline-warning w-100 preset-btn" data-hours="24" onclick="selectBoost(24)">
<strong>24h</strong>
<small class="d-block" style="font-size:0.7rem;">12 000 GNF</small>
</button>
</div>
<div class="col-3">
<button type="button" class="btn btn-outline-warning w-100 preset-btn" data-hours="48" onclick="selectBoost(48)">
<strong>48h</strong>
<small class="d-block" style="font-size:0.7rem;">24 000 GNF</small>
</button>
</div>
<div class="col-3">
<button type="button" class="btn btn-outline-warning w-100 preset-btn" data-hours="72" onclick="selectBoost(72)">
<strong>72h</strong>
<small class="d-block" style="font-size:0.7rem;">36 000 GNF</small>
</button>
</div>
<div class="col-3">
<button type="button" class="btn btn-outline-warning w-100 preset-btn" data-hours="168" onclick="selectBoost(168)">
<strong>7 jours</strong>
<small class="d-block" style="font-size:0.7rem;">84 000 GNF</small>
</button>
</div>
</div>

<div class="mb-3">
<label class="form-label small text-muted">Ou personnaliser (heures)</label>
<div class="input-group">
<input type="number" class="form-control text-center" id="boostCustom" min="1" max="720" value="24" oninput="customBoost(this.value)">
<span class="input-group-text bg-light">h</span>
</div>
</div>

<div class="bg-light rounded-3 p-3 mb-3">
<div class="d-flex justify-content-between align-items-center">
<span class="text-muted small">Prix unitaire</span>
<span class="fw-bold">500 GNF / heure</span>
</div>
<hr class="my-2">
<div class="d-flex justify-content-between align-items-center">
<span class="fw-bold">Total à payer</span>
<span class="fw-bold fs-5" style="color:#e66a00;" id="boostTotal">12 000 GNF</span>
</div>
</div>

<div class="d-grid gap-2">
<button type="submit" class="btn btn-warning btn-lg fw-bold py-2" style="border-radius:12px;">
<i class='bx bx-rocket me-1'></i> Booster maintenant
</button>
<button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
</div>
</div>
</form>
</div>
</div>
</div>

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

const PRIX_PAR_HEURE = 500;

function openBoostModal(btn) {
const url = btn.getAttribute('data-url');
document.getElementById('boostForm').action = url;
document.getElementById('boostCustom').value = 24;
selectBoost(24);
const modal = new bootstrap.Modal(document.getElementById('boostModal'));
modal.show();
}

function selectBoost(hours) {
document.getElementById('boostHeures').value = hours;
document.getElementById('boostCustom').value = hours;
const total = hours * PRIX_PAR_HEURE;
document.getElementById('boostTotal').textContent = total.toLocaleString() + ' GNF';

document.querySelectorAll('.preset-btn').forEach(b => {
b.classList.remove('btn-warning', 'text-white');
b.classList.add('btn-outline-warning');
});
const active = document.querySelector(`.preset-btn[data-hours="${hours}"]`);
if (active) {
active.classList.remove('btn-outline-warning');
active.classList.add('btn-warning', 'text-white');
}
}

function customBoost(val) {
const h = parseInt(val) || 24;
const clamped = Math.min(Math.max(h, 1), 720);
document.getElementById('boostHeures').value = clamped;
document.getElementById('boostCustom').value = clamped;
const total = clamped * PRIX_PAR_HEURE;
document.getElementById('boostTotal').textContent = total.toLocaleString() + ' GNF';

document.querySelectorAll('.preset-btn').forEach(b => {
const presetH = parseInt(b.dataset.hours);
if (presetH === clamped) {
b.classList.remove('btn-outline-warning');
b.classList.add('btn-warning', 'text-white');
} else {
b.classList.remove('btn-warning', 'text-white');
b.classList.add('btn-outline-warning');
}
});
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views/profile/listings.blade.php ENDPATH**/ ?>