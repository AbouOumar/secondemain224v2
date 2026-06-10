<?php
namespace App\Listeners;
use App\Events\MessageSent;

class BroadcastMessage
{
    public function handle(MessageSent $event): void
    {
        //
    }
}
