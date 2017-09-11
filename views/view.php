<ol class="breadcrumb">
    <li><a href="?">Films</a></li>
    <li class="active"><?= $item['title'] ?></li>
</ol>

<div class="row">
    <div class="col-md-3">
        <img src="<?= $item['poster_url_big'] ?>" class="img-responsive img-thumbnail">
        <br>
        <br>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $item['title'] ?>
                <span class="badge pull-right"><?= $item['year'] ?></span>
            </div>
            <div class="panel-body">
                <div>
                    <?= $item['fileName'] . '.' . $item['fileExtension'] ?>
                </div>
                <h4>
                    <?= $item['plot'] ?>
                </h4>
                <h5>
                    Rating: <?= $item['rating'] ?>.
                    View at <a target="_blank" href="<?= $item['imdb_url'] ?>">IMDB</a>.
                </h5>
                <div>
                    Cast: <?= implode(', ', array_slice($item['cast'], 0, 10)) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <ul class="list-group">
            <a href="?action=play&id=<?= $id ?>" class="list-group-item list-group-item-success">Play</a>
            <a href="?action=download&id=<?= $id ?>" class="list-group-item">Download</a>
        </ul>

        <div class="panel panel-default">
            <div class="panel-heading">Genre</div>
            <ul class="list-group">
                <?php $i=0; foreach($item['genre'] as $genre): ?>
                    <?php $i++; if($i>10) break; ?>
                    <li class="list-group-item"><?= $genre ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</div>