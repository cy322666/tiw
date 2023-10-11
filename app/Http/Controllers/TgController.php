<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Tg\Transaction;
use App\Services\amoCRM\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
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
        Log::info(__METHOD__, $request->toArray());
    }

    // при переходе по ссылке отправляется хук сюда
    public function constructor(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

//        Http::get('https://nicktech.ru/TH/add_to_bot.php', [
//            'user_id' => $request->tg_id,
//            'api_key' => \env('TG_CONSTRUCTOR_API_KEY'),
//            'channel' => 'TH',
//            'bot_id'  => \env('TG_CONSTRUCTOR_BOT_ID'),
//            'step_id' => \env('TG_CONSTRUCTOR_STEP_ID'),
//            'force'   => 1,
//        ]);

        $amoApi = (new Client(Account::first()))->init();

        $lead = $amoApi->service->leads()->find($request->lead_id);

        $contact = $lead->contact;
        $contact->cf('tg_id')->setValue($request->tg_id);
        $contact->save();
    }
}










