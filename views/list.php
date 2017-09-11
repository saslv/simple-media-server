<ol class="breadcrumb">
    <li class="active">Films</li>
</ol>

<div class="row films-list">
<?php foreach ($data as $i => $movieInfo): ?>
    <div class="col-md-2 col-sm-4 col-xs-6">
        <a href="?action=view&id=<?= $i ?>" class="thumbnail film">
            <div class="image" style="background-image: url('<?= $movieInfo['poster_url'] ?>');">
                <div class="caption">
                    <?= $movieInfo['title'] ?>
                    (<?= $movieInfo['year'] ?>)
                </div>
            </div>
        </a>
    </div>
<?php endforeach ?>
</div>