<?php

use yii\helpers\Html;

?>

<?php if($attributes) : ?>
    <?php foreach($attributes as $attribute) : ?>
        <li>
            <label for="input-<?= $attribute->id ?>">
                <?= Html::checkbox(
                    'checkbox-type',
                    in_array($attribute->id, $model->product_attributes),
                    ['id' => 'input-'.$attribute->id, 'class' => 'checkbox-select-attribute-o', 'data-attribute-type-id' => $attributeType->id, 'data-product-id' => $model->id, 'value' => $attribute->id]
                ) ?>
                <span class="attribute-type-list-item"><?= $attribute->name ?></span>
            </label>
        </li>
    <?php endforeach; ?>
<?php endif; ?>
