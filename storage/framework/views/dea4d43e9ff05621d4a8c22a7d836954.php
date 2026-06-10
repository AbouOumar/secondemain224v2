<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="mb-3">
        <a href="<?php echo e(route('messages.index')); ?>" class="btn btn-sm btn-outline-secondary">&larr; Toutes les conversations</a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:18px;">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                <i class='bx bx-user fs-4 text-muted'></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0"><?php echo e($user->name); ?></h5>
                <?php if(isset($article)): ?>
                    <small class="text-muted">À propos de : <a href="<?php echo e(route('articles.show', $article->slug)); ?>" class="text-primary"><?php echo e($article->titre); ?></a></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body px-4" style="max-height:60vh;overflow-y:auto;" id="messagesContainer">
            <?php if($conversation->isEmpty()): ?>
                <div class="text-center py-5">
                    <p class="text-muted">Aucun message. Envoyez le premier message !</p>
                </div>
            <?php else: ?>
                <?php $__currentLoopData = $conversation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="d-flex mb-3 <?php echo e($msg->sender_id === Auth::id() ? 'justify-content-end' : ''); ?>">
                        <div class="rounded-3 p-3 <?php echo e($msg->sender_id === Auth::id() ? 'bg-primary text-white' : 'bg-light'); ?>" style="max-width:75%;">
                            <p class="mb-1"><?php echo e($msg->message); ?></p>
                            <small class="<?php echo e($msg->sender_id === Auth::id() ? 'text-white-50' : 'text-muted'); ?>"><?php echo e($msg->created_at->format('d/m/Y H:i')); ?></small>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>

        <div class="card-footer bg-white border-0 px-4 pb-4">
            <form method="POST" action="<?php echo e(route('messages.store', $user->id)); ?>">
                <?php echo csrf_field(); ?>
                <?php if(isset($article)): ?>
                    <input type="hidden" name="article_id" value="<?php echo e($article->id); ?>">
                <?php endif; ?>
                <div class="input-group">
                    <textarea name="message" class="form-control" rows="2" placeholder="Écrivez votre message..." required></textarea>
                    <button class="btn btn-primary px-4" type="submit">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Souare\Documents\secondmainv2\resources\views/messages/show.blade.php ENDPATH**/ ?>