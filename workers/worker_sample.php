<?php
set_time_limit(0);
require 'worker.class.php';
define('WORKER_NAME',basename(__FILE__, '.php'));

$worker = new workerClass;
$queue = new queueClass;

//Continuous loop
while(true){
    //Do stuff here
    //Example get task from queue and process it    
    
    //Check if process should exit
    $worker->shouldExit();
}


?>
