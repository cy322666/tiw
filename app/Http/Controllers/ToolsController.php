<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Marquiz;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Contacts;
use App\Services\amoCRM\Models\Leads;
use App\Services\amoCRM\Models\Notes;
use App\Services\amoCRM\Models\Tags;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
                if ($lead->status_id != 142 && $lead->status_id != 143 && $lead->pipeline_id == 6770222) {
                    return $lead;
                }
            });
        }

        if (!empty($leadsActive) && $leadsActive->count() > 1) {

            $leadBase->attachTag('В работе');
        }
    }

    /**
     * @throws \Exception
     */
    public function utms(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $leadId = $request->leads['add'][0]['id'] ?? $request->leads['status'][0]['id'];

        sleep(3);

        $amoApi = (new Client(Account::query()->first()))
            ->init()
            ->initLogs();

        $lead = $amoApi->service->leads()->find($leadId);

        $contact = $lead->contact;

        $firstLead = Leads::searchOld($contact, $amoApi);

        if ($firstLead) {

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

            $contact = $amoApi->service->contacts()->find($contact->id);

            if ($contact->cf('utm_source')->getValue()) {

                $lead->cf('first_utm_source')->setValue($contact->cf('utm_source')->getValue());
            }

            if ($contact->cf('utm_medium')->getValue()) {

                $lead->cf('first_utm_medium')->setValue($contact->cf('utm_medium')->getValue());
            }

            if ($contact->cf('utm_content')->getValue()) {

                $lead->cf('first_utm_content')->setValue($contact->cf('utm_content')->getValue());
            }

            if ($contact->cf('utm_campaign')->getValue()) {

                $lead->cf('first_utm_campaign')->setValue($contact->cf('utm_campaign')->getValue());
            }

            if ($contact->cf('utm_term')->getValue()) {

                $lead->cf('first_utm_term')->setValue($contact->cf('utm_term')->getValue());
            }

            $lead->save();
        }
    }

    public function marquiz(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $body = $request->answers;

        Marquiz::query()->create([
            'body' => json_encode($body),
            'phone' => $request['contacts']['phone'] ?? null,
            'email' => $request['contacts']['email'] ?? null,
            'name' => $body[0]['a'],
            'city' => $body[1]['a'],
            'roistat' => $request['extra']['cookies']['roistat_visit'] ?? null,
        ]);
    }

    public function cron()
    {
        $marquizs = Marquiz::query()
            ->where('created_at', '>', Carbon::now()->subMinutes(2)->format('Y-m-d H:i:s'))
            ->where('status', 0)
            ->limit(10)
            ->get();

        foreach ($marquizs as $marquiz) {

            Artisan::call('app:marquiz-send', ['marquiz' => $marquiz->id]);
        }
    }
}
