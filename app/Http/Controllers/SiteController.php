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

        $lead = $amoApi->service
            ->leads()
            ->searchByCustomField($request->tranid, 'TRANID', 1);

        $lead = $lead->first();

        if($lead) {

            $a = $request->РАССМАТРИВАЛИ_ЛИ_УЖЕ_ПОТЕНЦИАЛЬНЫЕ_МЕСТА_ДЛЯ_УСТАНОВКИ_СВОЕЙ_КОФЕЙНИ_САМООБСЛУЖИВАНИЯ;
            $b = $request->ПОЧЕМУ_ВАС_ЗАИНТЕРЕСОВАЛИ_КОФЕЙНИ_САМООБСЛУЖИВАНИЯ_КАКАЯ_У_ВАС_ЦЕЛЬ;
            $c = $request->ИМЕЕТЕ_ЛИ_ФИНАНСОВЫЕ_ВОЗМОЖНОСТИ_ДО_500К_НА_ОТКРЫТИЕ_КОФЕЙНИ;
            $d = $request->КОГДА_ПЛАНИРУЕТЕ_ОТКРЫВАТЬ_КОФЕЙНЮ;
            $e = $request->НАСКОЛЬКО_ВАМ_ИНТЕРЕСЕН_БИЗНЕС_НА_КОФЕЙНЯХ_САМООБСЛУЖИВАНИЯ;
            $f = $request->ИЗ_КАКОГО_ВЫ_ГОРОДА;

            $lead->cf('РАССМАТРИВАЛИ_ЛИ_УЖЕ_ПОТЕНЦИАЛЬНЫЕ_МЕСТА_ДЛЯ_УСТАНОВКИ_СВОЕЙ_КОФЕЙНИ_САМООБСЛУЖИВАНИЯ')->setValue($a);
            $lead->cf('ПОЧЕМУ_ВАС_ЗАИНТЕРЕСОВАЛИ_КОФЕЙНИ_САМООБСЛУЖИВАНИЯ_КАКАЯ_У_ВАС_ЦЕЛЬ')->setValue($b);
            $lead->cf('ИМЕЕТЕ_ЛИ_ФИНАНСОВЫЕ_ВОЗМОЖНОСТИ_ДО_500К_НА_ОТКРЫТИЕ_КОФЕЙНИ')->setValue($c);
            $lead->cf('КОГДА_ПЛАНИРУЕТЕ_ОТКРЫВАТЬ_КОФЕЙНЮ')->setValue($d);
            $lead->cf('НАСКОЛЬКО_ВАМ_ИНТЕРЕСЕН_БИЗНЕС_НА_КОФЕЙНЯХ_САМООБСЛУЖИВАНИЯ')->setValue($e);
            $lead->cf('ИЗ_КАКОГО_ВЫ_ГОРОДА')->setValue($f);

            $lead->attachTag('квиз');

            Notes::addOne($lead, implode("\n", [
                'РАССМАТРИВАЛИ_ЛИ_УЖЕ_ПОТЕНЦИАЛЬНЫЕ_МЕСТА_ДЛЯ_УСТАНОВКИ_СВОЕЙ_КОФЕЙНИ_САМООБСЛУЖИВАНИЯ - '.$a,
                'ПОЧЕМУ_ВАС_ЗАИНТЕРЕСОВАЛИ_КОФЕЙНИ_САМООБСЛУЖИВАНИЯ_КАКАЯ_У_ВАС_ЦЕЛЬ - '.$b,
                'ИМЕЕТЕ_ЛИ_ФИНАНСОВЫЕ_ВОЗМОЖНОСТИ_ДО_500К_НА_ОТКРЫТИЕ_КОФЕЙНИ - '.$c,
                'КОГДА_ПЛАНИРУЕТЕ_ОТКРЫВАТЬ_КОФЕЙНЮ - '.$d,
                'НАСКОЛЬКО_ВАМ_ИНТЕРЕСЕН_БИЗНЕС_НА_КОФЕЙНЯХ_САМООБСЛУЖИВАНИЯ - '.$e,
                'ИЗ_КАКОГО_ВЫ_ГОРОДА - '.$f,
            ]));
        }
    }
}
