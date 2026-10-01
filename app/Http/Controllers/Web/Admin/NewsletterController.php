<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\User;
use App\Services\Newsletter\NewsletterSender;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function __construct(private NewsletterSender $sender) {}

    public function index()
    {
        return view('admin.newsletter.index', [
            'campaigns' => NewsletterCampaign::latest()->paginate(15),
            'subscribersCount' => User::newsletterRecipients()->count(),
        ]);
    }

    public function create()
    {
        return view('admin.newsletter.form', ['campaign' => new NewsletterCampaign]);
    }

    public function store(Request $request)
    {
        $campaign = NewsletterCampaign::create($this->validated($request) + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.newsletter.show', $campaign)->with('success', 'Brouillon enregistré.');
    }

    public function show(NewsletterCampaign $campaign)
    {
        return view('admin.newsletter.show', [
            'campaign' => $campaign,
            'pendingCount' => $campaign->isDraft() || $campaign->status === NewsletterCampaign::SENDING
                ? $campaign->pendingRecipients()->count()
                : 0,
            'perMinute' => config('mail.newsletter.per_minute'),
        ]);
    }

    public function edit(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isDraft(), 403, 'Une campagne envoyée ne peut plus être modifiée.');

        return view('admin.newsletter.form', compact('campaign'));
    }

    public function update(Request $request, NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isDraft(), 403, 'Une campagne envoyée ne peut plus être modifiée.');

        $campaign->update($this->validated($request));

        return redirect()->route('admin.newsletter.show', $campaign)->with('success', 'Brouillon mis à jour.');
    }

    public function destroy(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isDraft(), 403, 'Une campagne envoyée ne peut pas être supprimée.');

        $campaign->delete();

        return redirect()->route('admin.newsletter.index')->with('success', 'Brouillon supprimé.');
    }

    /**
     * Aperçu de l'e-mail tel que le recevra l'administrateur.
     */
    public function preview(Request $request, NewsletterCampaign $campaign)
    {
        return (new NewsletterMail($campaign, $request->user()))->render();
    }

    public function test(Request $request, NewsletterCampaign $campaign)
    {
        $admin = $request->user();

        if (! $admin->email) {
            return back()->with('error', 'Ajoutez une adresse e-mail à votre compte pour recevoir le test.');
        }

        $this->sender->sendTest($campaign, $admin);

        return back()->with('success', "E-mail de test envoyé à {$admin->email}.");
    }

    public function send(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isDraft(), 403, 'Cette campagne a déjà été lancée.');

        $this->sender->launch($campaign);

        return back()->with('success', 'Envoi lancé : les e-mails partent progressivement, par petits lots chaque minute.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'subject' => 'required|string|max:150',
            'content' => 'required|string|max:20000',
        ], [
            'subject.required' => "L'objet est obligatoire.",
            'content.required' => 'Le contenu est obligatoire.',
        ]);
    }
}
