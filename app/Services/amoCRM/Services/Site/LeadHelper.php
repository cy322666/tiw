<?php

namespace App\Services\amoCRM\Services\Site;

use Illuminate\Http\Request;
use Ufee\Amo\Models\Lead;

abstract class LeadHelper
{
    public static function setUtmsForObject(Lead $lead, object $body): Lead
    {
        return static::setUtms($lead, [
            'utm_source'   => $body->utm_source ?? null,
            'utm_campaign' => $body->utm_campaign ?? null,
            'utm_medium'   => $body->utm_medium ?? null,
            'utm_content'  => $body->utm_content ?? null,
            'utm_term' => $body->utm_term ?? null,

            'utm_source_first'   => $body->utm_source_first ?? null,
            'utm_campaign_first' => $body->utm_campaign_first ?? null,
            'utm_medium_first'   => $body->utm_medium_first ?? null,
            'utm_term_first'     => $body->utm_term_first ?? null,
            'utm_content_first'  => $body->utm_content_first ?? null,

            'referrer' => $body->referrer ?? null,
            'yclid'    => $body->yclid ?? null,
            '_YM_UID'  => $body->_YM_UID ?? null,
            '_ym_uid'  => $body->_ym_uid ?? null,
        ]);
    }

    public static function setUtms(Lead $lead, $utms): Lead
    {
        if ($lead->cf('utm_source')->getValue() == null) {

            $lead->cf('utm_source')->setValue($utms['utm_source'] ?? null);
        }
        if ($lead->cf('utm_campaign')->getValue() == null) {

            $lead->cf('utm_campaign')->setValue($utms['utm_campaign'] ?? null);
        }
        if ($lead->cf('utm_medium')->getValue() == null) {

            $lead->cf('utm_medium')->setValue($utms['utm_medium'] ?? null);
        }
        if ($lead->cf('utm_term')->getValue() == null) {

            $lead->cf('utm_term')->setValue($utms['utm_term'] ?? null);
        }
        if ($lead->cf('utm_content')->getValue() == null) {

            $lead->cf('utm_content')->setValue($utms['utm_content'] ?? null);
        }

        if ($lead->cf('utm_source_first')->getValue() == null) {

            $lead->cf('utm_source_first')->setValue($utms['utm_source_first'] ?? null);
        }
        if ($lead->cf('utm_campaign_first')->getValue() == null) {

            $lead->cf('utm_campaign_first')->setValue($utms['utm_campaign_first'] ?? null);
        }
        if ($lead->cf('utm_medium_first')->getValue() == null) {

            $lead->cf('utm_medium_first')->setValue($utms['utm_medium_first'] ?? null);
        }
        if ($lead->cf('utm_term_first')->getValue() == null) {

            $lead->cf('utm_term_first')->setValue($utms['utm_term_first'] ?? null);
        }
        if ($lead->cf('utm_content_first')->getValue() == null) {

            $lead->cf('utm_content_first')->setValue($utms['utm_content_first'] ?? null);
        }

        if ($lead->cf('referrer')->getValue() == null) {

            $lead->cf('referrer')->setValue($utms['referrer'] ?? null);
        }

        if ($lead->cf('yclid')->getValue() == null) {

            $lead->cf('yclid')->setValue($utms['yclid'] ?? null);
        }

        if ($lead->cf('_ym_uid')->getValue() == null) {

            $lead->cf('_ym_uid')->setValue($utms['_YM_UID'] ?? null);
        }

        if ($lead->cf('_ym_uid')->getValue() == null) {

            $lead->cf('_ym_uid')->setValue($utms['_ym_uid'] ?? null);
        }

        return $lead;
    }
}
