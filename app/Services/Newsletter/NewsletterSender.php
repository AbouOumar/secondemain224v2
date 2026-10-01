<?php

namespace App\Services\Newsletter;

use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoi progressif des campagnes : quelques e-mails par minute, pour rester
 * sous la limite d'envoi de l'hébergement mutualisé.
 */
class NewsletterSender
{
    public function launch(NewsletterCampaign $campaign): void
    {
        $campaign->forceFill([
            'status' => NewsletterCampaign::SENDING,
            'started_at' => now(),
        ])->save();
    }

    public function sendTest(NewsletterCampaign $campaign, User $user): void
    {
        Mail::to($user->email)->send(new NewsletterMail($campaign, $user));
    }

    /**
     * Envoie le prochain lot, toutes campagnes en cours confondues.
     * Retourne le nombre d'e-mails traités.
     */
    public function sendNextBatch(?int $limit = null): int
    {
        $remaining = $limit ?? config('mail.newsletter.per_minute');
        $processed = 0;

        $campaigns = NewsletterCampaign::where('status', NewsletterCampaign::SENDING)->orderBy('started_at')->get();

        foreach ($campaigns as $campaign) {
            if ($remaining <= 0) {
                break;
            }

            $recipients = $campaign->pendingRecipients()->orderBy('id')->limit($remaining)->get();

            foreach ($recipients as $user) {
                $this->deliver($campaign, $user);
                $remaining--;
                $processed++;
            }

            if (! $campaign->pendingRecipients()->exists()) {
                $campaign->forceFill(['status' => NewsletterCampaign::SENT, 'sent_at' => now()])->save();
            }
        }

        return $processed;
    }

    private function deliver(NewsletterCampaign $campaign, User $user): void
    {
        $failed = false;

        try {
            Mail::to($user->email)->send(new NewsletterMail($campaign, $user));
        } catch (Throwable $e) {
            $failed = true;
            Log::warning('Échec envoi newsletter', [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Enregistré même en cas d'échec : pas de nouvelle tentative en boucle.
        NewsletterDelivery::create([
            'newsletter_campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'failed' => $failed,
        ]);

        $campaign->increment($failed ? 'failed_count' : 'sent_count');
    }
}
