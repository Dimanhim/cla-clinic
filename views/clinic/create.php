<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Pipeline $model */

$this->title = $model->modelName . ' - добавление';
$this->params['breadcrumbs'][] = ['label' => $model->modelName, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pipeline-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
