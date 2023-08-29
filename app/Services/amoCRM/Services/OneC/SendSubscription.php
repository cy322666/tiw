<?php

namespace App\Services\amoCRM\Services\OneC;

use App\Models\OneC\Pay;
use App\Services\amoCRM\Client;

class SendSubscription
{
    public static function run(Client $amoApi, Pay $pay)
    {
        //ищем открытую или успешную и крепим
    }
}
