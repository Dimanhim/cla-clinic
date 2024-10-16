<?php

use yii\helpers\Html;

?>

<?php foreach($tree as $item) : ?>
    <li>
        <label for="input-<?= $item->id ?>">
            <?php //$modelName = Html::getInputName($model, 'product_attribute_types[]') ?>
            <?= Html::checkbox(
                    'checkbox-type',
                    in_array($item->id, $model->product_attribute_types),
                    ['id' => 'input-'.$item->id, 'class' => 'checkbox-select-type-o', 'data-product-id' => $model->id, 'value' => $item->id]
            ) ?>
            <span class="attribute-type-list-item attribute-type-list-item-o"><?= $item->name ?></span>
            <?php
                if(in_array($item->id, $model->product_attribute_types)) {
                    echo Html::a('<span class="bi bi-arrow-right-circle"></span>', ['#'], ['title' => 'Показать атрибуты', 'class' => 'show-attributes-for-type show-attributes-for-type-o ', 'data-type-id' => $item->id, 'data-product-id' => $model->id]);
                }
            ?>
            <?php if($childs = $item->childs) : ?>
                <?= $model->getCheckboxesItems($item->childs) ?>
            <?php endif; ?>
        </label>
    </li>
<?php endforeach; ?>


