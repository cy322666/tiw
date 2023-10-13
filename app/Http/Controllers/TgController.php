<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Tg\Transaction;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Contacts;
use App\Services\amoCRM\Models\Leads;
use App\Services\amoCRM\Models\Notes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class TgController extends Controller
{
    /**
     * @throws \Exception
     *
     * формирует ссылку для менеджера
     */
    public function link(Request $request)
    {
        $leadId = $request->leads['add'][0]['id'] ?? $request->leads['status'][0]['id'];

        Transaction::query()->create([
            'lead_id' => $leadId,
        ]);

        $amoApi = (new Client(Account::first()))->init();

        $lead = $amoApi->service->leads()->find($leadId);

        $lead->cf('Ссылка на чат')->setValue('https://hub.blackclever.ru/tiw/public/api/tg/redirect?lead_id='.$leadId);
        $lead->save();
    }

    //перенесено на сторону конструктора
    public function redirect(Request $request)
    {
        $transaction = Transaction::query()
            ->where('lead_id', $request->lead_id)
            ->first();

        if ($transaction) {

            $transaction->wait = true;
            $transaction->save();
        }

        $url = 'https://t.me/Takeandwakerussia_bot?start='.$request->lead_id;

        return Redirect::to($url);
    }

    public function quiz(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $amoApi = (new Client(Account::first()))->init();

        $text  = $request->comment;
        $phone = $request->phone;
        $name  = $request->name;
        $email = $request->email;

        $contact = Contacts::search([
            'Телефоны' => [$phone],
            'Почта' => $email,
        ], $amoApi);

        if (!$contact)
            $contact = Contacts::create($amoApi, $name);

        $contact = $amoApi
            ->service
            ->contacts()
            ->find($contact->id);

        $lead = Leads::create(
            $contact,
            [],
            'Заявка Квиз Телеграм бот',
        );

        $lead->attachTag('quiz');
        $lead->attachTag('bot');
        $lead->save();

        Notes::addOne($lead, $text);
    }

    //своя реализация кажется не актуальна
    public function hook(Request $request)
    {
//        Log::info(__METHOD__, $request->toArray());
//
//        if (!empty($request->message['new_chat_member']['id'])) {
//
//            $memberInfo = $request->message['new_chat_member'];
//
//            $transaction = Transaction::query()
//                ->where('wait', true)
//                ->where('updated_at', '>', Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'))
//                ->first();
//
//            if ($transaction) {
//
//                $transaction->msg_id  = $request['message_id'];
//                $transaction->user_id = $memberInfo['id'];
//                $transaction->is_bot  = $memberInfo['is_bot'];
//                $transaction->first_name = $memberInfo['first_name'];
//                $transaction->username   = $memberInfo['username'];
//                $transaction->wait = false;
//                $transaction->save();
//
//                $amoApi = (new Client(Account::first()))->init();
//
//                $lead = $amoApi->service->leads()->find($transaction->lead_id);
//
//                $lead->attachTag('вступилВчат');
//                $lead->save();
//            }
//        }
    }

    public function shipment(Request $request)
    {
//        Log::info(__METHOD__, $request->toArray());

        if ($request->leads['update'][0]['status_id'] == 142 &&
            $request->leads['update'][0]['pipeline_id'] == 6770222) {

            if (empty($request->leads['update'][0]['tags'])) exit;

            Log::info(__METHOD__, $request->toArray());

            foreach ($request->leads['update'][0]['tags'] as $tag) {

                if ($tag['name'] == 'отгрузка') {

                    foreach ($request->leads['update'][0]['custom_fields'] as $field) {

                        if ($field['name'] == 'Отгрузка отправлена' && $field['values'][0]['value'] == '1') {

                            Log::info(__METHOD__, ['повторная отправка']);

                            exit;
                        }
                    }

                    Log::info(__METHOD__, [
                        'отгрузка найдена',
                        'lead_id' => $request->leads['update'][0]['id'],
                    ]);

                    $amoApi = (new Client(Account::first()))->init();

                    $lead = $amoApi->service->leads()->find($request->leads['update'][0]['id']);

                    $lead->cf('Отгрузка отправлена')->setValue('1');
                    $lead->save();

                    $contact = $lead->contact;

                    $tgId = $contact->cf('tg_id')->getValue() ?? exit;

                    Http::get('https://nicktech.ru/TH/add_to_bot.php', [
                        'user_id' => $tgId,
                        'api_key' => \env('TG_CONSTRUCTOR_API_KEY'),
                        'channel' => 'TH',
                        'bot_id'  => \env('TG_CONSTRUCTOR_BOT_ID'),
                        'step_id' => \env('TG_CONSTRUCTOR_STEP_ID'),
                        'force'   => 1,
                    ]);

                    Log::info(__METHOD__, [
                        'отгрузка отправлена',
                        'tg_id' => $tgId,
                        'lead_id' => $request->leads['update'][0]['id'],
                    ]);
                }
            }
        }
    }

    // при переходе по ссылке отправляется хук сюда
    public function constructor(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $amoApi = (new Client(Account::first()))->init();

        $lead = $amoApi->service->leads()->find($request->lead_id);

        $contact = $lead->contact;
        $contact->cf('tg_id')->setValue($request->tg_id);
        $contact->save();
    }
}










