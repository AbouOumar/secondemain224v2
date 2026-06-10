@extends('layouts.app')

@section('content')
<div class="container py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
<h2 class="mb-0">Modifier l'annonce</h2>
<form method="POST" action="{{ route('articles.update', $article->id) }}" onsubmit="return confirm('Supprimer cette annonce ?')">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-outline-danger"><i class="bx bx-trash"></i> Supprimer</button>
</form>
</div>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body p-4">

@if ($errors->any())
<div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('articles.update', $article->id) }}" enctype="multipart/form-data">
@csrf
@method('PUT')

<div class="mb-3">
<label class="form-label fw-medium">Titre de l'annonce *</label>
<input type="text" name="titre" class="form-control form-control-lg" placeholder="Ex: iPhone 13 Pro Max 256 Go" value="{{ old('titre', $article->titre) }}" required>
</div>

<div class="mb-3">
<label class="form-label fw-medium">Description *</label>
<textarea name="description" class="form-control form-control-lg" rows="5" placeholder="Décrivez votre article en détail..." required>{{ old('description', $article->description) }}</textarea>
</div>

<div class="row g-3 mb-3">
<div class="col-md-6">
<label class="form-label fw-medium">Prix *</label>
<input type="number" name="prix" class="form-control form-control-lg" placeholder="Ex: 50000" value="{{ old('prix', $article->prix) }}" required>
</div>
<div class="col-md-6">
<label class="form-label fw-medium">Devise *</label>
<select name="currency" class="form-control form-control-lg" required>
<option value="GNF" {{ old('currency', $article->currency?->value) === 'GNF' ? 'selected' : '' }}>GNF (Franc Guinéen)</option>
<option value="FCFA" {{ old('currency', $article->currency?->value) === 'FCFA' ? 'selected' : '' }}>FCFA (Franc CFA)</option>
<option value="USD" {{ old('currency', $article->currency?->value) === 'USD' ? 'selected' : '' }}>USD (Dollar US)</option>
<option value="EUR" {{ old('currency', $article->currency?->value) === 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
</select>
</div>
</div>

<div class="row g-3 mb-3">
<div class="col-md-6">
<label class="form-label fw-medium">Catégorie *</label>
<select name="category_id" class="form-control form-control-lg" required>
<option value="">Sélectionnez une catégorie</option>
@foreach($categories as $cat)
<option value="{{ $cat->id }}" {{ old('category_id', $article->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->libelle }}</option>
@endforeach
</select>
</div>
<div class="col-md-6">
<label class="form-label fw-medium">État *</label>
<select name="condition" class="form-control form-control-lg" required>
<option value="neuf" {{ old('condition', $article->condition) === 'neuf' ? 'selected' : '' }}>Neuf</option>
<option value="très bon" {{ old('condition', $article->condition) === 'très bon' ? 'selected' : '' }}>Très bon état</option>
<option value="bon" {{ old('condition', $article->condition) === 'bon' ? 'selected' : '' }}>Bon état</option>
<option value="moyen" {{ old('condition', $article->condition) === 'moyen' ? 'selected' : '' }}>État moyen</option>
</select>
</div>
</div>

<div class="mb-3">
<label class="form-label fw-medium">Localisation *</label>
<input type="text" name="localisation" class="form-control form-control-lg" placeholder="Ex: Conakry, Ratoma" value="{{ old('localisation', $article->localisation) }}" required>
</div>

<div class="mb-4">
<div class="form-check form-switch mb-2">
<input class="form-check-input" type="checkbox" name="with_delivery" id="withDelivery" value="1" {{ old('with_delivery', $article->with_delivery) ? 'checked' : '' }}>
<label class="form-check-label fw-medium" for="withDelivery">Proposer la livraison</label>
</div>
<div id="deliveryPriceField" class="{{ old('with_delivery', $article->with_delivery) ? '' : 'd-none' }}">
<label class="form-label">Prix de la livraison (GNF)</label>
<input type="number" name="delivery_price" class="form-control form-control-lg" placeholder="Ex: 15000" value="{{ old('delivery_price', $article->delivery_price) }}">
</div>
</div>

<div class="mb-4">
<label class="form-label fw-medium">Images (max 5)</label>
<input type="file" name="images[]" class="form-control form-control-lg" multiple accept="image/*">
<small class="text-muted">Laissez vide pour conserver les images actuelles.</small>
</div>

@if($article->images->count() > 0)
<div class="mb-4">
	<label class="form-label fw-medium">Images actuelles</label>
	<div class="row g-2" id="currentImagesContainer">
		@foreach($article->images as $img)
		<div class="col-12 col-sm-6 col-md-4 col-lg-3 current-image-item">
			<div class="card h-100 shadow-sm" style="position: relative; border-radius: 10px;">
				<img src="{{ $img->url }}" style="height: 150px; object-fit: cover; border-radius: 8px 8px 0 0;" alt="Image actuelle">
				<div class="card-body p-2">
					<button type="button" class="btn btn-sm btn-outline-danger w-100 remove-current-image" data-image-id="{{ $img->id }}">
						<i class="bx bx-trash"></i> Supprimer
					</button>
				</div>
			</div>
		</div>
		@endforeach
	</div>
</div>
@endif

<div class="mb-4">
<label class="form-label fw-medium">Ajouter de nouvelles images (max 5 au total)</label>
<div class="image-upload-wrapper" style="border: 2px dashed #dee2e6; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; background: #f8f9fa;" id="dropZone">
	<i class="bx bx-cloud-upload" style="font-size: 2.5rem; color: #6c757d; display: block; margin-bottom: 10px;"></i>
	<p class="mb-1 fw-bold" style="color: #495057;">Glissez vos photos ici ou cliquez pour sélectionner</p>
	<small class="text-muted">JPG, PNG. Max 2 Mo par image.</small>
	<input type="file" id="imageInput" name="images[]" class="form-control form-control-lg" multiple accept="image/*" style="display: none;">
</div>

<div id="imagePreview" class="mt-3" style="display: none;">
	<div class="row g-2" id="previewContainer"></div>
</div>

<small class="text-muted d-block mt-2">
	<span id="imageCount">0</span> image(s) à ajouter
</small>
</div>
</div>

<button type="submit" class="btn btn-primary btn-lg w-100">Enregistrer les modifications</button>
</form>

</div>
</div>
</div>
</div>
</div>
@endsection

@push('scripts')
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

// Handle removal of current images
document.querySelectorAll('.remove-current-image').forEach(btn => {
	btn.addEventListener('click', (e) => {
		e.preventDefault();
		const imageId = btn.dataset.imageId;
		const item = btn.closest('.current-image-item');
    
		// Create hidden input to mark image for deletion
		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'delete_images[]';
		input.value = imageId;
		document.querySelector('form').appendChild(input);
    
		// Remove visual element
		item.style.opacity = '0.5';
		btn.disabled = true;
		btn.textContent = 'Supprimée';
	});
});
</script>
@endpush
