<?php

namespace App\Services\amoCRM\Services\Site;

use App\Models\Account;
use App\Models\Site;
use App\Services\amoCRM\Client;
use Exception;

class SiteSend
{
    /**
     * @throws Exception
     */
    public static function send(Site $site) :bool
    {
        $amoApi = (new Client(Account::query()->first()))->init();

        $service = match ($site->action) {
            'order-received', 'order' => OrderAction::class,
            'credit-form' => CreditAction::class,
            default => SiteAction::class,
        };

        //$lead->attachTags(['ИнОплата']);

        return (new $service($amoApi))->send($site, json_decode($site->body));
    }
}
