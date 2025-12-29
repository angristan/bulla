<?php

declare(strict_types=1);

namespace App\Actions\Email;

use App\Models\NotificationSubscription;
use Lorisleiva\Actions\Concerns\AsAction;

class Unsubscribe
{
    use AsAction;

    public function handle(string $token): bool
    {
        $subscription = NotificationSubscription::where('unsubscribe_token', $token)->first();

        if (! $subscription) {
            return false;
        }

        if ($subscription->unsubscribed_at) {
            // Already unsubscribed
            return true;
        }

        $subscription->update(['unsubscribed_at' => now()]);

        return true;
    }
}
