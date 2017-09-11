<?php

session_start();

error_reporting(E_ERROR);

function dbg($string){
//    echo '<div>' . $string .'</div>';
}

define('FFMPEG_PRIORITY', '2'); /* man nice */

define('CHUNKSIZE', 500*1024); /* how many bytes should fread() read from stdout of FFmpeg? */

set_time_limit(10);
ignore_user_abort(true); /* do not terminate script execution if disconnect */
header("Connection: close");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache");
header("Pragma: no-cache");

header('Content-type: video/webm');
header('Accept-Ranges: none');

define('P_STDIN', 0);
define('P_STDOUT', 1);
define('P_STDERR', 2);

$file = $_GET['file'];

$cmd = 'ffmpeg -i "' . $file . '"" -c:v libvpx -f webm -b:v 400K -crf 10 -an -y';

$cmd .= " pipe:1"; // ffmpeg should output to stdout (other messages to stderr)
/* execute ffmpeg */
$descriptorspec = array(
    P_STDIN => array("pipe", "r"),  // stdin (we write the process reads)
    P_STDOUT => array("pipe", "w"),  // stdout (we read the process writes)
    P_STDERR => array("pipe", "w")   // stderr (we read the process writes)
);
$process = proc_open("nice -n ".FFMPEG_PRIORITY." ".$cmd, $descriptorspec, $pipes);
dbg("Started FFmpeg process.\nCommand Line: $cmd");
$stdout_size = 0;
if (is_resource($process)) {
    while(!feof($pipes[P_STDOUT])){
        $chunk = fread($pipes[P_STDOUT], CHUNKSIZE);
        $stdout_size += strlen($chunk);
        if ($chunk !== false && !empty($chunk)){
            echo $chunk;
            /* flush output */
            if (ob_get_length()){
                @ob_flush();
                @flush();
                @ob_end_flush();
            }
            @ob_start();
            //dbg("Chunk sent to browser and flush output buffers");
        }
        if(connection_aborted()){
            dbg("Connection aborted.");
            break;
        }
    }
    dbg("Finished reading from stdout.");
    fclose($pipes[P_STDOUT]);
    if($stdout_size == 0){ /* not read anything from stdout indicates error */
        $stderr = stream_get_contents($pipes[P_STDERR]);
        dbg("An Error Occured. Stderr: ".$stderr);
    }
    fclose($pipes[P_STDERR]);
    /* this should quit the encoding process */
    fwrite($pipes[P_STDIN], "q\r\n");
    fclose($pipes[P_STDIN]);
    $return_value = proc_close($process);
    dbg("Process closed with return value: ".$return_value);
}