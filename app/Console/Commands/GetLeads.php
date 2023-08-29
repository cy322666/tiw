<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\amoCRM\Client;
use Illuminate\Console\Command;

class GetLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {
        $amoApi = (new Client(Account::first()))->init();

//        for ()
        $leads = $amoApi
            ->service
            ->ajax()
            ->get('');
    }
}
