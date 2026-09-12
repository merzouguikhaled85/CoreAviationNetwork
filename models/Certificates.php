<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "certificates".
 *
 * @property int $certificate_id
 * @property int|null $mro_id
 * @property string|null $type
 * @property string $certificate
 * @property int|null $certificate_type_id
 */
class Certificates extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'certificates';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mro_id' , 'certificate_type_id'], 'integer'],
            [['certificate'], 'file', 'skipOnEmpty' => true, 'extensions' => 'pdf, doc, docx'],
            [['type'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'certificate_id' => 'Certificate ID',
            'mro_id' => 'MRO ID',
            'type' => 'Type',
            'certificate' => 'Certificate',
            'certificate_type_id' => 'Certificate Type ID',

        ];
    }

    public function getMro()
    {
        return $this->hasOne(MroProfile::className(), ['mro_id' => 'mro_id']);
    }

     /**
     * Gets the certificate type associated with the certificate.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCertificateType()
    {
        return $this->hasOne(CertificateTypes::className(), ['certificate_type_id' => 'certificate_type_id']);
    }
}
