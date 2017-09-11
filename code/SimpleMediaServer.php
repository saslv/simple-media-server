<?php

//require('imdb.class.php');

class SimpleMediaServer{
    public $mediaDirectory = '/';
    public $videoExtensions = ['avi', 'mkv', 'mov', 'mp4'];
    public $dataFileName = 'data/db.json';

    public $basePath = false;

    function __construct($params = [])
    {
        foreach($params as $key => $val){
            $this->$key = $val;
        }

        if(!$this->basePath){
            $this->basePath = realpath(dirname(__FILE__) . '/..') . '/';
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
                    'cast' => [],
                    'genre' => 'unknown',
                    'rating' => '?',
                    'poster_url' => false,
                    'poster_url_big' => false,
                    'plot' => '',
                    'imdb_url' => '',
                ];

                if(
                    !$this->loadIMDBInfo($imdb_name, 'movie', $movieInfo) &&
                    !$this->loadIMDBInfo($imdb_name, 'all', $movieInfo)
                ){
                    self::error('Not found at IMDB (' . $imdb_name . ')');
                }

                $data[] = $movieInfo;
            }
        }

        $data = $this->array_msort($data, ['title' => SORT_ASC]);

        file_put_contents($this->basePath . $this->dataFileName, json_encode($data));
    }

    private function loadIMDBInfo($imdb_name, $imdb_type, &$movieInfo){
        $oIMDB = new IMDB($imdb_name, 60, $imdb_type, realpath($this->basePath));
        $oIMDB->bArrayOutput = true;

        if ($oIMDB->isReady) {
            self::success('Found at IMDB ' . $imdb_type . ' (' . $imdb_name . ')');
            $movieInfo['title'] = $oIMDB->getTitle();
            $movieInfo['year'] = $oIMDB->getYear();
            $movieInfo['rating'] = $oIMDB->getRating();
            $movieInfo['poster_url'] = $oIMDB->getPoster();
            $movieInfo['poster_url_big'] = $oIMDB->getPoster('big');
            $movieInfo['genre'] = $oIMDB->getGenre();
            $movieInfo['cast'] = $oIMDB->getCast();
            $movieInfo['plot'] = $oIMDB->getPlot();
            $movieInfo['imdb_url'] = $oIMDB->getUrl();
            return true;
        }else{
            return false;
        }
    }

    public function loadData($id = false){
        $data = json_decode(file_get_contents($this->basePath . $this->dataFileName), true);

        if(is_int($id)){
            if(array_key_exists($id, $data) && isset($data[$id])){
                return $data[$id];
            }else{
                return false;
            }
        }

        return $data;
    }

    public function actionList(){
        $data = $this->loadData();

        $this->render('list', [
            'data' => $data,
        ]);
    }

    public function actionView(){
        $id = (int)$_GET['id'];

        $item = $this->loadData($id);

        if(!$item){
            return $this->actionError('Not found!');
        }

        $this->render('view', [
            'item' => $item,
            'id' => $id
        ]);
    }

    public function actionPlay(){
        $id = (int)$_GET['id'];

        $item = $this->loadData($id);

        if(!$item){
            return $this->actionError('Not found!');
        }

        $this->render('play', [
            'item' => $item,
            'id' => $id
        ]);
    }

    private function render($view_file, $params = []){
        extract($params);

        ob_start();
        include $this->basePath . 'views/' . $view_file . '.php';

        $content = ob_get_clean();

        ob_end_clean();

        include $this->basePath . 'views/layout.php';

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

    public function actionError($message = 'General Error'){
        $this->render('error', [
            'message' => $message
        ]);
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