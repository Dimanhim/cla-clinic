<?php if($models) : ?>
    <ul>
        <?php foreach($models as $model) : ?>
            <li><?= $model->name ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

