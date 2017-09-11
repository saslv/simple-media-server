<?php

require('./../vendor/autoload.php');
require('./../code/SimpleMediaServer.php');

$server = new SimpleMediaServer([
//    'mediaDirectory' => '/var/www/html/tools/pi_local/media'
    'mediaDirectory' => '/srv/dev-disk-by-id-ata-ST1000LM035-1RK172_WDEBYSVL-part1/films'
]);

try{
    $server->autoRoute();
}catch(Exception $e){
    $server->actionError();
}