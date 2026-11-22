<?php

namespace app\components;

use app\models\Currency;
use app\models\Requests;
use Yii;

/**
 * Outils partagés par les e-mails liés à une demande (mail/partials/*).
 *
 * - sujet explicite : « [AOG] New quote received · Request #235 · Dassault Falcon 7X G-EGVO »
 *   pour que le destinataire comprenne le contexte avant d'ouvrir le mail ;
 * - lien absolu et signé vers la page de la demande ;
 * - nom affichable d'un profil AO / MRO et montant avec devise.
 *
 * Aucune logique métier : uniquement de la présentation.
 */
class RequestMail
{
    /**
     * Sujet d'e-mail contextualisé par la demande.
     */
    public static function subject(string $action, ?Requests $request): string
    {
        if ($request === null) {
            return $action;
        }

        $parts = [$action, 'Request #' . $request->request_id];

        $aircraft = $request->getAircraft()->one();
        $aircraftText = trim(implode(' ', array_filter([
            $aircraft ? trim((string) $aircraft->manufacturer . ' ' . (string) $aircraft->model) : '',
            trim((string) $request->aircraft_registration),
        ])));
        if ($aircraftText !== '') {
            $parts[] = $aircraftText;
        }

        $prefix = strtolower((string) $request->operational_priority) === Requests::PRIORITY_AOG
            ? '[AOG] '
            : (strtolower((string) $request->operational_priority) === Requests::PRIORITY_URGENT ? '[Urgent] ' : '');

        // Les sujets ne doivent contenir ni retour à la ligne ni texte démesuré.
        $subject = preg_replace('/\s+/', ' ', $prefix . implode(' · ', $parts));
        return mb_strlen($subject) > 150 ? rtrim(mb_substr($subject, 0, 149)) . '…' : $subject;
    }

    /**
     * Lien absolu vers la page de la demande (identifiant signé, comme dans l'application).
     */
    public static function requestUrl(Requests $request, string $fragment = ''): string
    {
        $url = Yii::$app->urlManager->createAbsoluteUrl([
            '/requests/view',
            'id' => UrlIdHelper::encode($request->request_id),
        ]);

        return $fragment !== '' ? $url . '#' . $fragment : $url;
    }

    /**
     * Nom affiché d'un profil AO ou MRO : raison sociale, sinon identifiant.
     *
     * @param object|null $profile
     */
    public static function displayName($profile, string $fallback = ''): string
    {
        if (!$profile) {
            return $fallback;
        }

        $company = trim((string) ($profile->company_name ?? ''));
        return $company !== '' ? $company : (string) ($profile->username ?? $fallback);
    }

    /**
     * Montant lisible avec sa devise : « 5,555.00 USD (US Dollar) ».
     *
     * @param mixed $amount
     */
    public static function money($amount, ?string $currencyCode): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $code = trim((string) $currencyCode);
        $text = is_numeric($amount) ? number_format((float) $amount, 2, '.', ',') : (string) $amount;

        if ($code === '') {
            return $text;
        }

        $currency = Currency::find()->where(['code' => $code])->one();
        $name = $currency ? trim((string) $currency->name) : '';

        return $text . ' ' . $code . ($name !== '' && strcasecmp($name, $code) !== 0 ? ' (' . $name . ')' : '');
    }

    /**
     * Texte tronqué proprement pour un e-mail.
     */
    public static function excerpt(?string $text, int $max = 1200): string
    {
        $text = trim((string) $text);
        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
    }
}
