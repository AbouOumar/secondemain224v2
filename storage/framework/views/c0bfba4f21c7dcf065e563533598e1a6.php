<?php $__env->startSection('content'); ?>
<div class="container-fluid p-0" style="height: 100vh;">
    <div class="row g-0 h-100">
        <div class="col-md-4 col-lg-3 p-3 bg-light overflow-auto" style="max-height: 100%;">
            <div class="d-flex align-items-center mb-3">
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm me-2">
                    <i class='bx bx-arrow-back'></i>
                </a>
                <h5 class="fw-bold mb-0">Suivi de livraison</h5>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold"><?php echo e($delivery->order->article->titre); ?></h6>
                    <p class="small text-muted mb-2">Réf: <?php echo e($delivery->order->reference); ?></p>
                    <span class="badge fs-6
                        <?php switch($delivery->status->value):
                            case ('en_attente'): ?> bg-secondary <?php break; ?>
                            <?php case ('acceptee'): ?> bg-warning text-dark <?php break; ?>
                            <?php case ('en_cours'): ?> bg-primary <?php break; ?>
                            <?php case ('effectuee'): ?> bg-success <?php break; ?>
                            <?php default: ?> bg-secondary
                        <?php endswitch; ?>
                    ">
                        <?php switch($delivery->status->value):
                            case ('en_attente'): ?> En attente <?php break; ?>
                            <?php case ('acceptee'): ?> Acceptée <?php break; ?>
                            <?php case ('en_cours'): ?> En cours <?php break; ?>
                            <?php case ('effectuee'): ?> Livrée <?php break; ?>
                            <?php default: ?> <?php echo e($delivery->status->value); ?>

                        <?php endswitch; ?>
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
                        <small class="text-muted d-block">Livreur</small>
                        <strong><?php echo e($delivery->rider->name ?? 'Non assigné'); ?></strong>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Progression</h6>
                    <div id="statusSteps" class="small">
                        <div class="d-flex align-items-center mb-2 <?php echo e(in_array($delivery->status->value, ['acceptee','en_cours','effectuee']) ? 'text-success' : 'text-muted'); ?>">
                            <i class='bx bx-check-circle me-2'></i> Commande confirmée
                        </div>
                        <div class="d-flex align-items-center mb-2 <?php echo e(in_array($delivery->status->value, ['en_cours','effectuee']) ? 'text-success' : 'text-muted'); ?>">
                            <i class='bx bx-check-circle me-2'></i> Colis récupéré
                        </div>
                        <div class="d-flex align-items-center mb-2 <?php echo e($delivery->status->value === 'effectuee' ? 'text-success' : 'text-muted'); ?>">
                            <i class='bx bx-check-circle me-2'></i> Livrée
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-lg-9 p-0 position-relative">
            <div id="map" style="width:100%;height:100%;"></div>
            <div id="riderInfo" class="position-absolute top-0 end-0 m-3 bg-white rounded-3 shadow-sm p-3" style="z-index:1000;display:none;min-width:200px;">
                <div class="d-flex align-items-center gap-2">
                    <div class="spinner-grow spinner-grow-sm text-primary" role="status"></div>
                    <div>
                        <strong id="riderStatusText">Livreur en déplacement</strong>
                        <small class="d-block text-muted" id="riderLastUpdate"></small>
                    </div>
                </div>
            </div>
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
let pollInterval = null;
const deliveryId = <?php echo e($delivery->id); ?>;
const pickupLat = <?php echo e($delivery->pickup_latitude); ?>;
const pickupLng = <?php echo e($delivery->pickup_longitude); ?>;
const deliveryLat = <?php echo e($delivery->delivery_latitude); ?>;
const deliveryLng = <?php echo e($delivery->delivery_longitude); ?>;
const pickupAddress = <?php echo json_encode($delivery->pickup_adresse); ?>;
const deliveryAddress = <?php echo json_encode($delivery->delivery_adresse); ?>;

const pickupIcon = L.divIcon({
    html: '<div style="background:#22c55e;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"><i class="bx bx-package" style="font-size:18px;"></i></div>',
    className: '',
    iconSize: [36, 36],
    iconAnchor: [18, 18]
});

const deliveryIcon = L.divIcon({
    html: '<div style="background:#ef4444;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"><i class="bx bx-current-location" style="font-size:18px;"></i></div>',
    className: '',
    iconSize: [36, 36],
    iconAnchor: [18, 18]
});

const riderIcon = L.divIcon({
    html: '<div style="background:#3b82f6;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);"><i class="bx bxs-motorcycle" style="font-size:16px;"></i></div>',
    className: '',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
});

function initMap() {
    const center = [(pickupLat + deliveryLat) / 2, (pickupLng + deliveryLng) / 2];
    map = L.map('map', { zoomControl: true }).setView(center, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    pickupMarker = L.marker([pickupLat, pickupLng], { icon: pickupIcon })
        .addTo(map)
        .bindPopup('<strong>Point de retrait</strong><br>' + pickupAddress);

    deliveryMarker = L.marker([deliveryLat, deliveryLng], { icon: deliveryIcon })
        .addTo(map)
        .bindPopup('<strong>Point de livraison</strong><br>' + deliveryAddress);

    const bounds = L.latLngBounds([[pickupLat, pickupLng], [deliveryLat, deliveryLng]]);
    map.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });

    setTimeout(() => map.invalidateSize(), 300);
}

function setRiderMarker(lat, lng) {
    if (riderMarker) {
        riderMarker.setLatLng([lat, lng]);
    } else {
        riderMarker = L.marker([lat, lng], { icon: riderIcon }).addTo(map).bindPopup('Position du livreur');
    }
}

function setTrackLine(points) {
    const latlngs = points.map(p => [p.lat, p.lng]);
    if (trackLine) {
        trackLine.setLatLngs(latlngs);
    } else if (latlngs.length > 0) {
        trackLine = L.polyline(latlngs, { color: '#3b82f6', weight: 4, opacity: 0.7 }).addTo(map);
    }
}

function fetchTrack() {
    fetch('<?php echo e(route("deliveries.track", $delivery->id)); ?>')
        .then(res => res.json())
        .then(data => {
            if (data.track && data.track.length > 0) {
                setTrackLine(data.track);
                if (data.last_position) {
                    setRiderMarker(data.last_position.lat, data.last_position.lng);
                }
            } else if (data.rider_position && data.rider_position.lat) {
                setRiderMarker(data.rider_position.lat, data.rider_position.lng);
            }

            if (data.last_position) {
                document.getElementById('riderInfo').style.display = 'block';
                document.getElementById('riderLastUpdate').textContent =
                    'Mis à jour il y a quelques secondes';
            }

            const steps = document.getElementById('statusSteps');
            if (steps) {
                const icons = steps.querySelectorAll('.bx');
                icons.forEach((icon, i) => {
                    if (data.status === 'acceptee' && i === 0) {
                        icon.closest('div').className = 'd-flex align-items-center mb-2 text-success';
                    } else if (data.status === 'en_cours' && i <= 1) {
                        icon.closest('div').className = 'd-flex align-items-center mb-2 text-success';
                    } else if (data.status === 'effectuee') {
                        icon.closest('div').className = 'd-flex align-items-center mb-2 text-success';
                    }
                });
            }
        })
        .catch(err => console.error('Track fetch error:', err));
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.container-fluid');
    const nav = document.querySelector('.navbar');
    if (container && nav) {
        container.style.height = (window.innerHeight - nav.offsetHeight) + 'px';
    }
    initMap();
    fetchTrack();
    pollInterval = setInterval(fetchTrack, 10000);
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views/deliveries/tracking.blade.php ENDPATH**/ ?>