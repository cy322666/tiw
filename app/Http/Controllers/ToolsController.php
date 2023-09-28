<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Leads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ToolsController extends Controller
{
    public function active(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $leadId = $request->toArray()['leads']['add'][0]['id'];

        sleep(10);

        $amoApi = (new Client(Account::query()->first()))
            ->init()
            ->initLogs();

        $leadBase = $amoApi
            ->service
            ->leads()
            ->find($leadId);

        $leads = $leadBase->contact->leads;

        if ($leads->count() > 1) {
            $leadsActive = $leads->filter(function ($lead) {
                if ($lead->status_id != 142 && $lead->status_id != 143) {
                    return $lead;
                }
            });
        }

        if (!empty($leadsActive) && $leadsActive->count() > 1) {

            $leadBase->attachTag('В работе');
        }
    }

    public function utms(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        sleep(3);

        $leadId = $request->toArray()['leads']['add'][0]['id'];

        $amoApi = (new Client(Account::query()->first()))
            ->init()
            ->initLogs();

        $lead = $amoApi->service->leads()->find($leadId);

        $contact = $lead->contact;

        $firstLead = Leads::searchOld($contact, $amoApi);

        if (!$contact->cf('utm_source')->getValue()) {

            $contact->cf('utm_source')->setValue($firstLead->cf('utm_source')->getValue());
        }

        if (!$contact->cf('utm_medium')->getValue()) {

            $contact->cf('utm_medium')->setValue($firstLead->cf('utm_medium')->getValue());
        }

        if (!$contact->cf('utm_content')->getValue()) {

            $contact->cf('utm_content')->setValue($firstLead->cf('utm_content')->getValue());
        }

        if (!$contact->cf('utm_campaign')->getValue()) {

            $contact->cf('utm_campaign')->setValue($firstLead->cf('utm_campaign')->getValue());
        }

        if (!$contact->cf('utm_term')->getValue()) {

            $contact->cf('utm_term')->setValue($firstLead->cf('utm_term')->getValue());
        }

        $contact->save();
    }
}
