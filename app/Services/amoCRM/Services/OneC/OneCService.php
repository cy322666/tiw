<?php

namespace App\Services\amoCRM\Services\OneC;

use App\Models\OneC\Pay;
use App\Services\amoCRM\Client;
use App\Services\amoCRM\Models\Contacts;
use Ufee\Amo\Models\Contact;

class OneCService
{
    public static function searchContact(Client $amoApi, Pay $pay): Contact|bool
    {
        if ($pay->email)
            $contact = Contacts::search(['Почта' => $pay->email], $amoApi);

        if (empty($contact) && $pay->email) {

            $leads = $amoApi->service
                ->leads()
                ->searchByCustomField($pay->email, 'Почта плательщика');

            $lead = $leads->first();

            if ($lead)
                $contact = $lead->contact;
        }

        return $contact ?? false;
    }

    public static function addNote(Client $amoApi, Pay $pay)
    {
        $note = $amoApi->service->notes()->create();
        $note->note_type = 4;
        $note->text = 'Прикреплена оплата из 1с';
        $note->element_type = $pay->lead_id ? 2 : 1;
        $note->element_id = $pay->lead_id ?? $pay->contact_id;
        $note->save();
    }
}
