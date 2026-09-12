<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Reponse redigee par un administrateur et adressee au demandeur du ticket.
 */
class SupportTicketReply extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%support_ticket_reply}}';
    }

    /**
     * Les limites correspondent au formulaire admin. Le statut cible reutilise le
     * cycle Support et ne peut donc pas introduire un statut metier aeronautique.
     */
    public function rules()
    {
        return [
            [['support_ticket_id', 'message', 'status_after_send', 'dispatch_token_hash'], 'required'],
            [['support_ticket_id', 'admin_id'], 'integer'],
            [['message', 'email_error'], 'string'],
            [['message'], 'string', 'min' => 2, 'max' => 5000],
            [['status_after_send'], 'in', 'range' => array_keys(SupportTicket::statusOptions())],
            [['email_status'], 'in', 'range' => ['pending', 'processing', 'sent', 'failed']],
            [['dispatch_token_hash'], 'string', 'length' => 64],
        ];
    }

    public function getTicket()
    {
        return $this->hasOne(SupportTicket::class, ['id' => 'support_ticket_id']);
    }

    public function getAdmin()
    {
        return $this->hasOne(AdminProfile::class, ['admin_id' => 'admin_id']);
    }
}
