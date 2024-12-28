<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Pipeline;
use kartik\select2\Select2;
use app\models\Clinic;

/** @var yii\web\View $this */
/** @var app\models\Pipeline $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="pipeline-form">


    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'clinic_id')->dropDownList(Clinic::getList(), ['prompt' => '[Не выбрана]']) ?>
                    <?= $form->field($model, 'amo_id')->textInput() ?>
                    <?= $form->field($model, 'mis_id')->textInput() ?>

                    <?= $form->field($model, 'is_active')->checkbox() ?>

                    <div class="form-group">
                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>





    <?php ActiveForm::end(); ?>

</div>
