<?php

use app\models\Step;
use app\models\Pipeline;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use himiklab\sortablegrid\SortableGridView;

/** @var yii\web\View $this */
/** @var app\models\PipelineSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="step-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= SortableGridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'name',
            [
                'attribute' => 'pipeline_id',
                'format' => 'raw',
                'value' => function($data) {
                    if($data->pipeline) {
                        return Html::a($data->pipeline->name, ['pipeline/view', 'id' => $data->pipeline->id]);
                    }
                },
                'filter' => Pipeline::getList(),
            ],
            'amo_id',
            [
                'attribute' => 'is_active',
                'format' => 'boolean',
            ],
            [
                'class' => ActionColumn::className(),
                'urlCreator' => function ($action, Step $model, $key, $index, $column) {
                    return Url::toRoute([$action, 'id' => $model->id]);
                 }
            ],
        ],
    ]); ?>


</div>
