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
            'body' => json_encode($request->toArray()),
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
            ->where('created_at', '<', Carbon::now()->subMinutes(2)->format('Y-m-d H:i:s'))
            ->where('status', 0)
            ->limit(10)
            ->get();

        foreach ($marquizs as $marquiz) {

            Artisan::call('app:marquiz-send', ['marquiz' => $marquiz->id]);
        }
    }

    /**
        //приходит таска
        //проверяем время
        //если рабочее то ставим
        //если не рабочее то ставим на утро
     *
     * @throws \Exception
     */
    public function task(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $amoApi = (new Client(Account::query()->first()))->init();

        $leadId = 16858759;//$request->toArray()['leads']['add'][0]['id'] ?? $request->toArray()['leads']['status'][0]['id'];

        $lead = $amoApi->service->leads()->find($leadId);

        $workAt = Carbon::parse(Carbon::now()->addHours(2)->format('Y-m-d').' 10:00:00');
        $workTo = Carbon::parse(Carbon::now()->addHours(2)->format('Y-m-d').' 19:00:00');

        if (Carbon::now()->addHours(2) > $workAt) {

            if (Carbon::now()->addHours(2) < $workTo) {
                //10-19

                Log::info(__METHOD__.' : work time');

                $task = $amoApi->service->tasks()->create();
                $task->text = 'Новая задача';
                $task->element_type = 2;
                $task->element_id = $leadId;
                $task->responsible_user_id = $lead->responsible_user_id;
                $task->duration = 60 * 60;
                $task->complete_till_at = Carbon::now()->addMinute()->timestamp;
                $task->save();
            } else {
                //19-00

                Log::info(__METHOD__.' : past work');

                $task = $amoApi->service->tasks()->create();
                $task->text = 'Новая задача';
                $task->element_type = 2;
                $task->element_id = $leadId;
                $task->responsible_user_id = $lead->responsible_user_id;
                $task->duration = 60 * 60;
                $task->complete_till_at = $workAt->addDay()->timestamp;
                $task->save();
            }
        } else {
            //00-10

            Log::info(__METHOD__.' : pre work');

            $task = $amoApi->service->tasks()->create();
            $task->text = 'Новая задача';
            $task->element_type = 2;
            $task->element_id = $leadId;
            $task->responsible_user_id = $lead->responsible_user_id;
            $task->duration = 60 * 60;
            $task->complete_till_at = $workAt->timestamp;
            $task->save();
        }
    }
}
