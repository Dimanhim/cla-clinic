<?php

use yii\helpers\Html;

?>
<?php if($models) : ?>
    <ul>
        <?php foreach($models as $model) : ?>
            <li><?= Html::a($model->name, [$link.'/view', 'id' => $model->id]) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>


