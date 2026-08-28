{{--
    Modale de signalement générique. Usage :
    @include('partials.report-modal', ['type' => 'article', 'id' => $article->id, 'label' => $article->titre])
    @include('partials.report-modal', ['type' => 'user', 'id' => $user->id, 'label' => $user->name])
--}}
<div class="modal fade" id="report-{{ $type }}-{{ $id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('reports.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="reportable_type" value="{{ $type }}">
            <input type="hidden" name="reportable_id" value="{{ $id }}">
            <div class="modal-header">
                <h5 class="modal-title">Signaler « {{ $label }} »</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Motif *</label>
                    <select name="reason" class="form-select" required>
                        <option value="">Sélectionnez un motif</option>
                        @foreach(\App\Enums\ReportReason::cases() as $reason)
                            <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label small fw-medium">Détails (facultatif)</label>
                    <textarea name="description" class="form-control" rows="3" maxlength="1000" placeholder="Décrivez le problème..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger"><i class='bx bx-flag'></i> Envoyer le signalement</button>
            </div>
        </form>
    </div>
</div>
