<ol class="breadcrumb">
    <li><a href="?">Films</a></li>
</ol>

<table class="table table-hover">
    <thead>
    <!--
    <tr>
        <th colspan="6">
            SimpleMediaServer
            <span class="pull-right">Total Movies in DB: <?= count($data) ?></span>
        </th>
    </tr>
    -->
    <tr>
        <th>Poster</th>
        <th>Title</th>
        <th>Year</th>
        <th>IMDB Rating</th>
        <th>Extension</th>
        <th>Open</th>
    </tr>
    </thead>

<?php

$index = 1;
foreach ($data as $i => $movieInfo){
    echo
        '<tr>
                    <td>
                        <img src="' . $movieInfo['poster_url'] . '" class="img-responsive">
                    </td>
                    <td>' . $movieInfo['title'] . '</td>
                    <td>' . $movieInfo['year'] . '</td>
                    <td>' . $movieInfo['rating'] . '</td>
                    <td>' . $movieInfo['fileExtension'] . '</td>
                    <td><a href="?action=view&id=' . $i . '">[open]</a></td>
                </tr>
            ';
    $index++;
}

?>

</table>
