<?php
use backend\components\Helpers;
use kartik\widgets\FileInput;
?>
<div class="card card-img card-img-o">
    <div class="card-header card-header-o">
        Изображения <i class="bi bi-chevron-down"></i>
    </div>
    <div class="card-body card-body-o">
        <?php if (!$model->isNewRecord && $model->gallery) echo $model->gallery->getPreviewListHTML() ?>
        <?= $form->field($model, 'image_fields[]')->widget(FileInput::classname(), Helpers::getFileInputOptions()) ?>
    </div>
</div>
