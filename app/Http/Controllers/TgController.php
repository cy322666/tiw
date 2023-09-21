<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Tg\Transaction;
use App\Services\amoCRM\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class TgController extends Controller
{
    /**
     * @throws \Exception
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

    public function redirect(Request $request)
    {
        $transaction = Transaction::query()
            ->where('lead_id', $request->lead_id)
            ->first();

        if ($transaction) {

            $transaction->wait = true;
            $transaction->save();
        }

        return Redirect::to(Env::get('TG_CHAT_LINK'));
    }

    public function hook(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        if (!empty($request->message->new_chat_member->id)) {

            $memberInfo = $request->message->new_chat_member;

            $transaction = Transaction::query()
                ->where('wait', true)
                ->where('updated_at', '>', Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'))
                ->first();

            if ($transaction) {

                $transaction->msg_id = $request->message->message_id;
                $transaction->user_id = $memberInfo->id;
                $transaction->is_bot = $memberInfo->is_bot;
                $transaction->first_name = $memberInfo->first_name;
                $transaction->username = $memberInfo->aleksandr_swedish;
                $transaction->wait = false;
                $transaction->save();
            }
        }
    }
}
