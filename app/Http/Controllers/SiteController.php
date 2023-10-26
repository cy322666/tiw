<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Contacts;
use App\Services\amoCRM\Models\Leads;
use App\Services\amoCRM\Models\Notes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SiteController extends Controller
{
    public function web(Request $request)
    {
        if (\request()->get('test') == 'test') exit;

        Log::info(__METHOD__, \request()->toArray());

        $amoApi = (new Client(Account::query()->first()))->init();

        $name  = $request->Name ??  $request->name;
        $phone = $request->Phone ?? $request->phone;

        $text = implode("\n", [
            'Новая заявка с сайта',
            '---------------------------------',
            " - Имя : $name",
            " - Телефон : $phone",
        ]);

        $contact = Contacts::search(['Телефон' => Contacts::clearPhone($phone)], $amoApi);

        if (!$contact) {

            $contact = Contacts::create($amoApi, $name ?? 'Неизвестно');
            $contact = Contacts::update($contact, ['Телефоны' => [Contacts::clearPhone($phone)]]);
        }

        $leadActive = Leads::search($contact, $amoApi, [6770222]);

        if (!$leadActive)
            $lead = Leads::create(
                $contact,
                [],
                $request->type == 'sale' ? 'Заявки с вебинара подарок+скидка' : 'Заявки с вебинара подарок',
            );
        else
            $lead = $leadActive;

        $lead->cf('utm_term')->setValue($request->utm_term);
        $lead->cf('utm_source')->setValue($request->utm_source);
        $lead->cf('utm_medium')->setValue($request->utm_medium);
        $lead->cf('utm_content')->setValue($request->utm_content);
        $lead->cf('utm_campaign')->setValue($request->utm_campaign);

        $arrayUtms = $this->parseCookies($request);

        foreach ($arrayUtms as $key => $utm) {

            try {
                $lead->cf($key)->setValue($utm);

            } catch (\Throwable $exception) {}
        }

        $lead->attachTag($request->type == 'sale' ? 'веб-подарок+скидка' : 'веб-подарок');
        $lead->attachTag('tilda');
        $lead->save();

        Notes::addOne($lead, $text);

        Log::info(__METHOD__, [
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
        ]);
    }

    public function parseCookies($request) : array
    {
        $utms = [];

        if (!empty($request->COOKIES)) {

            $arrayCookies = explode(';', $request->COOKIES ?? '');

            foreach ($arrayCookies as $cookie) {

                $array = explode('=', $cookie);

                $utms[trim($array[0])] = trim(urldecode($array[1] ?? ''));
            }
        }

        return $utms;
    }
}
