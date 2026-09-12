<?php
    namespace app\models;
    use yii\db\ActiveRecord;

    class Cities extends ActiveRecord
    {
        private $city_name;
        private $country_id;

        public $id; // Add this property


        public function rules(){
            return [
                [['city_name','country_id'],'required']
            ];
        }

        public function getCountry()
        {
            return $this->hasOne(Countries::class, ['country_id' => 'country_id']);
        }
    }

?>