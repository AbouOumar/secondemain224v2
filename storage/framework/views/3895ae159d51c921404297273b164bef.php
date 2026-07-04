<?php $__env->startSection('content'); ?>
<section>
<div class="container">
<div class="row align-items-center gy-3 py-4">
<div class="col-lg-7">
<h2 class="mb-2">Bienvenue sur <span style="color: var(--primary)">Seconde Main 224</span></h2>
<p class="text-muted">Achetez et vendez facilement des biens d'occasion. Publiez en quelques clics, discutez et organisez la livraison.</p>
<form id="searchForm" class="d-flex gap-2 mt-3" onsubmit="return false;">
<div class="search-wrapper w-100">
<i class='bx bx-search'></i>
<input id="searchInput" class="form-control form-control-lg" type="search" placeholder="Rechercher un article (titre, description)...">
</div>
</form>
<a href="<?php echo e(route('articles.create')); ?>" class="btn btn-primary mt-3 w-100">
<i class='bx bx-plus me-2'></i> Déposer une annonce
</a>
</div>
<div class="col-lg-5 text-center">
<img src="<?php echo e(asset('assets/img/hero-bg.jpg')); ?>" alt="hero" style="max-width:100%;border-radius:10px;box-shadow:var(--card-shadow);height:190px;object-fit:cover;">
</div>
</div>
</div>
</section>

<!-- Section des produits partenaires -->
<?php if($partnerArticles && $partnerArticles->count() > 0): ?>
<section class="py-4 bg-light">
<div class="container">
<h3 class="mb-4 text-center">Espaces partenaires - Découvrez les produits de nos magasins</h3>
<div class="row g-4">
<?php $__currentLoopData = $partnerArticles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $article): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="col-md-4">
<div class="card h-100 shadow-sm">
<?php if($article->images->count() > 0): ?>
<img src="<?php echo e($article->images->first()->url); ?>" class="card-img-top" alt="<?php echo e($article->titre); ?>" style="height: 180px; object-fit: cover;">
<?php else: ?>
<img src="<?php echo e(asset('assets/img/icon.png')); ?>" class="card-img-top" alt="Pas d'image" style="height: 180px; object-fit: contain; background-color: #f8f9fa;">
<?php endif; ?>
<div class="card-body d-flex flex-column">
<h5 class="card-title"><?php echo e($article->titre); ?></h5>
<p class="card-text text-muted small mb-2"><?php echo e($article->user->name); ?> • <?php echo e($article->localisation); ?></p>
<p class="card-text fw-bold mb-3"><?php echo e(number_format($article->prix, 0, ',', ' ')); ?> <?php echo e($article->currency?->value); ?></p>
<div class="d-flex justify-content-between align-items-center mt-auto">
<a href="<?php echo e(route('articles.show', $article->slug)); ?>" class="btn btn-sm btn-outline-primary">Voir détails</a>
                    <a href="<?php echo e(route('magasin.show', $article->user->partner->slug)); ?>" class="badge bg-success text-decoration-none"><?php echo e($article->user->partner->nom_magasin); ?></a>
</div>
</div>
</div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
</div>
</section>
<?php endif; ?>

<section class="py-3">
<div class="container d-flex flex-nowrap overflow-auto gap-3" id="categoriesContainer">
<div class="cat-item text-center flex-shrink-0" onclick="selectCategory('')">
<div class="cat-circle"><i class="bx bx-list-ul"></i></div>
<div class="mt-1 small">Tous</div>
</div>
<?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="cat-item text-center flex-shrink-0" onclick="selectCategory('<?php echo e($cat->id); ?>')">
<div class="cat-circle"><i class="bx <?php echo e($cat->icon); ?>"></i></div>
<div class="mt-1 small"><?php echo e($cat->libelle); ?></div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
</section>

<!-- Filtres avancés -->
<section class="pb-2">
<div class="container">
<div class="bg-light rounded-3 p-3">
<div class="row g-2 align-items-end">
<div class="col-md-3 col-6">
<label class="form-label small text-muted mb-1">Prix min</label>
<input type="number" id="filterMinPrice" class="form-control form-control-sm" placeholder="Min" min="0">
</div>
<div class="col-md-3 col-6">
<label class="form-label small text-muted mb-1">Prix max</label>
<input type="number" id="filterMaxPrice" class="form-control form-control-sm" placeholder="Max" min="0">
</div>
<div class="col-md-4 col-12">
<label class="form-label small text-muted mb-1">Localisation</label>
<input type="text" id="filterLocation" class="form-control form-control-sm" placeholder="Ville, quartier...">
</div>
<div class="col-md-2 col-12 d-grid">
<button class="btn btn-primary btn-sm" onclick="applyFilters()"><i class='bx bx-search'></i> Appliquer</button>
</div>
</div>
</div>
</div>
</section>

<section class="py-4">
<div class="container">
<h3 class="mb-4 text-center">Articles en vente</h3>
<div id="articlesGrid" class="row g-4">
<?php if($featuredArticles && $featuredArticles->count() > 0): ?>
<?php $__currentLoopData = $featuredArticles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="col-12 col-sm-6 col-md-4 col-lg-3 article-item">
<div class="card article-card h-100 border-0 shadow-sm position-relative w-100 d-flex flex-column">
<div class="position-relative">
<a href="<?php echo e(route('articles.show', $item->slug)); ?>">
<img src="<?php echo e($item->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo'); ?>?fit=fill&w=300&h=200" alt="<?php echo e($item->titre); ?>" class="card-img-top" loading="lazy">
</a>
<span class="badge price-badge"><?php echo e(number_format($item->prix, 0, ',', ' ')); ?> <?php echo e($item->currency->value); ?></span>
</div>
<div class="card-body d-flex flex-column p-3 flex-grow-1">
<h6 class="title mb-1 text-truncate" title="<?php echo e($item->titre); ?>"><?php echo e($item->titre); ?></h6>
<p class="text-muted small mb-1 flex-grow-1"><?php echo e(Str::limit($item->description, 80)); ?></p>
<span class="small text-muted mb-2"><?php echo e($item->category->libelle ?? ''); ?></span>
<div class="d-flex gap-2 position-relative mt-auto">
<a href="<?php echo e(route('articles.show', $item->slug)); ?>" class="btn btn-sm btn-primary w-100">Voir</a>
<button type="button" class="btn btn-sm btn-outline-secondary share-btn" data-url="<?php echo e(route('articles.show', $item->slug)); ?>"><i class='bx bx-share-alt'></i></button>
<div class="share-popup shadow-sm">
<button class="btn btn-sm w-100 text-start" onclick="copyLink('<?php echo e(route('articles.show', $item->slug)); ?>')"><i class='bx bx-link-alt'></i> Copier</button>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://wa.me/?text=<?php echo e(urlencode($item->titre.' - '.route('articles.show', $item->slug))); ?>"><i class='bx bxl-whatsapp'></i> WhatsApp</a>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo e(urlencode(route('articles.show', $item->slug))); ?>"><i class='bx bxl-facebook'></i> Facebook</a>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://twitter.com/intent/tweet?url=<?php echo e(urlencode(route('articles.show', $item->slug))); ?>&text=<?php echo e(urlencode($item->titre)); ?>"><i class='bx bxl-twitter'></i> X</a>
</div>
</div>
</div>
</div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php else: ?>
<div class="col-12 text-center py-5">
<div class="spinner-border text-muted" role="status"><span class="visually-hidden">Chargement...</span></div>
</div>
<?php endif; ?>
</div>
</div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const articlesGrid = document.getElementById('articlesGrid');
const searchInput = document.getElementById('searchInput');
let currentCategory = '';
let currentSearch = '';
let currentMinPrice = '';
let currentMaxPrice = '';
let currentLocation = '';
let currentPage = 2;
let loading = false;
let allLoaded = false;
let initialLoaded = true;

function renderArticles(html, append = false) {
if (append && (html.includes('Aucun article') || html.includes('alert-warning'))) return;
const tmp = document.createElement('div'); tmp.innerHTML = html;
if (!append) articlesGrid.innerHTML = '';
while (tmp.firstChild) articlesGrid.appendChild(tmp.firstChild);
}

function loadArticles(reset = false) {
if (reset) { currentPage = 1; allLoaded = false; }
if (loading || allLoaded) return;
loading = true;
if (reset) { articlesGrid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-muted" role="status"></div></div>'; }
let url = `/search?search=${encodeURIComponent(currentSearch)}&category=${currentCategory}&page=${currentPage}&ajax=1`;
if (currentMinPrice) url += `&min_price=${encodeURIComponent(currentMinPrice)}`;
if (currentMaxPrice) url += `&max_price=${encodeURIComponent(currentMaxPrice)}`;
if (currentLocation) url += `&localisation=${encodeURIComponent(currentLocation)}`;
fetch(url)
.then(res => res.json())
.then(data => {
renderArticles(data.html, !reset);
allLoaded = !data.hasMore;
currentPage++;
loading = false;
}).catch(() => { loading = false; });
}

function selectCategory(catId) { currentCategory = catId; initialLoaded = false; loadArticles(true); }

searchInput.addEventListener('keyup', () => {
currentSearch = searchInput.value.trim();
initialLoaded = false;
loadArticles(true);
});

window.addEventListener('scroll', () => {
if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) loadArticles();
});

function applyFilters() {
currentMinPrice = document.getElementById('filterMinPrice').value.trim();
currentMaxPrice = document.getElementById('filterMaxPrice').value.trim();
currentLocation = document.getElementById('filterLocation').value.trim();
initialLoaded = false;
loadArticles(true);
}

document.addEventListener('click', e => {
document.querySelectorAll('.share-popup').forEach(sp => sp.style.display = 'none');
if (e.target.classList.contains('share-btn')) {
const popup = e.target.parentNode.querySelector('.share-popup');
if (popup) popup.style.display = 'block';
}
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\home.blade.php ENDPATH**/ ?>