<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use app\components\RequestChangeBehavior;

class Requests extends ActiveRecord
{
    const PRIORITY_AOG = 'aog';
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_ROUTINE = 'routine';

    const STATUS_CREATED = 'created';
    const STATUS_ANSWERED = 'answered';
    const STATUS_PO_LOADED = 'po_loaded';
    const STATUS_WORK_ACCEPTED = 'work_accepted';
    const STATUS_WORK_STARTED = 'work_started';
    const STATUS_REPORT_SUBMITTED = 'report_submitted';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_UPDATE_REQUEST = 'update_request'; // New status added
    const STATUS_UPDATE_REQUEST_ACCEPTED = 'update_request_accepted'; // New status added
    const STATUS_UPDATE_REQUEST_DENIED = 'update_request_denied'; // New status added


    public static function tableName()
    {
        return 'requests';
    }

    /**
     * SYNCHRONISATION AUTOMATIQUE : chaque creation, modification ou suppression
     * d'une demande publie un evenement apres la reussite d'ActiveRecord. Cette
     * observation ne modifie ni la validation, ni le statut, ni la redirection
     * choisie par le controleur metier.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => RequestChangeBehavior::class,
                'requestIdAttribute' => 'request_id',
                'eventPrefix' => 'request',
            ],
        ];
    }

    public function rules()
    {
        return [
            [['request_details', 'status','aircraft_registration', 'location', 'serial_number', 'aircraft_id', 'destination'], 'required'],
            [['ao_id', 'aircraft_id', 'destination'], 'integer'],
            [['eta', 'etd'], 'required'],
            [['request_details', 'attachment','required_certificates'], 'string'],
            [['aircraft_registration', 'location', 'serial_number'], 'string', 'max' => 255],
            /*
             * PRIORITÉ OPÉRATIONNELLE : « routine » protège les anciennes demandes.
             * Un AOG exige un délai de réponse compris entre 15 minutes et 24 heures ;
             * Urgent accepte le même délai de façon facultative, tandis que Routine
             * ne conserve volontairement aucune échéance.
             */
            ['operational_priority', 'default', 'value' => self::PRIORITY_ROUTINE],
            ['operational_priority', 'in', 'range' => array_keys(self::getOperationalPriorityOptions())],
            ['response_required_minutes', 'integer', 'min' => 15, 'max' => 1440],
            ['response_required_minutes', 'required', 'when' => static function (self $model) {
                return $model->operational_priority === self::PRIORITY_AOG;
            }, 'whenClient' => "function () { return $('[name=\"Requests[operational_priority]\"]:checked').val() === 'aog'; }"],
            /*
             * ÉCHÉANCE SERVEUR UNIQUEMENT : response_due_at_utc n'est volontairement
             * pas déclarée « safe ». Elle ne peut donc pas être injectée par POST et
             * reste exclusivement calculée dans beforeSave() à partir du délai validé.
             */
            [['status'], 'in', 'range' => [
                self::STATUS_CREATED,
                self::STATUS_ANSWERED,
                self::STATUS_PO_LOADED,
                self::STATUS_WORK_ACCEPTED,
                self::STATUS_WORK_STARTED,
                self::STATUS_REPORT_SUBMITTED,
                self::STATUS_CLOSED,
                self::STATUS_CANCELLED,
                self::STATUS_UPDATE_REQUEST, // Include the new status here
                self::STATUS_UPDATE_REQUEST_ACCEPTED, // Include the new status here
                self::STATUS_UPDATE_REQUEST_DENIED, // Include the new status here

            ]],
            ['required_certificates', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'request_id' => 'Request ID',
            'ao_id' => 'AO ID',
            'aircraft_id' => 'Aircraft Model ID',
            'request_details' => 'Request Details',
            'aircraft_registration' => 'Aircraft Registration',
            'serial_number' => 'Serial Number',
            'eta' => 'ETA',
            'etd' => 'ETD',
            'location' => 'Location',
            'destination' => 'Destination (Airport)',
            'status' => 'Status',
            'required_certificates' => 'Required Certificates',
            'attachment' => 'Attachment',
            'operational_priority' => 'Operational Priority',
            'response_required_minutes' => 'Response Required Within',
            'response_due_at_utc' => 'Response Due (UTC)',
        ];
    }

    /**
     * Retourne l'unique vocabulaire autorisé pour les formulaires, badges et filtres.
     * Les valeurs techniques restent stables même si les libellés visibles évoluent.
     */
    public static function getOperationalPriorityOptions(): array
    {
        return [
            self::PRIORITY_AOG => 'AOG',
            self::PRIORITY_URGENT => 'Urgent',
            self::PRIORITY_ROUTINE => 'Routine',
        ];
    }

    /**
     * TRI OPÉRATIONNEL PARTAGÉ : fournit aux listes AO et MRO une seule règle SQL
     * stable (AOG, Urgent, Routine). L'alias est nettoyé puis cité par Yii avant
     * insertion dans l'expression ; aucun paramètre provenant de l'URL n'est utilisé.
     *
     * Cette règle influence uniquement l'ordre d'affichage. Elle ne change ni le
     * statut, ni l'éligibilité MRO, ni les actions autorisées sur une demande.
     */
    public static function getOperationalPriorityOrderExpression(string $alias = 'requests'): Expression
    {
        $safeAlias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias) ?: 'requests';
        $priorityColumn = Yii::$app->db->quoteColumnName($safeAlias . '.operational_priority');

        return new Expression(
            "CASE COALESCE({$priorityColumn}, 'routine') " .
            "WHEN 'aog' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END"
        );
    }

    /**
     * Fournit un libellé sûr sans recopier la logique de traduction dans les vues.
     */
    public function getOperationalPriorityLabel(): string
    {
        $options = self::getOperationalPriorityOptions();
        return $options[$this->operational_priority] ?? $options[self::PRIORITY_ROUTINE];
    }

    /**
     * NORMALISATION AVANT VALIDATION : empêche une casse différente de créer une
     * nouvelle valeur et efface le délai Routine avant que les règles soient jouées.
     */
    public function beforeValidate()
    {
        $this->operational_priority = strtolower(trim((string) $this->operational_priority));
        if ($this->operational_priority === '') {
            $this->operational_priority = self::PRIORITY_ROUTINE;
        }

        if ($this->operational_priority === self::PRIORITY_ROUTINE) {
            $this->response_required_minutes = null;
            $this->response_due_at_utc = null;
        }

        return parent::beforeValidate();
    }

    /**
     * CALCUL UTC : l'échéance n'est recalculée qu'à la création ou lorsque la
     * priorité/le délai change réellement. Une simple correction de description
     * ne prolonge donc jamais silencieusement le temps accordé aux MRO.
     */
    public function beforeSave($insert)
    {
        $hasResponseWindow = in_array(
            $this->operational_priority,
            [self::PRIORITY_AOG, self::PRIORITY_URGENT],
            true
        ) && (int) $this->response_required_minutes > 0;

        $windowChanged = $insert
            || $this->isAttributeChanged('operational_priority')
            || $this->isAttributeChanged('response_required_minutes');

        if (!$hasResponseWindow) {
            $this->response_required_minutes = null;
            $this->response_due_at_utc = null;
        } elseif ($windowChanged) {
            $this->response_due_at_utc = gmdate(
                'Y-m-d H:i:s',
                time() + ((int) $this->response_required_minutes * 60)
            );
        }

        return parent::beforeSave($insert);
    }

    /**
     * TRAÇABILITÉ : enregistre l'état initial puis uniquement les changements de
     * priorité ou de délai. Une panne de l'historique est journalisée sans masquer
     * la sauvegarde métier déjà réussie ; elle pourra ensuite être surveillée.
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (
            !$insert
            && !array_key_exists('operational_priority', $changedAttributes)
            && !array_key_exists('response_required_minutes', $changedAttributes)
        ) {
            return;
        }

        try {
            $history = new RequestPriorityHistory();
            $history->request_id = (int) $this->request_id;
            $history->previous_priority = $insert ? null : ($changedAttributes['operational_priority'] ?? $this->operational_priority);
            $history->new_priority = $this->operational_priority;
            $history->previous_response_minutes = $insert ? null : ($changedAttributes['response_required_minutes'] ?? $this->response_required_minutes);
            $history->new_response_minutes = $this->response_required_minutes;
            $history->previous_due_at_utc = $insert ? null : ($changedAttributes['response_due_at_utc'] ?? null);
            $history->new_due_at_utc = $this->response_due_at_utc;

            /*
             * L'AUTEUR provient uniquement de la session serveur. Aucun identifiant
             * envoyé par le navigateur n'est accepté pour alimenter cette preuve.
             */
            if (Yii::$app instanceof \yii\web\Application) {
                $history->actor_type = strtolower((string) Yii::$app->session->get('user_type')) ?: null;
                $sessionKey = $history->actor_type === 'ao' ? 'ao_id' : ($history->actor_type === 'mro' ? 'mro_id' : 'admin_id');
                $history->actor_id = Yii::$app->session->get($sessionKey) ?: null;
            }

            if (!$history->save()) {
                Yii::error(['message' => "Échec d'historisation de la priorité.", 'errors' => $history->errors], __METHOD__);
            }
        } catch (\Throwable $exception) {
            Yii::error(['message' => "Échec d'historisation de la priorité.", 'exception' => $exception->getMessage()], __METHOD__);
        }
    }

    public function getRequiredCertificates()
    {
        return $this->hasMany(Certificates::className(), ['certificate_id' => 'certificate_id'])
            ->viaTable('request_certificates', ['request_id' => 'request_id']);
    }

    public function getDestinationAirport()
    {
        return $this->hasOne(Airports::className(), ['airport_id' => 'destination']);
    }

    /**
     * HISTORIQUE DE PRIORITÉ : relation en lecture seule ordonnée du changement
     * le plus récent au plus ancien. La relation est séparée du statut afin que
     * la traçabilité de l'urgence ne soit jamais confondue avec le workflow.
     */
    public function getPriorityHistory()
    {
        return $this->hasMany(RequestPriorityHistory::class, ['request_id' => 'request_id'])
            ->orderBy([
                'changed_at' => SORT_DESC,
                'id' => SORT_DESC,
            ]);
    }

    public function getAO()
    {
        return $this->hasOne(AoProfile::className(), ['ao_id' => 'ao_id']);
    }
    public function getAircraft()
    {
        return $this->hasOne(Aircrafts::className(), ['aircraft_id' => 'aircraft_id']);
    }
    public function getMroApplication()
{
    return $this->hasOne(MroRequestApply::className(), ['request_id' => 'request_id']);
}

}
