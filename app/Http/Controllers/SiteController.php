<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Notes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    /**
     * @throws \Exception
     */
    public function quiz(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $amoApi = (new Client(Account::first()))->init();

        $arrRoistat = explode('=', explode(';', $request->toArray()['COOKIES'])[28]);

        if ($arrRoistat[0] == 'roistat_visit') {

            $roistat = $arrRoistat[1];
        }

        if (empty($roistat)) {

            Log::error(__METHOD__, ['empty roistat']);

            exit;
        }

        $lead = $amoApi->service
            ->leads()
            ->searchByCustomField($roistat, 'roistat', 1);

        $lead = $lead->first();

        if($lead) {

            Log::info(__METHOD__, [
                'lead_id' => $lead->id,
            ]);

            $a = $request->Рассматривали_ли_уже_потенциальные_места_для_установки_своей_кофейни_самообслуживания;
            $b = $request->Почему_вас_заинтересовали_кофейни_самообслуживания_Какая_у_вас_цель;
            $c = $request->Имеете_ли_финансовые_возможности_до_500К_на_открытие_кофейни;
            $d = $request->Когда_планируете_открывать_кофейню;
            $e = $request->Насколько_вам_интересен_бизнес_на_кофейнях_самообслуживания;
            $f = $request->Из_какого_вы_города;

            $lead->cf('Рассматривали_ли_уже_потенциальные_места_для_установки_своей_кофейни_самообслуживания')->setValue($a);
            $lead->cf('Почему_вас_заинтересовали_кофейни_самообслуживания_Какая_у_вас_цель')->setValue($b);
            $lead->cf('Имеете_ли_финансовые_возможности_до_500К_на_открытие_кофейни')->setValue($c);
            $lead->cf('Когда_планируете_открывать_кофейню')->setValue($d);
            $lead->cf('Насколько_вам_интересен_бизнес_на_кофейнях_самообслуживания')->setValue($e);
            $lead->cf('Из_какого_вы_города')->setValue($f);

            $lead->attachTag('квиз');

            Notes::addOne($lead, implode("\n", [
                'Рассматривали_ли_уже_потенциальные_места_для_установки_своей_кофейни_самообслуживания - '.$a,
                'Почему_вас_заинтересовали_кофейни_самообслуживания_Какая_у_вас_цель - '.$b,
                'Имеете_ли_финансовые_возможности_до_500К_на_открытие_кофейни - '.$c,
                'Когда_планируете_открывать_кофейню - '.$d,
                'Насколько_вам_интересен_бизнес_на_кофейнях_самообслуживания - '.$e,
                'Из_какого_вы_города - '.$f,
            ]));
        }
    }
}
