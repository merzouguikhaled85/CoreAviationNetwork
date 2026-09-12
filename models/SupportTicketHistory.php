<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Evenement horodate de la gestion administrative d'un ticket Support.
 */
class SupportTicketHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%support_ticket_history}}';
    }

    /**
     * Les valeurs sont ecrites exclusivement par le controleur admin, mais ces
     * regles protegent aussi les futurs usages console ou API du modele.
     */
    public function rules()
    {
        return [
            [['support_ticket_id', 'action'], 'required'],
            [['support_ticket_id', 'admin_id', 'old_assigned_admin_id', 'new_assigned_admin_id'], 'integer'],
            [['action'], 'string', 'max' => 40],
            [['old_status', 'new_status'], 'string', 'max' => 24],
            [['comment'], 'string', 'max' => 500],
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
