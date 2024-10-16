<?php
use backend\components\Helpers;
use kartik\widgets\FileInput;

$rows = isset($rows) ? $rows : null;
?>
<div class="card card-img">
    <div class="card-header">
        Изображения
    </div>
    <div class="card-body">
        <?php if (!$model->isNewRecord && $model->gallery) echo $model->gallery->getPreviewListHTML($rows) ?>
        <?= $form->field($model, 'image_fields[]')->widget(FileInput::classname(), Helpers::getFileInputOptions()) ?>
    </div>
</div>

