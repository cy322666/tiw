<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Notes;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-leads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {
        $amoApi = (new Client(Account::first()))->init();

        $arrays = explode("\n", file_get_contents('https://hub.blackclever.ru/tiw/storage/logs/log-26.log'));

        foreach ($arrays as $array) {

            $data = json_decode(trim($array, ','), true);

            if ($data == null) continue;

            $segment = static::getSegment($data);

            $note = match ($segment) {
                'hot'   => 'Деньги есть / Желание есть / Скоро хочет открыть - Закрыть здесь и сейчас',
                'offer' => 'Деньги есть / Желание есть / Не знает, когда откроет - Ускорить офером',
                'idea'  => 'Деньги есть / Желания нет или не выявлено / Не знает, когда откроет - Соблазнить идеей и офером',
                'call'  => 'Денег нет / Желание есть / Скоро хочет открыть - Аккуратный прозвон, но больше догрев',
                'lazy'  => 'Деньги есть / Желания нет или не выявлено / Не знает, когда откроет',
                'academy' => 'Денег нет / Желание есть / Скоро хочет открыть',
                default => 'Неопределенный - Аккуратный прозвон, но больше догрев'
            };

            if (empty($data['COOKIES'])) continue;

            $arrRoistat = explode(';', $data['COOKIES']);

            foreach ($arrRoistat as $value) {

                if(strripos($value, 'roistat_visit=') !== false) {

                    $roistat = (int)trim(explode('=', $value)[1]);
                }
            }

            if (empty($roistat)) {

                Log::error(__METHOD__, ['empty roistat']);

                continue;
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

                if (is_array($lead) == true) {

                    $lead = $amoApi->service->leads()->find($lead['id']);
                }

                dump([
                    'lead_id' => $lead->id,
                    'roistat' => $roistat,
                    'segment' => $segment,
                ]);

                $a = $data['Рассматривали_ли_уже_потенциальные_места_для_установки_своей_кофейни_самообслуживания'];
                $b = $data['Почему_вас_заинтересовали_кофейни_самообслуживания_Какая_у_вас_цель'];
                $c = $data['Имеете_ли_финансовые_возможности_до_500К_на_открытие_кофейни'];
                $d = $data['Когда_планируете_открывать_кофейню'];
                $e = $data['Насколько_вам_интересен_бизнес_на_кофейнях_самообслуживания'];
                $f = $data['Из_какого_вы_города'];

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
                $contact->name = $data['Name'];
                $contact->save();

            } else
                Log::info(__METHOD__, ['lead no found, roistat : '.$roistat]);
        }
    }

    private static function getSegment($request): string
    {
        $variants = [
            'hot' => [
                'count' => 0,
                ['Очень интересен', 'В целом интересен'],
                ['В течение года', 'Через месяц'],
                ['Да, средства есть', 'Найду, если подойдут условия']
            ],
            'offer' => [
                'count' => 0,
                ['Очень интересен', 'В целом интересен'],
                ['В течение года', 'Еще не определился', 'Через 3 месяца'],
                'Да, средства есть',
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

                                ++$variants[$variant]['count'];

                                if ($variants[$variant]['count'] == 3) {

                                    break 4;
                                }

                                break 3;
                            }
                        }

                    } elseif ($item == $value) {

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
