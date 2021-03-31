<?php
use function Opis\Closure\{unserialize as u};
require_once(dirname(__DIR__, 4).'/vendor/autoload.php');
\App\Http\Controllers\LogController::insertLog('thread.php pass 1');

$jobId = $argv[1];
$jobsDir = $argv[2];
$logsDir = $argv[3];
$helperClass = $argv[4];
$helper = new $helperClass;
$basePath = $helper->getAppBasePath();
if(!file_exists("{$basePath}/{$jobsDir}/{$jobId}_closure.ser")) {
    \App\Http\Controllers\LogController::insertLog('thread.php pass 2');
    die("Closure file for Job ID: $jobId doesn't exist");
}
if(!file_exists("{$basePath}/{$jobsDir}/{$jobId}_arguments.ser")) {
    \App\Http\Controllers\LogController::insertLog('thread.php pass 3');
    die("Arguments file for Job ID: $jobId doesn't exist");
}
try{
    \App\Http\Controllers\LogController::insertLog('thread.php pass 4');
    $helper->bootstrap();
	$wrapper = unserialize(file_get_contents("{$basePath}/{$jobsDir}/{$jobId}_closure.ser"));
	$arguments = u(file_get_contents("{$basePath}/{$jobsDir}/{$jobId}_arguments.ser"));
	file_put_contents("{$basePath}/{$logsDir}/smt_{$jobId}.log", $wrapper($arguments), FILE_APPEND);
}catch(Exception $e){
	file_put_contents("{$basePath}/{$logsDir}/smt_{$jobId}_error.log", "Caught Exception at ".date('Y-m-d H:i:s').": ".$e->getMessage()." at line: ".$e->getLine()." on file: ".$e->getFile().". Stack trace: ".$helper::getExceptionTraceAsString($e),FILE_APPEND);
}
//garbage collection..
unlink("{$basePath}/{$jobsDir}/{$jobId}_closure.ser");
unlink("{$basePath}/{$jobsDir}/{$jobId}_arguments.ser");
exit;
