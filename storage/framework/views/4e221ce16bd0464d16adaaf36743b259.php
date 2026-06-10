<?php $__env->startSection('content'); ?>
<div class="container py-4">
<h2 class="mb-4">Publier une annonce</h2>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body p-4">

<?php if($errors->any()): ?>
<div class="alert alert-danger py-2"><?php echo e($errors->first()); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('articles.store')); ?>" enctype="multipart/form-data">
<?php echo csrf_field(); ?>

<div class="mb-3">
<label class="form-label fw-medium">Titre de l'annonce *</label>
<input type="text" name="titre" class="form-control form-control-lg" placeholder="Ex: iPhone 13 Pro Max 256 Go" value="<?php echo e(old('titre')); ?>" required>
</div>

<div class="mb-3">
<label class="form-label fw-medium">Description *</label>
<textarea name="description" class="form-control form-control-lg" rows="5" placeholder="Décrivez votre article en détail..." required><?php echo e(old('description')); ?></textarea>
</div>

<div class="row g-3 mb-3">
<div class="col-md-6">
<label class="form-label fw-medium">Prix *</label>
<input type="number" name="prix" class="form-control form-control-lg" placeholder="Ex: 50000" value="<?php echo e(old('prix')); ?>" required>
</div>
<div class="col-md-6">
<label class="form-label fw-medium">Devise *</label>
<select name="currency" class="form-control form-control-lg" required>
<option value="GNF" <?php echo e(old('currency') === 'GNF' ? 'selected' : ''); ?>>GNF (Franc Guinéen)</option>
<option value="FCFA" <?php echo e(old('currency') === 'FCFA' ? 'selected' : ''); ?>>FCFA (Franc CFA)</option>
<option value="USD" <?php echo e(old('currency') === 'USD' ? 'selected' : ''); ?>>USD (Dollar US)</option>
<option value="EUR" <?php echo e(old('currency') === 'EUR' ? 'selected' : ''); ?>>EUR (Euro)</option>
</select>
</div>
</div>

<div class="row g-3 mb-3">
<div class="col-md-6">
<label class="form-label fw-medium">Catégorie *</label>
<select name="category_id" class="form-control form-control-lg" required>
<option value="">Sélectionnez une catégorie</option>
<?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<option value="<?php echo e($cat->id); ?>" <?php echo e(old('category_id') == $cat->id ? 'selected' : ''); ?>><?php echo e($cat->libelle); ?></option>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
</div>
<div class="col-md-6">
<label class="form-label fw-medium">État *</label>
<select name="condition" class="form-control form-control-lg" required>
<option value="neuf" <?php echo e(old('condition') === 'neuf' ? 'selected' : ''); ?>>Neuf</option>
<option value="très bon" <?php echo e(old('condition') === 'très bon' ? 'selected' : ''); ?>>Très bon état</option>
<option value="bon" <?php echo e(old('condition') === 'bon' ? 'selected' : ''); ?>>Bon état</option>
<option value="moyen" <?php echo e(old('condition') === 'moyen' ? 'selected' : ''); ?>>État moyen</option>
</select>
</div>
</div>

<div class="mb-3">
<label class="form-label fw-medium">Localisation *</label>
<input type="text" name="localisation" class="form-control form-control-lg" placeholder="Ex: Conakry, Ratoma" value="<?php echo e(old('localisation')); ?>" required>
</div>

<div class="mb-4">
<div class="form-check form-switch mb-2">
<input class="form-check-input" type="checkbox" name="with_delivery" id="withDelivery" value="1" <?php echo e(old('with_delivery') ? 'checked' : ''); ?>>
<label class="form-check-label fw-medium" for="withDelivery">Proposer la livraison</label>
</div>
<div id="deliveryPriceField" class="<?php echo e(old('with_delivery') ? '' : 'd-none'); ?>">
<label class="form-label">Prix de la livraison (GNF)</label>
<input type="number" name="delivery_price" class="form-control form-control-lg" placeholder="Ex: 15000" value="<?php echo e(old('delivery_price')); ?>">
</div>
</div>

<div class="mb-4">
<label class="form-label fw-medium">Images de l'annonce (max 5) *</label>
<div class="image-upload-wrapper" style="border: 2px dashed #dee2e6; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; background: #f8f9fa;" id="dropZone">
	<i class="bx bx-cloud-upload" style="font-size: 2.5rem; color: #6c757d; display: block; margin-bottom: 10px;"></i>
	<p class="mb-1 fw-bold" style="color: #495057;">Glissez vos photos ici ou cliquez pour sélectionner</p>
	<small class="text-muted">JPG, PNG. Max 2 Mo par image. Vous pouvez en ajouter jusqu'à 5.</small>
	<input type="file" id="imageInput" name="images[]" class="form-control form-control-lg" multiple accept="image/*" style="display: none;">
</div>

<div id="imagePreview" class="mt-3" style="display: none;">
	<div class="row g-2" id="previewContainer"></div>
</div>

<small class="text-muted d-block mt-2">
	<span id="imageCount">0</span> image(s) sélectionnée(s)
</small>
</div>

<button type="submit" class="btn btn-primary btn-lg w-100">Publier l'annonce</button>
</form>

</div>
</div>
</div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.getElementById('withDelivery').addEventListener('change', function() {
document.getElementById('deliveryPriceField').classList.toggle('d-none', !this.checked);
});
</script>
<script>
const dropZone = document.getElementById('dropZone');
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');
const previewContainer = document.getElementById('previewContainer');
const imageCount = document.getElementById('imageCount');

// Handle drag and drop
dropZone.addEventListener('dragover', (e) => {
	e.preventDefault();
	dropZone.style.borderColor = '#e66a00';
	dropZone.style.backgroundColor = '#fff8f3';
});

dropZone.addEventListener('dragleave', () => {
	dropZone.style.borderColor = '#dee2e6';
	dropZone.style.backgroundColor = '#f8f9fa';
});

dropZone.addEventListener('drop', (e) => {
	e.preventDefault();
	dropZone.style.borderColor = '#dee2e6';
	dropZone.style.backgroundColor = '#f8f9fa';
  
	const files = e.dataTransfer.files;
	imageInput.files = files;
	updateImagePreview();
});

dropZone.addEventListener('click', () => imageInput.click());

imageInput.addEventListener('change', updateImagePreview);

function updateImagePreview() {
	const files = imageInput.files;
	previewContainer.innerHTML = '';
  
	if (files.length === 0) {
		imagePreview.style.display = 'none';
		imageCount.textContent = '0';
		return;
	}
  
	imagePreview.style.display = 'block';
	imageCount.textContent = files.length;
  
	Array.from(files).forEach((file, index) => {
		const reader = new FileReader();
		reader.onload = (e) => {
			const col = document.createElement('div');
			col.className = 'col-12 col-sm-6 col-md-4 col-lg-3';
			col.innerHTML = `
				<div class="card h-100 shadow-sm" style="position: relative; border-radius: 10px;">
					<img src="${e.target.result}" style="height: 150px; object-fit: cover; border-radius: 8px 8px 0 0;" alt="Aperçu ${index + 1}">
					<div class="card-body p-2">
						<small class="text-muted text-truncate d-block">${file.name}</small>
						<small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} Mo</small>
						<button type="button" class="btn btn-sm btn-outline-danger mt-2 w-100 remove-image" data-index="${index}">
							<i class="bx bx-trash"></i> Supprimer
						</button>
					</div>
				</div>
			`;
			previewContainer.appendChild(col);
		};
		reader.readAsDataURL(file);
	});
  
	// Add remove functionality after preview is rendered
	setTimeout(() => {
		document.querySelectorAll('.remove-image').forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				const index = parseInt(btn.dataset.index);
				removeImage(index);
			});
		});
	}, 100);
}

function removeImage(index) {
	const dataTransfer = new DataTransfer();
	const files = imageInput.files;
  
	for (let i = 0; i < files.length; i++) {
		if (i !== index) {
			dataTransfer.items.add(files[i]);
		}
	}
  
	imageInput.files = dataTransfer.files;
	updateImagePreview();
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views/articles/create.blade.php ENDPATH**/ ?>