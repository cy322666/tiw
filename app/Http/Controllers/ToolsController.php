<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Contacts;
use App\Services\amoCRM\Models\Leads;
use App\Services\amoCRM\Models\Notes;
use App\Services\amoCRM\Models\Tags;
use Carbon\Carbon;
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

    /**
     * @throws \Exception
     */
    public function utms(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $leadId = $request->toArray()['leads']['add'][0]['id'] ?? $request->toArray()['leads']['status'][0]['id'];

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

            if (!$contact->cf('utm_source')->getValue()) {

                $lead->cf('first_utm_source')->setValue($contact->cf('utm_source')->getValue());
            }

            if (!$contact->cf('utm_medium')->getValue()) {

                $lead->cf('first_utm_medium')->setValue($contact->cf('utm_medium')->getValue());
            }

            if (!$contact->cf('utm_content')->getValue()) {

                $lead->cf('first_utm_content')->setValue($contact->cf('utm_content')->getValue());
            }

            if (!$contact->cf('utm_campaign')->getValue()) {

                $lead->cf('first_utm_campaign')->setValue($contact->cf('utm_campaign')->getValue());
            }

            if (!$contact->cf('utm_term')->getValue()) {

                $lead->cf('first_utm_term')->setValue($contact->cf('utm_term')->getValue());
            }

            $lead->save();
        }
    }

    public function marquiz(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $amoApi = (new Client(Account::query()->first()))
            ->init()
            ->initLogs();

        $body = $request->answers;

        $phone = $request['contacts']['phone'] ?? null;
        $email = $request['contacts']['email'] ?? null;
        $formname = 'Новая заявка с Marquiz';
        $name = $body[0]['a'];
        $city = $body[1]['a'];
        $roistat = $request['extra']['cookies']['roistat_visit'] ?? null;

        $contact = Contacts::search([
            'Телефоны' => [$phone],
            'Почта' => $email,
        ], $amoApi);

        if (!$contact)
            $contact = Contacts::create($amoApi, $name ?? 'Неизвестно');

        $contact = $amoApi
            ->service
            ->contacts()
            ->find($contact->id);

        $contact = Contacts::update($contact, [
            'Телефоны' => [$phone],
            'Почта' => $email,
        ]);

        $lead = Leads::search($contact, $amoApi, 6770222);

        if (!$lead) {

            $lead = Leads::create(
                $contact, [
                    'responsible_user_id' => $contact->responsible_user_id,
            ], $formname);
        } else {
            try {
                $amoApi->service->salesbots()->start(15949, $lead->id, $entity_type = 2);

            } catch (\Throwable $e) {

                Log::info(__METHOD__, [$e->getMessage(), $e->getTraceAsString()]);
            }
        }

        $lead->cf('Город')->setValue($city);
        $lead->cf('Марквиз дата заявки')->setValue(Carbon::now()->timezone('Europe/Moscow')->format('d.m.Y'));
//
        if(!empty($request['extra']['utm']['term']))
            $lead->cf('utm_term')->setValue($request['extra']['utm']['term']);

        if(!empty($request['extra']['utm']['source']))
            $lead->cf('utm_source')->setValue($request['extra']['utm']['source']);

        if(!empty($request['extra']['utm']['medium']))
            $lead->cf('utm_medium')->setValue($request['extra']['utm']['medium']);

        if(!empty($request['extra']['utm']['content']))
            $lead->cf('utm_content')->setValue($request['extra']['utm']['content']);

        if(!empty($request['extra']['utm']['campaign']))
            $lead->cf('utm_campaign')->setValue($request['extra']['utm']['campaign']);

        $lead->cf('Квиз. Насколько интересно')->setValue($body[2]['a']);
        $lead->cf('Квиз. Когда планируете')->setValue($body[3]['a']);
        $lead->cf('Квиз. Почему заинтересовала')->setValue($body[5]['a']);
        $lead->cf('Квиз. Рассматривали ли уже')->setValue($body[6]['a']);
        $lead->cf('Квиз. Финансовые возможности')->setValue($body[4]['a']);
        $lead->cf('roistat')->setValue($roistat);

        $lead->attachTag('marquiz');
        $lead->save();

        Notes::addOne($lead, implode("\n", [
            $lead->cf('Квиз. Насколько интересно')->getValue(),
            $lead->cf('Квиз. Когда планируете')->getValue(),
            $lead->cf('Квиз. Почему заинтересовала')->getValue(),
            $lead->cf('Квиз. Рассматривали ли уже')->getValue(),
            $lead->cf('Квиз. Финансовые возможности')->getValue(),
        ]));
    }
}
