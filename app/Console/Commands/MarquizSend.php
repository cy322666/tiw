<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Marquiz;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Contacts;
use App\Services\amoCRM\Models\Leads;
use App\Services\amoCRM\Models\Notes;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarquizSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:marquiz-send {marquiz}';

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
        $marquiz = Marquiz::query()->find($this->argument('marquiz'));

        $amoApi = (new Client(Account::query()->first()))
            ->init()
            ->initLogs();

        $lead = $amoApi->service->ajax()->get('/api/v4/leads?order[created_at]=desc&limit=1');

        $lead = $amoApi->service->leads()->find($lead->_embedded->leads[0]->id);

        $body = json_decode($marquiz->body);

        $lead->cf('Квиз. Насколько интересно')->setValue($body->Насколько_вам_интересен_бизнес_на_кофейнях_самообслуживания);
        $lead->cf('Квиз. Когда планируете')->setValue($body->Когда_планируете_открывать_кофейню);
        $lead->cf('Квиз. Почему заинтересовала')->setValue($body->Почему_вас_заинтересовали_кофейни_самообслуживания_Какая_у_вас_цель);
        $lead->cf('Квиз. Финансовые возможности')->setValue($body->Имеете_ли_вы_финансовые_возможности_до_500_тыс__на_открытие_кофейни);

        $lead->cf('Марквиз дата заявки')
            ->setValue(
                Carbon::now()
                    ->timezone('Europe/Moscow')
                    ->format('d.m.Y')
            );

        $lead->attachTag('marquiz');
        $lead->save();

        try {
            $amoApi->service->salesbots()->start(15949, $lead->id, $entity_type = 2);

        } catch (\Throwable $e) {

            Log::info(__METHOD__, [$e->getMessage(), $e->getTraceAsString()]);
        }

        $marquiz->lead_id = $lead->id;
        $marquiz->status = 1;
        $marquiz->save();
    }
}
