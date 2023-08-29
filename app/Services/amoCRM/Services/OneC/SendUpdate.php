<?php

namespace App\Services\amoCRM\Services\OneC;

use App\Models\OneC\Pay;
use App\Services\amoCRM\Client;
use Illuminate\Support\Facades\Artisan;

class SendUpdate
{
    public static function run(Client $amoApi, Pay $pay)
    {
        Artisan::call('1c:pay-update '.$pay->id);
    }
}
