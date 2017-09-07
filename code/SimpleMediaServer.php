<?php

require('imdb.class.php');

class SimpleMediaServer{
    public $mediaDirectory = '/';
    public $videoExtensions = ['avi', 'mkv', 'mov', 'mp4'];
    public $dataFileName = './../data/db.json';

    function __construct($params = [])
    {
        foreach($params as $key => $val){
            $this->$key = $val;
        }
    }

    private function array_msort($array, $cols)
    {
        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
        }
        $eval = 'array_multisort(';
        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\''.$col.'\'],'.$order.',';
        }
        $eval = substr($eval,0,-1).');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k,1);
                if (!isset($ret[$k])) $ret[$k] = $array[$k];
                $ret[$k][$col] = $array[$k][$col];
            }
        }
        return $ret;

    }

    private function readAllFiles($root = '.'){
        $files  = array('files'=>array(), 'dirs'=>array());
        $directories  = array();
        $last_letter  = $root[strlen($root)-1];
        $root  = ($last_letter == '\\' || $last_letter == '/') ? $root : $root.DIRECTORY_SEPARATOR;

        $directories[]  = $root;

        while (sizeof($directories)) {
            $dir  = array_pop($directories);
            if ($handle = opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $file  = $dir.$file;
                    if (is_dir($file)) {
                        $directory_path = $file.DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][]  = $directory_path;
                    } elseif (is_file($file)) {
                        $files['files'][]  = $file;
                    }
                }
                closedir($handle);
            }
        }

        return $files;
    }

    public static function parseMovieName($name){
        $re = '/^(
              (?P<ShowNameA>.*[^ (_.]) # Show name
                [ (_.]+
                ( # Year with possible Season and Episode
                  (?P<ShowYearA>\d{4})
                  ([ (_.]+S(?P<SeasonA>\d{1,2})E(?P<EpisodeA>\d{1,2}))?
                | # Season and Episode only
                  (?<!\d{4}[ (_.])
                  S(?P<SeasonB>\d{1,2})E(?P<EpisodeB>\d{1,2})
                | # Alternate format for episode
                  (?P<EpisodeC>\d{3})
                )
            |
              # Show name with no other information
              (?P<ShowNameB>.+)
            )/mx';
        preg_match_all($re, $name, $matches, PREG_SET_ORDER, 0);

        $info = [
            'title' => false,
            'year' => false,
            'season' => false,
            'episode' => false,
        ];

        if(array_key_exists(0, $matches)){
            if(array_key_exists('ShowNameA', $matches[0])){
                $info['title'] = $matches[0]['ShowNameA'];
            }
            if(array_key_exists('ShowNameB', $matches[0])){
                $info['title'] = $matches[0]['ShowNameB'];
            }
            if(array_key_exists('ShowYearA', $matches[0])){
                $info['year'] = $matches[0]['ShowYearA'];
            }
            if(array_key_exists('SeasonA', $matches[0])){
                $info['season'] = $matches[0]['SeasonA'];
            }
            if(array_key_exists('SeasonB', $matches[0])){
                $info['season'] = $matches[0]['SeasonB'];
            }
            if(array_key_exists('EpisodeA', $matches[0])){
                $info['episode'] = $matches[0]['EpisodeA'];
            }
            if(array_key_exists('EpisodeB', $matches[0])){
                $info['episode'] = $matches[0]['EpisodeB'];
            }
            if(array_key_exists('EpisodeC', $matches[0])){
                $info['episode'] = $matches[0]['EpisodeB'];
            }
        }

        return $info;
    }

    public function actionReload(){
        $filesAndDirectories = $this->readAllFiles($this->mediaDirectory);

        $data = [];

        foreach($filesAndDirectories['files'] as $filePath){
            $path_parts = pathinfo($filePath);

            if(in_array($path_parts['extension'], $this->videoExtensions)){
                set_time_limit(10);

                $nameInfo = self::parseMovieName($path_parts['filename']);
                $imdb_name = $nameInfo['title'] . ' (' . $nameInfo['year'] . ')';

                $movieInfo = [
                    'filePath' => $filePath,
                    'fileName' => $path_parts['filename'],
                    'fileExtension' => $path_parts['extension'],
                    'title' => $nameInfo['title'],
                    'year' => $nameInfo['year'],
                    'rating' => '?',
                    'poster_url' => false,
                ];

                $oIMDB = new IMDB($imdb_name, 60, 'movie');
                if ($oIMDB->isReady) {
                    self::success('Found at IMDB Movies (' . $imdb_name . ')');
                    $movieInfo['title'] = $oIMDB->getTitle();
                    $movieInfo['year'] = $oIMDB->getYear();
                    $movieInfo['year'] = $oIMDB->getYear();
                    $movieInfo['rating'] = $oIMDB->getRating();
                    $movieInfo['poster_url'] = $oIMDB->getPoster();
                }else{
                    $oIMDB = new IMDB($imdb_name, 60, 'all');
                    if ($oIMDB->isReady) {
                        self::warning('Found at IMDB All (' . $imdb_name . ')');
                        $movieInfo['title'] = $oIMDB->getTitle();
                        $movieInfo['year'] = $oIMDB->getYear();
                        $movieInfo['year'] = $oIMDB->getYear();
                        $movieInfo['rating'] = $oIMDB->getRating();
                        $movieInfo['poster_url'] = $oIMDB->getPoster();
                    }else{
                        self::error('Not found at IMDB (' . $imdb_name . ')');
                    }
                }

                $data[] = $movieInfo;
            }
        }

        $data = $this->array_msort($data, ['title' => SORT_ASC]);

        file_put_contents($this->dataFileName, json_encode($data));
    }

    public function actionList(){
        $data = json_decode(file_get_contents($this->dataFileName), true);

        $html =
            '<table class="table table-hover">
                <thead>
                    <tr>
                        <th colspan="6">
                            SimpleMediaServer
                            <span class="pull-right">Total Movies in DB: ' . count($data) .'</span>
                        </th>
                    </tr>
                    <tr>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Year</th>
                        <th>IMDB Rating</th>
                        <th>Extension</th>
                        <th>Open</th>
                    </tr>
                </thead>';

        $index = 0;
        foreach ($data as $movieInfo){
            $html .=
                '<tr>
                    <td>
                        <img src="' . $movieInfo['poster_url'] . '" class="img-responsive">
                    </td>
                    <td>' . $movieInfo['title'] . '</td>
                    <td>' . $movieInfo['year'] . '</td>
                    <td>' . $movieInfo['rating'] . '</td>
                    <td>' . $movieInfo['fileExtension'] . '</td>
                    <td><a href="?view=' . $index . '">[open]</a></td>
                </tr>
            ';
            $index++;
        }

        $html .= '</table>';

        return $this->render($html);
    }

    public function actionView($index){
        $html = '
            <object classid="clsid:9BE31822-FDAD-461B-AD51-BE1D1C159921" codebase="http://download.videolan.org/pub/videolan/vlc/last/win32/axvlc.cab" id="vlc">
                <embed type="application/x-vlc-plugin" pluginspage="http://www.videolan.org" name="vlc" width="800px" height="600px" target="http://youtube.com" />
            </object>
        ';

        return $this->render($html);
    }

    private function render($html_content){
        echo
        '<html>
            <head>
                <title>SimpleMediaServer</title>
                <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
            </head>
            <body>' .
            $html_content .
            '</body>
        </html>';

        return;
    }

    public static function success($message){
        echo '<div style="background-color: green;">' . $message . '</div>';
    }

    public static function error($message){
        echo '<div style="background-color: red;">' . $message . '</div>';
    }

    public static function warning($message){
        echo '<div style="background-color: yellow;">' . $message . '</div>';
    }

    public function actionError(){
        echo 'Error occurred!';
        die();
    }

    public function autoRoute(){
        if(!isset($_GET['action'])){
            $action = 'list';
        }else{
            $action = $_GET['action'];
        }

        $methodName = 'action' . ucfirst($action);

        if(method_exists($this, $methodName)){
            $this->$methodName();
        }else{
            $this->actionError();
        }
    }
}