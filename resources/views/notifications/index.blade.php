@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Notifications</h2>
        <form action="{{ route('notifications.readAll') }}" method="POST" style="display:inline;">
            @csrf
            <button class="btn btn-outline-secondary btn-sm" type="submit">Tout marquer comme lu</button>
        </form>
    </div>

    @if($notifications->isEmpty())
        <div class="text-center py-5">
            <i class='bx bx-bell' style="font-size:4rem;color:var(--gray-400);"></i>
            <p class="text-muted mt-3">Aucune notification pour le moment.</p>
        </div>
    @else
        <div class="list-group">
            @foreach($notifications as $notif)
                <div class="list-group-item list-group-item-action border-0 shadow-sm mb-2 notification-item {{ !$notif->is_read ? 'bg-light' : '' }}" style="border-radius:12px;" data-id="{{ $notif->id }}">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle {{ $notif->is_read ? 'bg-light' : 'bg-primary bg-opacity-10' }} d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                            @switch($notif->type)
                                @case('order_created')
                                @case('order_paid')
                                    <i class='bx bx-cart fs-4 text-primary'></i>
                                    @break
                                @case('delivery_assigned')
                                @case('delivery_accepted')
                                @case('delivery_completed')
                                    <i class='bx bxs-truck fs-4 text-warning'></i>
                                    @break
                                @case('message')
                                    <i class='bx bx-message-dots fs-4 text-success'></i>
                                    @break
                                @default
                                    <i class='bx bx-bell fs-4 text-muted'></i>
                            @endswitch
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold mb-0 {{ !$notif->is_read ? '' : 'text-muted' }}">{{ $notif->title }}</h6>
                                <small class="text-muted flex-shrink-0">{{ $notif->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-0 text-muted small">{{ $notif->message }}</p>
                            @php $notifData = $notif->data ?? []; @endphp
                            @if(isset($notifData['delivery_id']))
                                <a href="{{ route('deliveries.tracking', $notifData['delivery_id']) }}" class="btn btn-sm btn-outline-info mt-1">
                                    <i class='bx bx-map'></i> Suivre la livraison
                                </a>
                            @endif
                        </div>
                        @if(!$notif->is_read)
                            <span class="badge bg-primary rounded-pill" style="min-width:10px;height:10px;padding:0;cursor:pointer;" onclick="markRead(this, '{{ $notif->id }}')"></span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function markRead(el, id) {
    fetch('/notifications/' + id + '/read', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
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
@endpush
@endsection
