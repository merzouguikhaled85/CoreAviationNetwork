<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * Server-side filtering for the private early-access administration page.
 */
class PrelaunchSubscriberSearch extends Model
{
    public $search;
    public $company_type;
    public $subscription_status;
    public $confirmation_email_status;
    public $date_from;
    public $date_to;
    public $page_size = 20;

    public function rules()
    {
        return [
            [['search'], 'string', 'max' => 190],
            [['company_type'], 'in', 'range' => array_keys(PrelaunchSubscriber::companyTypeOptions())],
            [['subscription_status'], 'in', 'range' => array_keys(PrelaunchSubscriber::subscriptionStatusOptions())],
            [['confirmation_email_status'], 'in', 'range' => array_keys(PrelaunchSubscriber::confirmationEmailStatusOptions())],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
            [['page_size'], 'in', 'range' => [10, 20, 50, 100]],
        ];
    }

    public function formName()
    {
        return '';
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = $this->buildQuery($params);

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC, 'id' => SORT_DESC],
                'attributes' => [
                    'id',
                    'created_at',
                    'first_name',
                    'last_name',
                    'company_name',
                    'business_email',
                    'company_type',
                    'subscription_status',
                    'confirmation_email_status',
                    'confirmed_at',
                ],
            ],
            'pagination' => [
                'pageSize' => (int) $this->page_size,
                'pageSizeLimit' => [10, 100],
            ],
        ]);
    }

    public function buildQuery(array $params): ActiveQuery
    {
        $query = PrelaunchSubscriber::find();
        $this->load($params);

        if (!$this->validate()) {
            return $query->where('0=1');
        }

        $this->page_size = (int) $this->page_size;
        $term = trim((string) $this->search);
        if ($term !== '') {
            $conditions = ['or',
                ['like', 'first_name', $term],
                ['like', 'last_name', $term],
                ['like', 'company_name', $term],
                ['like', 'business_email', $term],
                ['like', 'company_website', $term],
            ];
            if (ctype_digit($term)) {
                $conditions[] = ['id' => (int) $term];
            }
            $query->andWhere($conditions);
        }

        $query->andFilterWhere(['company_type' => $this->company_type]);
        $query->andFilterWhere(['subscription_status' => $this->subscription_status]);
        $query->andFilterWhere(['confirmation_email_status' => $this->confirmation_email_status]);

        if ($this->date_from) {
            $query->andWhere(['>=', 'created_at', $this->date_from . ' 00:00:00']);
        }
        if ($this->date_to) {
            $query->andWhere(['<=', 'created_at', $this->date_to . ' 23:59:59']);
        }

        return $query;
    }
}
