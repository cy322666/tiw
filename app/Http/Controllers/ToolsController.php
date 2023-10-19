<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Contacts;
use App\Services\amoCRM\Models\Leads;
use App\Services\amoCRM\Models\Notes;
use App\Services\amoCRM\Models\Tags;
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

    public function marquiz(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $amoApi = (new Client(Account::query()->first()))
            ->init()
            ->initLogs();

        $body = $request->answers;

        $phone = $request['contacts']['phone'];
        $email = $request['contacts']['email'];
        $formname = 'Новая заявка с Marquiz';
        $name = $body[0]->a;
        $city = $body[1]->a;
        $roistat = $request['extra']->cookies->roistat_visit;

        $contact = Contacts::search(['Телефоны' => [$phone]], $amoApi);

        if (!$contact)
            $contact = Contacts::create($amoApi, $name);

        $contact = $amoApi
            ->service
            ->contacts()
            ->find($contact->id);

        $contact = Contacts::update($contact, [
            'Телефоны' => [$phone],
            'Почта' => $email,
        ]);

        $lead = Leads::search($contact, $amoApi, 6770222);

        if (!$lead)
            $lead = Leads::create(
                $contact, [],
    //            ['status_id' => $statusId,],
                $formname,
            );

//
        $lead->cf('Город')->setValue($city);
//
//        $lead->cf('utm_term')->setValue($model->utm_term);
//        $lead->cf('utm_source')->setValue($model->utm_source);
//        $lead->cf('utm_medium')->setValue($model->utm_medium);
//        $lead->cf('utm_content')->setValue($model->utm_content);
//        $lead->cf('utm_campaign')->setValue($model->utm_campaign);

        $lead->cf('Квиз. Насколько интересно')->setValue($body[2]->a);
        $lead->cf('Квиз. Когда планируете')->setValue($body[3]->a);
        $lead->cf('Квиз. Почему заинтересовала')->setValue($body[5]->a);
        $lead->cf('Квиз. Рассматривали ли уже')->setValue($body[6]->a);
        $lead->cf('Квиз. Финансовые возможности')->setValue($body[4]->a);
        $lead->cf('roistat')->setValue($roistat);

        $lead->attachTag('marquiz');
        $lead->save();

//        Notes::addOne($lead, $text);
//
//        $model->lead_id = $lead->id;
//        $model->contact_id = $contact->id;
//        $model->save();
    }
}
