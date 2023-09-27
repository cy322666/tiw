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
    /**
     * @throws \Exception
     */
    public function quiz(Request $request)
    {
        Log::info(__METHOD__, $request->toArray());

        $segment = static::getSegment($request);

        $note = match ($segment) {
            'hot'   => 'Деньги есть / Желание есть / Скоро хочет открыть - Закрыть здесь и сейчас',
            'offer' => 'Деньги есть / Желание есть / Не знает, когда откроет - Ускорить офером',
            'idea'  => 'Деньги есть / Желания нет или не выявлено / Не знает, когда откроет - Соблазнить идеей и офером',
            'call'  => 'Денег нет / Желание есть / Скоро хочет открыть - Аккуратный прозвон, но больше догрев',
            'lazy'  => 'Деньги есть / Желания нет или не выявлено / Не знает, когда откроет',
            'academy' => 'Денег нет / Желание есть / Скоро хочет открыть',
            default => 'Неопределенный - Аккуратный прозвон, но больше догрев'
        };

        $amoApi = (new Client(Account::first()))->init();

        $arrRoistat = explode(';', $request->toArray()['COOKIES']);

        foreach ($arrRoistat as $value) {

            if(strripos($value, 'roistat_visit=') !== false) {

                $roistat = (int)trim(explode('=', $value)[1]);
            }
        }

        if (empty($roistat)) {

            Log::error(__METHOD__, ['empty roistat']);

            exit;
        }

        $leads = $amoApi->service
            ->leads()
            ->searchByCustomField((string)$roistat, 'roistat', 10)
            ->toArray();

        foreach ($leads as $lead) {

            if ($lead['status_id'] !== 142 && $lead['status_id'] !== 143) {

                $lead = $amoApi->service->leads()->find($lead['id']);

                break;
            }
        }

        if(!empty($lead)) {

            Log::info(__METHOD__, [
                'lead_id' => $lead->id,
                'roistat' => $roistat,
                'segment' => $segment,
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

            $tag = match ($segment) {
                'hot', 'offer', 'idea' => 'ГорячийКвиз',
                'academy', 'call' => 'ТёплыйКвиз',
                default => 'ХолодныйКвиз',
            };

            $lead->attachTag($tag);
            $lead->attachTag('квиз');
            $lead->save();

            Notes::addOne($lead, $note);

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

            $contact = $lead->contact;
            $contact->name = $request->Name;
            $contact->save();

        } else
            Log::info(__METHOD__, ['lead no found, roistat : '.$roistat]);
    }

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

    private static function getSegment($request): string
    {
        $variants = [
            'hot' => [
                'count' => 0,
                ['Очень интересен', 'В целом интересен'],
                ['В течение года', 'Через месяц'],
                ['Да, средства есть', 'Найду, если подойдут условия']//+3
            ],
            'offer' => [
                'count' => 0,
                ['Очень интересен', 'В целом интересен'],
                ['В течение года', 'Еще не определился', 'Через 3 месяца'],
                'Да, средства есть',//+3
            ],
            'idea' => [
                'count' => 0,
                ['Найду, если подойдут условия'],
                ['Рассматриваю разные бизнесы', 'Затрудняюсь ответить'],
                ['В течение года', 'Еще не определился'],
            ],
            'academy' => [
                'count' => 0,
                ['С этим есть сложности', 'Нет, таких средств точно нет'],
                ['В целом интересен', 'Очень интересен'],
                ['В ближайшее время', 'Через месяц', 'Через 3 месяца'],
            ],
            'call' => [
                'count' => 0,
                ['С этим есть сложности', 'Нет, таких средств точно нет'],
                ['В целом интересен', 'Очень интересен'],
                ['В течение года', 'Еще не определился'],
            ],
            'lazy' => [
                'count' => 0,
                ['С этим есть сложности', 'Нет, таких средств точно нет'],
                ['Рассматриваю разные бизнесы', 'Затрудняюсь ответить'],
                ['В течение года', 'Еще не определился'],
            ]
        ];

        foreach ($request as $key => $value) {

            if ($key == 'Name' || $key == 'Из_какого_вы_города') continue;

            foreach ($variants as $variant => $array) {

                foreach ($array as $item) {

                    if (is_array($item)) {

                        foreach ($item as $item1) {

                            if ($item1 == $value) {

                                if ($value == 'Да, средства есть' || $value == 'Найду, если подойдут условия') {

                                    $variants[$variant]['count'] =+ 2;
                                } else
                                    ++$variants[$variant]['count'];

                                if ($variants[$variant]['count'] == 3) {

                                    break 4;
                                }

                                break 3;
                            }
                        }

                    } elseif ($item == $value) {

                        if ($value == 'Да, средства есть' || $value == 'Найду, если подойдут условия') {

                            $variants[$variant]['count'] =+ 2;
                        } else
                            ++$variants[$variant]['count'];

                        if ($variants[$variant]['count'] == 3) {

                            break 3;
                        }

                        break 2;
                    }
                }
            }
        }

        foreach ($variants as $variant => $key) {

            if ($key['count'] == 3)

                return $variant;
        }

        return 'undefined';

        //'Хочу совмещать основную работу с дополнительным заработком',
        //'В целом интересен',
        //'Найду, если подойдут условия',
        //Рассматриваю разные бизнесы
        //Затрудняюсь ответить
        //В ближайшее время
        //Через месяц
        //Через 3 месяца
        //В течение года
        //Еще не определилися
        //Да, средства есть
        //С этим есть сложности
        //Нет, таких средств точно нет
        //Хочу начать бизнес, который не требует больших вложений денег и времени
        //Люблю кофе и хочу открыть связанный с ним бизнес
    }
}
