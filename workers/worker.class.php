<?php

/**
 * Description of workerClass
 *
 * tools that are used by workers
 */
define('DEBUG', false);

class workerClass {
    
    private $pid = null;
    
    //Setup by echoing process id (required for process control) and setting up debug level
    public function __construct() {
        
        $this->pid = getmypid();
        echo $this->pid;        
        
        set_time_limit(0);
        
        if (DEBUG){
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }
        else {
            ini_set('display_errors', 0);
            error_reporting(0);    
        } 
        
        return $this->pid;
    }
    
    
    //Check if process id is on the exit list
    public function shouldExit(){
        $this->pid = $this->pid+0;
        
        //Connect to mongodb
        $mongoDb = new Mongo();
        $dbname = 'processControl';
        $collection = 'workers';
        $mongoDb = $mongoDb->$dbname;
        
        $exit = $mongoDb->command(array(
            'findAndModify' => $collection,
            'query' => array('process' => 'kill', 'pids' => $this->pid),
            'update' => array('$pull' => array('pids' => $this->pid)),
            )
        );           
        
        //$exit = $mongoDb->findOne(array('process' => 'kill', 'pids' => $this->pid));
        //$mongoDb->update(array('process' => 'kill'),array('$pull' => array('pids' => $this->pid)));
        
        //Exit process if pid is on the list
        if (!empty($exit['value'])){
            $mongoDb = null;
            die;
        }        
        
        $mongoDb = null;
    }
    
}

?>
