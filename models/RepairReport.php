<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "repair_report".
 *
 * @property int $repair_report_id
 * @property int $mro_request_apply_id
 * @property string $report
 * @property bool $CRSed
 * @property string|null $CRS_attachment
 * @property string|null $AO_description
 * @property string|null $mro_quote
 * @property bool|null $quote_approved
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $mro_attachment
 *
 * @property MroRequestApply $mroRequestApply
 */
class RepairReport extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'repair_report';
    }

    /**
     * SYNCHRONISATION DES RAPPORTS/CRS : RepairReport ne contient pas directement
     * request_id. Le resolver lit uniquement cette cle dans la candidature MRO
     * associee afin de publier l'evenement pour la bonne demande, sans charger
     * les autres relations ni modifier leur logique actuelle.
     */
    public function behaviors()
    {
        return [
            'requestChange' => [
                'class' => \app\components\RequestChangeBehavior::class,
                'eventPrefix' => 'report',
                'requestIdResolver' => static function (self $report) {
                    return MroRequestApply::find()
                        ->select('request_id')
                        ->where(['id' => $report->mro_request_apply_id])
                        ->scalar();
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mro_request_apply_id',  'CRSed'], 'required'],
            [['mro_request_apply_id'], 'integer'],
            [['CRSed', 'quote_approved'], 'boolean'],
            [['report','created_at', 'updated_at'], 'safe'],
            [['report', 'AO_description', 'mro_quote'], 'string', 'max' => 255],
            [['CRS_attachment'], 'string'], // Updated rule for CRS_attachment
            [['mro_request_apply_id'], 'exist', 'skipOnError' => true, 'targetClass' => MroRequestApply::className(), 'targetAttribute' => ['mro_request_apply_id' => 'id']],
            [['mro_attachment'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, pdf', 'maxSize' => 1024 * 1024 * 10], // Example validation for file uploads

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'repair_report_id' => 'Repair Report ID',
            'mro_request_apply_id' => 'MRO Request Apply ID',
            'report' => 'Report',
            'CRSed' => 'CRSed',
            'CRS_attachment' => 'CRS Attachment',
            'AO_description' => 'AO Description',
            'mro_quote' => 'MRO Quote',
            'mro_attachment' => 'MRO Attachment', // Added label for mro_attachment
            'quote_approved' => 'Quote Approved',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[MroRequestApply]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMroRequestApply()
    {
        return $this->hasOne(MroRequestApply::className(), ['id' => 'mro_request_apply_id']);
    }

    /**
     * Gets the MRO related to this report.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMro()
    {
        return $this->getMroRequestApply()->one()->getMro();
    }

    /**
     * Gets the AO related to this report.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAo()
    {
        return $this->getRequest()->one()->getAO();
    }

    /**
     * Gets the request related to this report.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequest()
    {
        return Requests::findOne($this->getMroRequestApply()->one()->request_id) ;
    }
}
