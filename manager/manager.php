<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

//Switch to directory of file 
//Needed for PHP CLI
chdir( dirname ( __FILE__ ) );

require '../config.php';
require_once 'processControl.class.php';

//Set time that the manager started running
$start_time = time();

$pc = new processControlClass();

while (true){
    
    //Check if config file has been updated, shut down all processes, reload self, and exit
    if (filemtime('../config.php') >= $start_time) {
        $pc->scheduleProcessExitAll($PROCESS_LIST);
        sleep(2);
        $pc->launchOne('_manager');
        exit();
    }
    
    $plist = $pc->checkAllProcesses($PROCESS_LIST);
    
    //Do stuff here (populate queues, etc)    
    
    sleep(1);
}