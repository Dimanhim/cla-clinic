<?php

use common\models\AttributeType;
use yii\helpers\Html;

?>
    <?php if($tree = AttributeType::buildTree()) : ?>
        <ul class="attribute-type-list list-block">
            <?php foreach($tree as $item) : ?>
                <li>
                    <label for="input-<?= $item->id ?>">
                        <?php $modelName = Html::getInputName($model, 'product_attribute_types[]') ?>
                        <?= Html::checkbox($modelName, in_array($item->id, $model->product_attribute_types), ['id' => 'input-'.$item->id, 'value' => $item->id]) ?>
                        <span class="attribute-type-list-item attribute-type-list-item-o"><?= $item->name ?></span>
                        <?= Html::a('<span class="bi bi-arrow-right-circle"></span>', ['#'], ['title' => 'Показать атрибуты', 'class' => 'show-attributes-for-type show-attributes-for-type-o ', 'data-type-id' => $item->id]) ?>
                        <?php if($childs = $item->childs) : ?>
                            <ul class="attribute-type-sublist">
                                <?php foreach($childs as $subItem) : ?>
                                    <li>
                                        <label for="input-<?= $subItem->id ?>">
                                            <?= Html::checkbox($modelName, in_array($subItem->id, $model->product_attribute_types), ['id' => 'input-'.$subItem->id, 'value' => $subItem->id]) ?>
                                            <span class="attribute-type-list-item attribute-type-list-item-o"><?= $subItem->name ?></span>
                                            <?= Html::a('<span class="bi bi-arrow-right-circle"></span>', ['#'], ['title' => 'Показать атрибуты', 'class' => 'show-attributes-for-type show-attributes-for-type-o ', 'data-type-id' => $subItem->id]) ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

