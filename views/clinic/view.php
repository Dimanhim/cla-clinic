<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Pipeline $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => $model->modelName, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="pipeline-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы уверены, что хотите удалить воронку?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'amo_name',
            'amo_id',
            'mis_id',
            'reception_time',
            /*[
                'attribute' => '_steps_create',
                'format' => 'raw',
                'value' => function($data) {
                    if($models = $data->createSteps) {
                        $str = '<ul>';
                        foreach($models as $model) {
                            $listName = '';
                            $pipelineName = $model->pipeline ? $model->pipeline->name : '';
                            $listName .= $pipelineName . ' / ';
                            $listName .= $model->name;
                            $str .= "<li>{$listName}</li>";
                        }
                        $str .= '</ul>';
                        return $str;
                    }
                }
            ],*/
            [
                'attribute' => '_steps_update',
                'format' => 'raw',
                'value' => function($data) {
                    if($models = $data->updateSteps) {
                        $str = '<ul>';
                        foreach($models as $model) {
                            $listName = '';
                            $pipelineName = $model->pipeline ? $model->pipeline->name : '';
                            $listName .= $pipelineName . ' / ';
                            $listName .= $model->name;
                            $str .= "<li>{$listName}</li>";
                        }
                        $str .= '</ul>';
                        return $str;
                        return $data->getListChunk($models);
                    }
                }
            ],
            'is_active:boolean',
            //'created_at:datetime',
        ],
    ]) ?>

</div>
