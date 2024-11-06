<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use app\models\Step;

/** @var yii\web\View $this */
/** @var app\models\Pipeline $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="pipeline-form">


    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Основная информация
                </div>
                <div class="card-body">

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'amo_id')->textInput() ?>
                    <?= $form->field($model, 'mis_id')->textInput() ?>
                    <?= $form->field($model, 'api_key')->textInput() ?>
                    <?= $form->field($model, 'reception_time')->textInput() ?>

                    <?= $form->field($model, 'is_active')->checkbox() ?>

                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Этапы воронок
                </div>
                <div class="card-body">

                    <?= $form->field($model, '_steps_create')->widget(Select2::className(), [
                        'data' => Step::getListWithPipeline(),
                        'options' => [
                            'prompt' => '[Не выбраны]',
                            'multiple' => true,
                        ],
                    ]) ?>

                    <?= $form->field($model, '_steps_update')->widget(Select2::className(), [
                        'data' => Step::getListWithPipeline(),
                        'options' => [
                            'prompt' => '[Не выбраны]',
                            'multiple' => true,
                        ],
                    ]) ?>


                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>


    <?php ActiveForm::end(); ?>

</div>
