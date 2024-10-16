<div class="card<?= $hidden ? '  card-img card-img-o' : '' ?>">
    <div class="card-header<?= $hidden ? ' card-header-o' : '' ?>">
        <?= $cardName ?><?= $hidden ? ' <i class="bi bi-chevron-down"></i>' : '' ?>
    </div>
    <div class="card-body<?= $hidden ? ' card-body-o' : '' ?>">
        <?php foreach($attributes as $attribute) { echo $attribute; } ?>
    </div>
</div>
