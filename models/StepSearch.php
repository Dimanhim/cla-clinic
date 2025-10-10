<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Step;

/**
 * PipelineSearch represents the model behind the search form of `app\models\Pipeline`.
 */
class StepSearch extends Step
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','pipeline_id', 'amo_id', 'is_active', 'deleted', 'position', 'created_at', 'updated_at', 'name', 'amo_status', 'mis_status', 'is_for_cancel'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
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
        $query = Step::findSearch();

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
            'pipeline_id' => $this->pipeline_id,
            'amo_id' => $this->amo_id,
            'amo_status' => $this->amo_status,
            'mis_status' => $this->mis_status,
            'is_for_cancel' => $this->is_for_cancel,
            'is_active' => $this->is_active,
            'deleted' => $this->deleted,
            'position' => $this->position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }
}
