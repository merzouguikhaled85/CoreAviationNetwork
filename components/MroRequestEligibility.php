<?php

namespace app\components;

use app\models\Aircrafts;
use app\models\AoRequestsApplications;
use app\models\Certificates;
use app\models\MroAircraftCertificate;
use app\models\MroprofileAirport;
use app\models\MroRequestApply;
use app\models\Requests;

/**
 * RÈGLE D'ÉLIGIBILITÉ MRO (correctif sécurité IDOR, 2026-11)
 *
 * Point unique qui répond à la question : « cette demande est-elle proposée
 * à ce MRO ? ». Elle reprend, pour UNE demande, exactement les critères de la
 * liste MRO « New requests » (MroRequestsController::actionIndex) :
 *  1. statut created ou answered ;
 *  2. le MRO possède le type de certificat requis (requests.required_certificates) ;
 *  3. le MRO dessert l'aéroport de destination (mroprofile_airport) ;
 *  4. le MRO est approuvé pour le modèle de l'avion (mro_aircraft_certificate) ;
 *  5. aucun PO n'a été émis, ou un PO a été émis vers CE MRO.
 *
 * Utilisée par :
 *  - MroRequestsController : apply, contact, recommend-mro ;
 *  - RequestsController : lecture de requests/view par un MRO.
 *
 * ATTENTION : la requête SQL de la liste MroRequestsController::actionIndex
 * applique les mêmes critères en masse. Toute évolution de la règle métier doit
 * être faite aux deux endroits (dette technique TD-5).
 */
final class MroRequestEligibility
{
    /** Statuts dans lesquels une demande peut encore recevoir des réponses MRO. */
    public const OPEN_STATUSES = ['created', 'answered'];

    /**
     * Indique si la demande est proposée au MRO (critères 1 à 5 ci-dessus).
     */
    public static function isOfferedTo(Requests $request, int $mroId): bool
    {
        if ($mroId <= 0 || !in_array($request->status, self::OPEN_STATUSES, true)) {
            return false;
        }

        $hasCertificate = Certificates::find()
            ->where(['mro_id' => $mroId, 'type' => $request->required_certificates])
            ->exists();

        $servesAirport = MroprofileAirport::find()
            ->where(['mro_id' => $mroId, 'airport_id' => $request->destination])
            ->exists();

        $aircraft = Aircrafts::findOne($request->aircraft_id);
        $approvedForModel = $aircraft !== null && MroAircraftCertificate::find()
            ->where(['mro_id' => $mroId, 'aircraft_model_id' => $aircraft->aircraft_model_id])
            ->exists();

        if (!$hasCertificate || !$servesAirport || !$approvedForModel) {
            return false;
        }

        // Critère 5 : visible s'il n'existe aucun PO, ou si un PO a été émis vers ce MRO.
        $poQuery = AoRequestsApplications::find()
            ->alias('ara')
            ->where(['ara.request_id' => $request->request_id])
            ->andWhere(['IS NOT', 'ara.po', null])
            ->andWhere(['<>', 'ara.po', '']);

        if (!(clone $poQuery)->exists()) {
            return true;
        }

        return $poQuery
            ->innerJoin(['mra' => MroRequestApply::tableName()], 'mra.id = ara.application_id')
            ->andWhere(['mra.mro_id' => $mroId])
            ->exists();
    }

    /**
     * Indique si le MRO a déjà déposé au moins une réponse (devis) sur la demande.
     */
    public static function hasApplied(Requests $request, int $mroId): bool
    {
        return $mroId > 0 && MroRequestApply::find()
            ->where(['request_id' => $request->request_id, 'mro_id' => $mroId])
            ->exists();
    }

    /**
     * Le MRO est « concerné » par la demande : elle lui est proposée,
     * ou il y a déjà répondu (mission en cours ou passée).
     */
    public static function isConcerned(Requests $request, int $mroId): bool
    {
        return self::hasApplied($request, $mroId) || self::isOfferedTo($request, $mroId);
    }
}
