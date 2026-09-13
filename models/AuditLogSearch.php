<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Recherche serveur pour un volume important de traces d'audit.
 */
class AuditLogSearch extends Model
{
    public $date_from;
    public $date_to;
    public $user_id;
    public $username;
    public $company;
    public $role;
    public $action;
    public $model;
    public $record_id;
    public $ip_address;
    public $country;
    public $request_id;
    public $page_size = 50;

    public function rules()
    {
        return [
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
            [['user_id'], 'integer'],
            [['username', 'company', 'role', 'action', 'model', 'record_id', 'ip_address', 'country', 'request_id'], 'string'],
            [['page_size'], 'in', 'range' => [25, 50, 100]],
        ];
    }

    public function formName()
    {
        return '';
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = AuditLog::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC, 'id' => SORT_DESC],
                /*
                 * id doit rester autorise, car il stabilise l'ordre lorsque
                 * plusieurs evenements possedent exactement le meme horodatage.
                 */
                'attributes' => ['id', 'created_at', 'action', 'username', 'company_name', 'model', 'ip_address'],
            ],
            'pagination' => [
                'pageSize' => 50,
                'pageSizeLimit' => [25, 100],
            ],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            $query->where('0=1');
            return $provider;
        }

        $provider->pagination->pageSize = (int) $this->page_size;
        $query->andFilterWhere(['user_id' => $this->user_id]);
        $query->andFilterWhere(['user_role' => $this->role]);
        $query->andFilterWhere(['action' => $this->action]);
        $query->andFilterWhere(['record_id' => $this->record_id]);
        $query->andFilterWhere(['ip_address' => $this->ip_address]);
        $query->andFilterWhere(['request_id' => $this->request_id]);
        $query->andFilterWhere(['like', 'username', $this->username]);
        $query->andFilterWhere(['like', 'company_name', $this->company]);
        $query->andFilterWhere(['like', 'model', $this->model]);
        $query->andFilterWhere(['or', ['like', 'country_code', $this->country], ['like', 'country_name', $this->country]]);

        if ($this->date_from) {
            $query->andWhere(['>=', 'created_at', $this->date_from . ' 00:00:00.000000']);
        }
        if ($this->date_to) {
            $query->andWhere(['<=', 'created_at', $this->date_to . ' 23:59:59.999999']);
        }

        return $provider;
    }
}
