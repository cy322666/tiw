<?php

namespace App\Services\amoCRM\Services\Site;

abstract class NoteHelper
{
    public static function switchNoteType(string $action) : string
    {
//        return match ($action)
    }

    public static function createNoteDefault($data, $site): string
    {
        $text = [
            'Новая заявка на сайте!',
            '-----------------------------',
            ' - Имя : '. $data->firstname ?? '-',
            ' - Почта : '. $data->email ?? '-',
            ' - Телефон : '. $data->phone ?? '-',
            '-----------------------------',
            ' - Название курса : '. $data->course_name ?? '-',
            ' - ID курса : '. $data->course_id ?? '-',
            '-----------------------------'
        ];
        return implode("\n", $text);
    }

    public static function createNoteOrder($data, $site): string
    {
        $text = [
            'Новая оплата на сайте!',
            '-----------------------------',
            ' - Имя : '. $data->firstname ?? '-',
            ' - Почта : '. $data->email ?? '-',
            ' - Телефон : '. $data->phone ?? '-',
            ' - Оплачено : '. $site->amount ?? '-',
            '-----------------------------',
            ' - Название курса : '. $site->name ?? '-',
            ' - ID курса : '. $site->course_id ?? '-',
            '-----------------------------'
        ];
        return implode("\n", $text);
    }

    public static function createNoteConsultation($data, $site): string
    {
        $text = [
            'Новая заявка на консультацию!',
            '-----------------------------',
            ' - Имя : '. $data->firstname ?? '-',
            ' - Почта : '. $data->email ?? '-',
            ' - Телефон : '. $data->phone ?? '-',
            '-----------------------------',
            ' - Название продукта : '. $site->name ?? '-',
            ' - ID курса : '. $site->course_id ?? '-',
            '-----------------------------'
        ];

        if(!empty($data->communicationMethod))
            $text = array_merge($text, [
                ' - Способ связи : '.self::switchCommunication($data->communicationMethod),
            ]);

        return implode("\n", $text);
    }

    public static function switchCommunication($method): string
    {
        return match ($method) {
            'messenger' => 'Мессенджер',
            'phone' => 'Телефон',
            default => $method,
        };
    }

    public static function getTypeProduct($body): ?string
    {
        if ((!empty($body->discriminator) && $body->discriminator == 'yearly-program') ||
            (!empty($body->coursetype) && $body->coursetype == 'yearly-program'))

            return 'Годовая программа';

        if ((!empty($body->discriminator) && $body->discriminator == 'course') ||
            (!empty($body->coursetype) && $body->coursetype == 'course'))

        if (!empty($body->product_name) && $body->product_name == 'Подписка на год')

            return 'Подписка - 12 месяцев';

        if (!empty($body->product_name) && $body->product_name == 'Подписка на месяц')

            return 'Подписка - Месяц';

        if (($body->action == 'order' && $body->discriminator == 'course') ||
            (!empty($body->type) && $body->type == 'course') ||
            (!empty($body->type) && $body->type == 'курс'))

            return 'Курс';

        return null;
    }

    public static function createNoteCredit($data, $site): string
    {
        $text = [
            'Новая рассрочка с сайта !',
            '-----------------------------',
            ' - Имя : '. $data->firstname ?? '-',
            ' - Почта : '. $site->email ?? '-',
            ' - Телефон : '. $site->phone ?? '-',
            ' - Оплачено : '. $site->amount ?? '-',
            '-----------------------------',
            ' - Название продукта : '. $site->name ?? '-',
            ' - ID курса : '. $site->course_id ?? '-',
            '-----------------------------'
        ];
        return implode("\n", $text);
    }
}
