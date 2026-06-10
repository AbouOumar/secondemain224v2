@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="mb-3">
        <a href="{{ route('messages.index') }}" class="btn btn-sm btn-outline-secondary">&larr; Toutes les conversations</a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:18px;">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                <i class='bx bx-user fs-4 text-muted'></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                @if(isset($article))
                    <small class="text-muted">À propos de : <a href="{{ route('articles.show', $article->slug) }}" class="text-primary">{{ $article->titre }}</a></small>
                @endif
            </div>
        </div>

        <div class="card-body px-4" style="max-height:60vh;overflow-y:auto;" id="messagesContainer">
            @if($conversation->isEmpty())
                <div class="text-center py-5">
                    <p class="text-muted">Aucun message. Envoyez le premier message !</p>
                </div>
            @else
                @foreach($conversation as $msg)
                    <div class="d-flex mb-3 {{ $msg->sender_id === Auth::id() ? 'justify-content-end' : '' }}">
                        <div class="rounded-3 p-3 {{ $msg->sender_id === Auth::id() ? 'bg-primary text-white' : 'bg-light' }}" style="max-width:75%;">
                            <p class="mb-1">{{ $msg->message }}</p>
                            <small class="{{ $msg->sender_id === Auth::id() ? 'text-white-50' : 'text-muted' }}">{{ $msg->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="card-footer bg-white border-0 px-4 pb-4">
            <form method="POST" action="{{ route('messages.store', $user->id) }}">
                @csrf
                @if(isset($article))
                    <input type="hidden" name="article_id" value="{{ $article->id }}">
                @endif
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
@endsection
