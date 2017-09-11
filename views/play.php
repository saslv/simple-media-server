<ol class="breadcrumb">
    <li><a href="?">Films</a></li>
    <li><a href="?action=view&id=<?= $id ?>"><?= $item['title'] ?></a></li>
    <li class="active">Play</li>
</ol>

<div class="row">
    <div class="col-md-12">
        <video controls="none" class="img-thumbnail" style="width: 100%;">
            <source src="play.php?file=<?= $item['filePath'] ?>" type="video/webm">
            Your browser does not support the video tag.
        </video>
    </div>
</div>