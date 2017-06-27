<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\DrugCode;

/**
 * DrugCodeSearch represents the model behind the search form about `common\models\DrugCode`.
 */
class DrugCodeSearch extends DrugCode
{
    public function attributes()
    {
        return array_merge(parent::attributes(), ['clinicName']);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'create_at', 'submit_at'], 'integer'],
            [['code', 'info', 'clinic_uuid', 'clinicName'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = DrugCode::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'create_at' => $this->create_at,
            'submit_at' => $this->submit_at,
        ]);

        $query->andFilterWhere(['=', 'code', $this->code])
            ->andFilterWhere(['like', 'info', $this->info])
            ->andFilterWhere(['like', 'clinic_uuid', $this->clinic_uuid]);

        $query->join('LEFT JOIN','clinic','drug_code.clinic_uuid = clinic.user_uuid');
        $query->andFilterWhere(['like','clinic.name',$this->clinicName]);

        $dataProvider->sort->attributes['clinicName'] =
            [
                'asc'=>['clinic.name'=>SORT_ASC],
                'desc'=>['clinic.name'=>SORT_DESC],
            ];

        return $dataProvider;
    }
}
