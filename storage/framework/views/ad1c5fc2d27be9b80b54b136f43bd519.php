<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Mes conversations</h2>
        <?php if(isset($unreadCount) && $unreadCount > 0): ?>
            <span class="badge bg-danger rounded-pill"><?php echo e($unreadCount); ?> non lu(s)</span>
        <?php endif; ?>
    </div>

    <?php if($conversations->isEmpty()): ?>
        <div class="text-center py-5">
            <i class='bx bx-message-dots' style="font-size:4rem;color:var(--gray-400);"></i>
            <p class="text-muted mt-3">Aucune conversation pour le moment.</p>
            <a href="<?php echo e(url('/')); ?>" class="btn btn-primary">Parcourir les annonces</a>
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $other = $msg->sender_id === Auth::id() ? $msg->receiver : $msg->sender;
                    $route = route('messages.show', ['user' => $other->id]);
                    if ($msg->article_id) {
                        $route = route('messages.show', ['user' => $other->id, 'article' => $msg->article_id]);
                    }
                ?>
                <a href="<?php echo e($route); ?>" class="list-group-item list-group-item-action border-0 shadow-sm mb-2" style="border-radius:12px;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                            <i class='bx bx-user fs-4 text-muted'></i>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold mb-0 text-truncate"><?php echo e($other->name); ?></h6>
                                <small class="text-muted flex-shrink-0"><?php echo e($msg->created_at->diffForHumans()); ?></small>
                            </div>
                            <?php if($msg->article): ?>
                                <small class="text-primary"><?php echo e($msg->article->titre); ?></small>
                            <?php endif; ?>
                            <p class="mb-0 text-muted text-truncate small"><?php echo e($msg->message); ?></p>
                        </div>
                        <?php if(!$msg->is_read && $msg->receiver_id === Auth::id()): ?>
                            <span class="badge bg-danger rounded-pill" style="min-width:10px;height:10px;padding:0;"></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views/messages/index.blade.php ENDPATH**/ ?>