<?php
//Stops all workers from running

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

//Switch to directory of file 
//Needed for PHP CLI
chdir( dirname ( __FILE__ ) );

require '../config.php';
require_once 'processControl.class.php';

$pc = new processControlClass();

$pc->scheduleProcessExitAll($PROCESS_LIST);

?>
