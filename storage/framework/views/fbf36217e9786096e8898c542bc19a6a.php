<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Modifier mon magasin</h4>
                        <p class="text-muted mb-0 mt-1"><?php echo e($magasin->nom_magasin); ?></p>
                    </div>
                    <a href="<?php echo e($magasin->url); ?>" class="btn btn-outline-primary" target="_blank">
                        <i class='bx bx-show'></i> Voir la boutique
                    </a>
                </div>
                <div class="card-body p-4">
                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($err); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('magasin.update')); ?>" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Nom du magasin *</label>
                            <input type="text" name="nom_magasin" class="form-control form-control-lg" value="<?php echo e(old('nom_magasin', $magasin->nom_magasin)); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Slug (URL)</label>
                            <input type="text" name="slug" class="form-control form-control-lg" value="<?php echo e(old('slug', $magasin->slug)); ?>">
                            <small class="text-muted">URL publique : /boutique/<?php echo e($magasin->slug); ?></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo e(old('description', $magasin->description)); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Adresse</label>
                            <input type="text" name="adresse" class="form-control form-control-lg" value="<?php echo e(old('adresse', $magasin->adresse)); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control form-control-lg" value="<?php echo e(old('telephone', $magasin->telephone)); ?>">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Logo</label>
                                <?php if($magasin->logo): ?>
                                    <div class="mb-2"><img src="<?php echo e($magasin->logo_url); ?>" alt="Logo" style="height:60px;border-radius:8px;"></div>
                                <?php endif; ?>
                                <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Max 2 Mo. Laissez vide pour garder l'actuel.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Bannière (couverture)</label>
                                <?php if($magasin->couverture): ?>
                                    <div class="mb-2"><img src="<?php echo e($magasin->cover_url); ?>" alt="Couverture" style="height:60px;border-radius:8px;object-fit:cover;width:100%;"></div>
                                <?php endif; ?>
                                <input type="file" name="couverture" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Max 5 Mo. Laissez vide pour garder l'actuelle.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">Horaires d'ouverture</label>
                            <div id="horaires-container">
                                <?php $__empty_1 = true; $__currentLoopData = old('horaire.jours', $magasin->horaire ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $jour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php if(is_array($jour)): ?>
                                        <?php $h = $jour; ?>
                                        <div class="row g-2 mb-2 horaire-row">
                                            <div class="col-4">
                                                <select name="horaire[jours][]" class="form-control">
                                                    <?php $__currentLoopData = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($d); ?>" <?php echo e(($h['jour'] ?? '') === $d ? 'selected' : ''); ?>><?php echo e($d); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                            <div class="col-3"><input type="time" name="horaire[ouverture][]" class="form-control" value="<?php echo e($h['ouverture'] ?? '08:00'); ?>"></div>
                                            <div class="col-3"><input type="time" name="horaire[fermeture][]" class="form-control" value="<?php echo e($h['fermeture'] ?? '18:00'); ?>"></div>
                                            <div class="col-2"><button type="button" class="btn btn-danger btn-sm w-100 remove-horaire">×</button></div>
                                        </div>
                                    <?php elseif(is_string($jour)): ?>
                                        <?php
                                            $jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
                                            $label = $jour;
                                            $ouvert = old('horaire.ouverture', $magasin->horaire ?? [])[$i] ?? '08:00';
                                            $fermeture = old('horaire.fermeture', $magasin->horaire ?? [])[$i] ?? '18:00';
                                        ?>
                                        <div class="row g-2 mb-2 horaire-row">
                                            <div class="col-4">
                                                <select name="horaire[jours][]" class="form-control">
                                                    <?php $__currentLoopData = $jours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($d); ?>" <?php echo e($d === $label ? 'selected' : ''); ?>><?php echo e($d); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                            <div class="col-3"><input type="time" name="horaire[ouverture][]" class="form-control" value="<?php echo e($ouvert); ?>"></div>
                                            <div class="col-3"><input type="time" name="horaire[fermeture][]" class="form-control" value="<?php echo e($fermeture); ?>"></div>
                                            <div class="col-2"><button type="button" class="btn btn-danger btn-sm w-100 remove-horaire">×</button></div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <div class="row g-2 mb-2 horaire-row">
                                        <div class="col-4">
                                            <select name="horaire[jours][]" class="form-control">
                                                <option value="Lundi">Lundi</option>
                                                <option value="Mardi">Mardi</option>
                                                <option value="Mercredi">Mercredi</option>
                                                <option value="Jeudi">Jeudi</option>
                                                <option value="Vendredi">Vendredi</option>
                                                <option value="Samedi">Samedi</option>
                                                <option value="Dimanche">Dimanche</option>
                                            </select>
                                        </div>
                                        <div class="col-3"><input type="time" name="horaire[ouverture][]" class="form-control" value="08:00"></div>
                                        <div class="col-3"><input type="time" name="horaire[fermeture][]" class="form-control" value="18:00"></div>
                                        <div class="col-2"><button type="button" class="btn btn-outline-danger btn-sm add-horaire w-100">+</button></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Ajoutez les jours et horaires d'ouverture de votre magasin.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('horaires-container');

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-horaire')) {
            const row = container.querySelector('.horaire-row').cloneNode(true);
            row.querySelectorAll('select, input').forEach(el => { el.value = ''; });
            row.querySelector('select').value = 'Lundi';
            row.querySelector('input[type="time"]:first-of-type').value = '08:00';
            row.querySelector('input[type="time"]:last-of-type').value = '18:00';
            const btn = row.querySelector('.add-horaire');
            if (btn) {
                btn.textContent = '×';
                btn.classList.remove('btn-outline-danger', 'add-horaire');
                btn.classList.add('btn-danger', 'remove-horaire');
                btn.onclick = null;
            }
            container.appendChild(row);
        }
        if (e.target.classList.contains('remove-horaire')) {
            const rows = container.querySelectorAll('.horaire-row');
            if (rows.length > 1) {
                e.target.closest('.horaire-row').remove();
            }
        }
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\magasin\edit.blade.php ENDPATH**/ ?>