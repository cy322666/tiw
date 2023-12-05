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
        $amoApi = (new Client(Account::query()->first()))
            ->init()
            ->initLogs();

        $marquiz = Marquiz::query()->find($this->argument('marquiz'));

        $body = json_decode($marquiz->body, true);

        $contact = Contacts::search([
            'Телефон' => $marquiz->phone,
            'Почта' => $marquiz->email,
        ], $amoApi);

        if (!$contact)
            $contact = Contacts::create($amoApi, $name ?? 'Неизвестно');

        $contact = $amoApi
            ->service
            ->contacts()
            ->find($contact->id);

        $contact = Contacts::update($contact, [
            'Телефоны' => [$marquiz->phone],
            'Почта' => $marquiz->email,
        ]);

        $lead = Leads::search($contact, $amoApi, 6770222);

        if (!$lead) {

            $lead = Leads::create(
                $contact, [
                'responsible_user_id' => $contact->responsible_user_id,
            ], 'Новый лид из Marquiz');
        } else {
            try {
                $amoApi->service->salesbots()->start(15949, $lead->id, $entity_type = 2);

            } catch (\Throwable $e) {

                Log::info(__METHOD__, [$e->getMessage(), $e->getTraceAsString()]);
            }
        }

        $lead->cf('Город')->setValue($marquiz->city);
        $lead->cf('Марквиз дата заявки')->setValue(Carbon::now()->timezone('Europe/Moscow')->format('d.m.Y'));
//
        if(!empty($body['extra']['utm']['term']))
            $lead->cf('utm_term')->setValue($body['extra']['utm']['term']);

        if(!empty($body['extra']['utm']['source']))
            $lead->cf('utm_source')->setValue($body['extra']['utm']['source']);

        if(!empty($body['extra']['utm']['medium']))
            $lead->cf('utm_medium')->setValue($body['extra']['utm']['medium']);

        if(!empty($body['extra']['utm']['content']))
            $lead->cf('utm_content')->setValue($body['extra']['utm']['content']);

        if(!empty($body['extra']['utm']['campaign']))
            $lead->cf('utm_campaign')->setValue($body['extra']['utm']['campaign']);

        $lead->cf('Квиз. Насколько интересно')->setValue($body[2]['a']);
        $lead->cf('Квиз. Когда планируете')->setValue($body[3]['a']);
        $lead->cf('Квиз. Почему заинтересовала')->setValue($body[5]['a']);
        $lead->cf('Квиз. Рассматривали ли уже')->setValue($body[6]['a']);
        $lead->cf('Квиз. Финансовые возможности')->setValue($body[4]['a']);
        $lead->cf('roistat')->setValue($marquiz->roistat);

        $lead->attachTag('marquiz');
        $lead->save();

        Notes::addOne($lead, implode("\n", [
            $lead->cf('Квиз. Насколько интересно')->getValue(),
            $lead->cf('Квиз. Когда планируете')->getValue(),
            $lead->cf('Квиз. Почему заинтересовала')->getValue(),
            $lead->cf('Квиз. Рассматривали ли уже')->getValue(),
            $lead->cf('Квиз. Финансовые возможности')->getValue(),
        ]));

        $marquiz->lead_id = $lead->id;
        $marquiz->contact_id = $contact->id;
        $marquiz->status = 1;
        $marquiz->save();
    }
}
