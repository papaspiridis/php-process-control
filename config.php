<?php

//Set debug mode
define('DEBUG', true);

//PHP executable location
define ('PHP_CLI','php');

//Process Control Variables
define('WORKER_BASE_PATH','/www/workers/');

//Logging
define('LOG_PROCESS_CONTROL',true);

//Process List
//Each process is an array, with filename (without .php), min and max processes
$PROCESS_LIST = array(
    'task1' => array('file' => 'task1', 'min' => 3, 'max' => 5),
    'worker2' => array('file' => 'worker2', 'min' => 2, 'max' => 5)
);



?>
