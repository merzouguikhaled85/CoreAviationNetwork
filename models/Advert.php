<?php

namespace app\models;

use app\widgets\Alert;
use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

class Advert extends ActiveRecord
{
    public $use_url;
    public $url;

    public static function tableName()
    {
        return 'adverts';
    }

    public function rules()
    {
        return [
            [['admin_id', 'advert_type'], 'required'],
            [['admin_id'], 'integer'],
            [['advert_type', 'status'], 'string'],
            [['timestamp', 'start_date', 'end_date'], 'safe'],
            [['content'], 'required', 'when' => function($model) {
                return !$model->use_url;
            }, 'whenClient' => "function (attribute, value) {
                return !$('#use-url-checkbox').is(':checked');
            }"],
            [['content'], 'file', 'extensions' => 'jpg, png, mp4, avi', 'maxSize' => 1024*1024*20, 'skipOnEmpty' => true],
            [['status'], 'in', 'range' => ['active', 'inactive']],
            [['start_date', 'end_date'], 'date', 'format' => 'yyyy-MM-dd'],
            [['use_url'], 'boolean'],
            [['url'], 'url', 'when' => function($model) {
                return $model->use_url == 1;
            }, 'whenClient' => "function (attribute, value) {
                return $('#use-url-checkbox').is(':checked');
            }"],
        ];
    }

    public function attributeLabels()
    {
        return [
            'advert_id' => 'Advert ID',
            'admin_id' => 'Admin ID',
            'advert_type' => 'Advert Type',
            'content' => 'Content',
            'timestamp' => 'Timestamp',
            'status' => 'Status',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'use_url' => 'Use URL', // Label for use_url attribute
            'url' => 'URL', // Label for url attribute
        ];
    }

    public function toggleStatus()
    {
        if ($this->status == 'active') {
            $this->status = 'inactive';
        } else {
            $this->status = 'active';
        }
        return $this->save(false);
    }

    public static function findRandomActiveAdvert()
    {
        return self::find()
            ->where(['status' => 'active'])
            ->orderBy('RAND()')
            ->one();
    }

    public function uploadContent()
    {
        if ($this->validate()) {
            $this->content->saveAs('uploads/' . $this->content->baseName . '.' . $this->content->extension);
            return true;
        } else {
            return false;
        }
    }

    public function getAdmin()
    {
        return $this->hasOne(AdminProfile::className(), ['admin_id' => 'admin_id']);
    }
}
