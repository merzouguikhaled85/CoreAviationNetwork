<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modele du ticket cree par la bulle Support globale.
 *
 * Les champs techniques d'envoi restent separes du statut fonctionnel du ticket :
 * un echec SMTP ne transforme donc jamais une demande valide en donnee perdue.
 */
class SupportTicket extends ActiveRecord
{
    /** @var \yii\web\UploadedFile|null Fichier temporaire recu du formulaire. */
    public $attachment;

    /** @var string Champ invisible utilise comme piege anti-robot. */
    public $website;

    /**
     * Retourne le nom exact de la table creee par la migration associee.
     */
    public static function tableName()
    {
        return '{{%support_ticket}}';
    }

    /**
     * Valide strictement les donnees visibles et limite la piece jointe aux
     * formats consultables sans execution de code.
     */
    public function rules()
    {
        return [
            [['name', 'email', 'category', 'priority', 'subject', 'message'], 'required'],
            [['name'], 'string', 'min' => 2, 'max' => 120],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 190],
            [['category'], 'in', 'range' => array_keys(self::categoryOptions())],
            [['priority'], 'in', 'range' => array_keys(self::priorityOptions())],
            [['subject'], 'string', 'min' => 4, 'max' => 190],
            [['message'], 'string', 'min' => 20, 'max' => 5000],
            [['request_reference'], 'string', 'max' => 100],
            [['website'], 'string', 'max' => 255],
            [['website'], 'validateHoneypot'],
            [
                ['attachment'],
                'file',
                'skipOnEmpty' => true,
                'extensions' => ['pdf', 'png', 'jpg', 'jpeg'],
                'mimeTypes' => ['application/pdf', 'image/png', 'image/jpeg'],
                'maxSize' => 5 * 1024 * 1024,
                'checkExtensionByMimeType' => true,
            ],
        ];
    }

    /**
     * Refuse silencieusement le champ que seuls les robots devraient remplir.
     */
    public function validateHoneypot($attribute)
    {
        if (trim((string) $this->$attribute) !== '') {
            $this->addError($attribute, 'The support request could not be submitted.');
        }
    }

    /**
     * Libelles controles utilises a la fois par la validation et par la vue.
     */
    public static function categoryOptions()
    {
        return [
            'technical' => 'Technical issue',
            'account' => 'Account & access',
            'request' => 'Maintenance request',
            'billing' => 'Billing & purchase order',
            'other' => 'Other',
        ];
    }

    /**
     * Le support distingue seulement normal et urgent. AOG reste une priorite
     * operationnelle des demandes de maintenance, pas un niveau de ticket IT.
     */
    public static function priorityOptions()
    {
        return [
            'normal' => 'Normal',
            'urgent' => 'Urgent',
        ];
    }

    /**
     * Cycle de traitement volontairement separe des statuts aeronautiques.
     */
    public static function statusOptions()
    {
        return [
            'new' => 'New',
            'in_progress' => 'In progress',
            'waiting_user' => 'Waiting for user',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }

    /**
     * Administrateur actuellement responsable du suivi du ticket.
     */
    public function getAssignedAdmin()
    {
        return $this->hasOne(AdminProfile::class, ['admin_id' => 'assigned_admin_id']);
    }

    /**
     * Piste d'audit ordonnee chronologiquement pour la fiche administrative.
     */
    public function getHistory()
    {
        return $this->hasMany(SupportTicketHistory::class, ['support_ticket_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC]);
    }

    /**
     * Reponses administratives classees dans l'ordre de conversation. Les tentatives
     * echouees restent visibles afin que l'equipe puisse les relancer sans perdre le texte.
     */
    public function getReplies()
    {
        return $this->hasMany(SupportTicketReply::class, ['support_ticket_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC]);
    }

    /**
     * Produit une reference lisible qui ne remplace pas la cle primaire interne.
     */
    public function getPublicReference()
    {
        $date = $this->created_at ? strtotime($this->created_at) : time();

        return sprintf('SUP-%s-%06d', date('Ymd', $date), (int) $this->id);
    }
}
