<?php
    namespace app\models;
    use yii\db\ActiveRecord;

    class Countries extends ActiveRecord
    {
        private $country_name;
        private $country_code;

        public function rules(){
            return [
                [['country_name','country_code'],'required']
            ];
        }

    }

?>