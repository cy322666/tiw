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

        $arrRoistat = explode(';', $request->toArray()['COOKIES']);

        foreach ($arrRoistat as $value) {

            if(strripos($value, 'roistat_visit=') !== false) {

                $roistat = trim(explode('=', $value)[1]);
            }
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

            $lead->cf('РАССМАТРИВАЛИ_ЛИ_УЖЕ_ПОТЕНЦИАЛЬНЫЕ_МЕСТА_ДЛЯ_УСТАНОВКИ_СВОЕЙ_КОФЕЙНИ_САМООБСЛУЖИВАНИЯ')->setValue($a);
            $lead->cf('ПОЧЕМУ_ВАС_ЗАИНТЕРЕСОВАЛИ_КОФЕЙНИ_САМООБСЛУЖИВАНИЯ_КАКАЯ_У_ВАС_ЦЕЛЬ')->setValue($b);
            $lead->cf('ИМЕЕТЕ_ЛИ_ФИНАНСОВЫЕ_ВОЗМОЖНОСТИ_ДО_500К_НА_ОТКРЫТИЕ_КОФЕЙНИ')->setValue($c);
            $lead->cf('КОГДА_ПЛАНИРУЕТЕ_ОТКРЫВАТЬ_КОФЕЙНЮ')->setValue($d);
            $lead->cf('НАСКОЛЬКО_ВАМ_ИНТЕРЕСЕН_БИЗНЕС_НА_КОФЕЙНЯХ_САМООБСЛУЖИВАНИЯ')->setValue($e);
            $lead->cf('ИЗ_КАКОГО_ВЫ_ГОРОДА')->setValue($f);

            $lead->attachTag('квиз');

            Notes::addOne($lead, implode("\n", [
                'Рассматривали_ли_уже_потенциальные_места_для_установки_своей_кофейни_самообслуживания - '.$a,
                '',
                'Почему_вас_заинтересовали_кофейни_самообслуживания_Какая_у_вас_цель - '.$b,
                '',
                'Имеете_ли_финансовые_возможности_до_500К_на_открытие_кофейни - '.$c,
                '',
                'Когда_планируете_открывать_кофейню - '.$d,
                '',
                'Насколько_вам_интересен_бизнес_на_кофейнях_самообслуживания - '.$e,
                '',
                'Из_какого_вы_города - '.$f,
            ]));
        }
    }
}
