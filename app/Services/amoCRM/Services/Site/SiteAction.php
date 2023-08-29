<?php

namespace App\Services\amoCRM\Services\Site;

use App\Models\Site;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Contacts;
use App\Services\amoCRM\Models\Leads;
use App\Services\amoCRM\Models\Notes;
use App\Services\amoCRM\Models\Tasks;
use App\Services\Telegram;
use Exception;
use Throwable;

class SiteAction
{
    private string $taskText = 'Клиент оставил заявку на сайте';

    public function __construct(public Client $amoApi) {}

    /**
     * @throws Exception
     */
    public function send(Site $site, object $body) : bool
    {
        try {

            $contact = Contacts::search([
                'Телефоны' => $site->phone,
                'Почта'    => $site->email ?? null,
            ], $this->amoApi);

            $statusId = $site->is_test ? 53757562 : 33522700;
            $statusId = !empty($body->feature) && $body->feature == 'subscription-3' ? 55684270 : $statusId;

            $productType = NoteHelper::getTypeProduct($body);

            if (!$contact) {

                $contact = Contacts::create($this->amoApi, $body->firstname);
                $contact = Contacts::update($contact, [
                    'Почта'    => $site->email,
                    'Телефоны' => [$site->phone],
                ]);

                $lead = Leads::create($contact, [
                    'status_id' => $statusId
                ], $body->name);

                try {

                    $lead->cf('Название продукта')->setValue($site->name);
                } catch (Exception $e) {

                    Telegram::send('Неизвестный продукт :'.$site->name, env('TG_CHAT_DEBUG'), env('TG_TOKEN_DEBUG'), []);
                }

                if ($productType)
                    $lead->cf('Тип продукта')->setValue($productType);

                $lead->attachTag($productType);

                $lead->cf('Источник')->setValue('Основной сайт');
                $lead->cf('Способ оплаты')->setValue('Сайт');

                if ($body->communicationMethod) {

                    $lead->cf('Способ связи')->setValue(NoteHelper::switchCommunication($body->communicationMethod));
                }
                $lead->save();

                $lead = LeadHelper::setUtmsForObject($lead, $body);

            } else {

                $lead = Leads::search($contact, $this->amoApi, [
                    3342043,
                    6540894,
                ]);

                if ($lead) {

                    $lead->attachTag('В работе');
                    $lead->save();

                } else {

                    $lead = Leads::create($contact, [
                        'status_id' => $statusId
                    ], $body->name);

                    $lead->attachTag($productType ?? null);

                    try {

                        $lead->cf('Название продукта')->setValue($site->name);
                    } catch (Exception $e) {

                        Telegram::send('Неизвестный продукт :'.$site->name, env('TG_CHAT_DEBUG'), env('TG_TOKEN_DEBUG'), []);
                    }
                    if ($productType)
                        $lead->cf('Тип продукта')->setValue($productType);

                    $lead->cf('Источник')->setValue('Основной сайт');
                    $lead->cf('Способ оплаты')->setValue('Сайт');

                    if ($body->communicationMethod) {

                        $lead->cf('Способ связи')->setValue(NoteHelper::switchCommunication($body->communicationMethod));
                    }
                    $lead->save();

                    $lead = LeadHelper::setUtmsForObject($lead, $body);
                }
            }

            $lead->attachTag('Основной');
            $lead->save();

            $site->lead_id = $lead->id;
            $site->contact_id = $contact->id;
            $site->save();

            Notes::addOne($lead, NoteHelper::createNoteConsultation($body, $site));

        } catch (Throwable $e) {

            $site->error = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
            $site->save();

            throw new Exception($e->getMessage().' '.$e->getFile().' '.$e->getLine());
        }

        return 1;
    }
}
