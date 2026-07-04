<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Notifications</h2>
        <form action="<?php echo e(route('notifications.readAll')); ?>" method="POST" style="display:inline;">
            <?php echo csrf_field(); ?>
            <button class="btn btn-outline-secondary btn-sm" type="submit">Tout marquer comme lu</button>
        </form>
    </div>

    <?php if($notifications->isEmpty()): ?>
        <div class="text-center py-5">
            <i class='bx bx-bell' style="font-size:4rem;color:var(--gray-400);"></i>
            <p class="text-muted mt-3">Aucune notification pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="list-group-item list-group-item-action border-0 shadow-sm mb-2 notification-item <?php echo e(!$notif->is_read ? 'bg-light' : ''); ?>" style="border-radius:12px;" data-id="<?php echo e($notif->id); ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle <?php echo e($notif->is_read ? 'bg-light' : 'bg-primary bg-opacity-10'); ?> d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                            <?php switch($notif->type):
                                case ('order_created'): ?>
                                <?php case ('order_paid'): ?>
                                    <i class='bx bx-cart fs-4 text-primary'></i>
                                    <?php break; ?>
                                <?php case ('delivery_assigned'): ?>
                                <?php case ('delivery_accepted'): ?>
                                <?php case ('delivery_completed'): ?>
                                    <i class='bx bxs-truck fs-4 text-warning'></i>
                                    <?php break; ?>
                                <?php case ('message'): ?>
                                    <i class='bx bx-message-dots fs-4 text-success'></i>
                                    <?php break; ?>
                                <?php default: ?>
                                    <i class='bx bx-bell fs-4 text-muted'></i>
                            <?php endswitch; ?>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold mb-0 <?php echo e(!$notif->is_read ? '' : 'text-muted'); ?>"><?php echo e($notif->title); ?></h6>
                                <small class="text-muted flex-shrink-0"><?php echo e($notif->created_at->diffForHumans()); ?></small>
                            </div>
                            <p class="mb-0 text-muted small"><?php echo e($notif->message); ?></p>
                            <?php $notifData = $notif->data ?? []; ?>
                            <?php if(isset($notifData['delivery_id'])): ?>
                                <a href="<?php echo e(route('deliveries.tracking', $notifData['delivery_id'])); ?>" class="btn btn-sm btn-outline-info mt-1">
                                    <i class='bx bx-map'></i> Suivre la livraison
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php if(!$notif->is_read): ?>
                            <span class="badge bg-primary rounded-pill" style="min-width:10px;height:10px;padding:0;cursor:pointer;" onclick="markRead(this, '<?php echo e($notif->id); ?>')"></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mt-4">
            <?php echo e($notifications->links()); ?>

        </div>
    <?php endif; ?>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function markRead(el, id) {
    fetch('/notifications/' + id + '/read', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' }
    }).then(res => res.json()).then(data => {
        if (data.success) {
            el.style.display = 'none';
            el.closest('.notification-item').classList.remove('bg-light');
            el.closest('.notification-item').querySelector('h6').classList.add('text-muted');
            const badge = document.getElementById('notif-badge');
            if (badge) {
                let count = parseInt(badge.textContent);
                count--;
                if (count <= 0) badge.style.display = 'none';
                else badge.textContent = count;
            }
        }
    });
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views\notifications\index.blade.php ENDPATH**/ ?>