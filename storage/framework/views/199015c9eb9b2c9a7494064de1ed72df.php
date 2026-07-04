<?php $__env->startSection('content'); ?>
<div class="container-fluid p-0" style="height: 100vh;">
    <div class="row g-0 h-100">
        <div class="col-md-4 col-lg-3 p-3 bg-light overflow-auto" style="max-height: 100%;">
            <div class="d-flex align-items-center mb-3">
                <a href="<?php echo e(route('motard.dashboard')); ?>" class="btn btn-outline-secondary btn-sm me-2">
                    <i class='bx bx-arrow-back'></i>
                </a>
                <h5 class="fw-bold mb-0">Livraison en cours</h5>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold"><?php echo e($delivery->order->article->titre); ?></h6>
                    <p class="small text-muted mb-2">Réf: <?php echo e($delivery->order->reference); ?></p>
                    <span class="badge fs-6 <?php echo e($delivery->status->value === 'en_cours' ? 'bg-primary' : 'bg-warning text-dark'); ?>">
                        <?php echo e($delivery->status->value === 'en_cours' ? 'En cours' : 'Acceptée'); ?>

                    </span>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Détails</h6>
                    <div class="mb-2">
                        <small class="text-muted d-block">De (retrait)</small>
                        <strong><?php echo e($delivery->pickup_adresse); ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">À (livraison)</small>
                        <strong><?php echo e($delivery->delivery_adresse); ?></strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Client</small>
                        <strong><?php echo e($delivery->order->buyer->name ?? 'Client'); ?></strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Gain</small>
                        <strong class="text-success"><?php echo e(number_format($delivery->prix, 0, ',', ' ')); ?> GNF</strong>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Actions</h6>
                    <button id="startTrackingBtn" class="btn btn-primary w-100 mb-2">
                        <i class='bx bx-current-location'></i> Partager ma position
                    </button>
                    <button id="stopTrackingBtn" class="btn btn-outline-danger w-100 mb-2" style="display:none;">
                        <i class='bx bx-stop'></i> Arrêter le partage
                    </button>
                    <?php if($delivery->status->value === 'acceptee'): ?>
                    <form method="POST" action="<?php echo e(route('deliveries.pickup', $delivery->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class='bx bx-package'></i> Marquer comme récupéré
                        </button>
                    </form>
                    <?php elseif($delivery->status->value === 'en_cours'): ?>
                    <form method="POST" action="<?php echo e(route('deliveries.complete', $delivery->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-success w-100">
                            <i class='bx bx-check-circle'></i> Terminer la livraison
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Parcours</h6>
                    <div id="statusSteps" class="small">
                        <div class="d-flex align-items-center mb-2 <?php echo e(in_array($delivery->status->value, ['acceptee','en_cours','effectuee']) ? 'text-success' : 'text-muted'); ?>">
                            <i class='bx bx-check-circle me-2'></i> Acceptée
                        </div>
                        <div class="d-flex align-items-center mb-2 <?php echo e($delivery->status->value === 'en_cours' || $delivery->status->value === 'effectuee' ? 'text-success' : 'text-muted'); ?>">
                            <i class='bx bx-check-circle me-2'></i> Colis récupéré
                        </div>
                        <div class="d-flex align-items-center mb-2 <?php echo e($delivery->status->value === 'effectuee' ? 'text-success' : 'text-muted'); ?>">
                            <i class='bx bx-check-circle me-2'></i> Livrée
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-lg-9 p-0">
            <div id="map" style="width:100%;height:100%;"></div>
        </div>
    </div>
</div>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
#map .leaflet-popup-content { margin: 8px 12px; font-size: 13px; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map, riderMarker, pickupMarker, deliveryMarker, trackLine;
let watchId = null;
let isTracking = false;
let trackPoints = <?php echo json_encode($track ?? [], 15, 512) ?>;
const deliveryId = <?php echo e($delivery->id); ?>;
const pickupLat = <?php echo e($delivery->pickup_latitude); ?>;
const pickupLng = <?php echo e($delivery->pickup_longitude); ?>;
const deliveryLat = <?php echo e($delivery->delivery_latitude); ?>;
const deliveryLng = <?php echo e($delivery->delivery_longitude); ?>;
const pickupAddress = <?php echo json_encode($delivery->pickup_adresse); ?>;
const deliveryAddress = <?php echo json_encode($delivery->delivery_adresse); ?>;

function initMap() {
    const center = [(pickupLat + deliveryLat) / 2, (pickupLng + deliveryLng) / 2];
    map = L.map('map', { zoomControl: true }).setView(center, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    const pickupIcon = L.divIcon({
        html: '<div style="background:#22c55e;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"><i class="bx bx-package" style="font-size:16px;"></i></div>',
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    const deliveryIcon = L.divIcon({
        html: '<div style="background:#ef4444;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"><i class="bx bx-current-location" style="font-size:16px;"></i></div>',
        className: '',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    pickupMarker = L.marker([pickupLat, pickupLng], { icon: pickupIcon })
        .addTo(map)
        .bindPopup('<strong>Point de retrait</strong><br>' + pickupAddress);

    deliveryMarker = L.marker([deliveryLat, deliveryLng], { icon: deliveryIcon })
        .addTo(map)
        .bindPopup('<strong>Point de livraison</strong><br>' + deliveryAddress);

    if (trackPoints.length > 0) {
        const polylinePoints = trackPoints.map(p => [p.lat, p.lng]);
        trackLine = L.polyline(polylinePoints, { color: '#3b82f6', weight: 4, opacity: 0.7 }).addTo(map);

        const last = trackPoints[trackPoints.length - 1];
        setRiderMarker(last.lat, last.lng);
        map.setView([last.lat, last.lng], 14);
    }

    const bounds = L.latLngBounds([
        [pickupLat, pickupLng],
        [deliveryLat, deliveryLng]
    ]);
    if (trackPoints.length > 0) {
        trackPoints.forEach(p => bounds.extend([p.lat, p.lng]));
    }
    map.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });

    setTimeout(() => map.invalidateSize(), 300);
}

function setRiderMarker(lat, lng) {
    const riderIcon = L.divIcon({
        html: '<div style="background:#3b82f6;color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"><i class="bx bxs-motorcycle" style="font-size:14px;"></i></div>',
        className: '',
        iconSize: [28, 28],
        iconAnchor: [14, 14]
    });

    if (riderMarker) {
        riderMarker.setLatLng([lat, lng]);
    } else {
        riderMarker = L.marker([lat, lng], { icon: riderIcon }).addTo(map);
    }
}

function sendPosition(lat, lng) {
    fetch('<?php echo e(route("motard.position", $delivery->id)); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ latitude: lat, longitude: lng })
    }).catch(err => console.error('Position update failed:', err));
}

function startTracking() {
    if (!navigator.geolocation) {
        alert('La géolocalisation n\'est pas supportée par votre navigateur.');
        return;
    }

    document.getElementById('startTrackingBtn').style.display = 'none';
    document.getElementById('stopTrackingBtn').style.display = 'block';

    isTracking = true;
    watchId = navigator.geolocation.watchPosition(
        function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            setRiderMarker(lat, lng);
            map.setView([lat, lng], 15);
            sendPosition(lat, lng);

            trackPoints.push({ lat, lng, timestamp: new Date().toISOString() });
            if (trackLine) {
                const points = trackPoints.map(p => [p.lat, p.lng]);
                trackLine.setLatLngs(points);
            }
        },
        function(err) {
            console.error('Geolocation error:', err);
            alert('Erreur de géolocalisation. Veuillez vérifier vos permissions.');
            stopTracking();
        },
        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
    );
}

function stopTracking() {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
    isTracking = false;
    document.getElementById('startTrackingBtn').style.display = 'block';
    document.getElementById('stopTrackingBtn').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.container-fluid');
    const nav = document.querySelector('.navbar');
    if (container && nav) {
        container.style.height = (window.innerHeight - nav.offsetHeight) + 'px';
    }
    initMap();
    document.getElementById('startTrackingBtn').addEventListener('click', startTracking);
    document.getElementById('stopTrackingBtn').addEventListener('click', stopTracking);
});

window.addEventListener('resize', function() {
    const container = document.querySelector('.container-fluid');
    const nav = document.querySelector('.navbar');
    if (container && nav) {
        container.style.height = (window.innerHeight - nav.offsetHeight) + 'px';
    }
    if (map) setTimeout(() => map.invalidateSize(), 100);
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\motard\tracking.blade.php ENDPATH**/ ?>