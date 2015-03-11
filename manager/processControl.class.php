<?php

/**
 * Description of processControlClass
 * Manages launching background processes
 * @author papaspiridis
 */

class processControlClass {
    
    private $checkingNow = false;
    private $mongodb = null;

    //Connect to db (defined in config.php)
    private function mongoConnect(){
        $m = new Mongo();
        $dbname = 'processControl';
        $this->mongodb = $m->$dbname;
    }    
    
    //Disconnect from db
    private function mongoDisconnect(){
        $this->mongodb = null;
    }    

    
    //Get when file was last modified (unix timestamp)
    public function fileLastModified($file) {
        return filemtime(WORKER_BASE_PATH."/$file.php");
    }
    
    
    //Launches process and gets PID
    public function launchOne($file){
        
        $path = WORKER_BASE_PATH;
        $command = PHP_CLI . " $path/$file.php";
        
        $pid = shell_exec("nohup $command > /dev/null 2> /dev/null & echo $!");
        $pid = $pid + 0;
        
        //Log launch of process
        if (LOG_PROCESS_CONTROL){
            $this->logLaunch($file,$pid);
        }
        
        return $pid;
    }
    
    
    //Launches multiple proecesses
    //Send array of existing running processes to store all pids correctly
    public function launchMulti($file,$instances,$existing = null){
        
        $pids = array();
        for ($i=1;$i<=$instances;$i++){
            $pids[] = $this->launchOne($file);
        }
        
        if ($existing) {
            $pids = array_merge($existing,$pids);
        }
        
        $modified = $this->fileLastModified($file);
        $modified = $modified + 0;
        
        $key = $this->storeProcessIds($file, $pids, $modified);
        return $key;
    }
    
    
    
    //Logs starting of process
    public function logLaunch($file,$pid){
        $fp = fopen('processes.log','a');
        fwrite($fp,'Time: ' . date('d/m/Y H:i:s | ') . "File: $file.php  | PID: $pid\n");
        fclose($fp);
    }
    
    
    
    //Keep process id's for a task in archive
    public function storeProcessIds($file,$pids,$modified){
        if (!$this->mongodb) $this->mongoConnect();
        $collection = $this->mongodb->workers;        
        $collection->update(array('process'=>$file),array('$set'=>array('pids'=>$pids,'modified'=>$modified)),array('upsert'=>true));
        $this->mongoDisconnect();
        return $file;
    }
    
    
    //Get process id's for a task from archive
    public function getProcessIds($file){
        if (!$this->mongodb) $this->mongoConnect();
        $collection = $this->mongodb->workers;
        $pids = $collection->findOne(array('process'=>$file));
        $pids = $pids['pids'];
        $this->mongoDisconnect();
        return $pids;
    }

    
    //Get process modified time (version) for a task from archive
    public function getRunningProcessVersion($file){
        if (!$this->mongodb) $this->mongoConnect();
        $collection = $this->mongodb->workers;
        $modified = $collection->findOne(array('process'=>$file));
        $modified = $modified['modified'];
        $this->mongoDisconnect();
        return $modified;
    }    
    
    
    //Check if process is still running
    public function isProcessRunning($pid){
        $output = '';
        exec("ps -p $pid", $output);

        if (count($output) > 1) {
            $running = true;
        }
        else {
            $running = false;
        }
        
        return $running;
    }
    
    
    //Check if processes are running (Accepts array of pids) and task name
    //If not all tasks are running, it will restart them
    //Returns the number of tasks it found not to be running
    public function areProcessesRunning($file,$minimum){
        
        //Get pids that are supposed to be running
        $pids = $this->getProcessIds($file);
        if (!$pids) $pids = array();
        
        foreach ($pids as $key => $pid){
             if (!$this->isProcessRunning($pid)){
                 unset($pids[$key]);
             }
        }

        //Launch new processes to replace dead ones
        $running = count($pids);
        if ($running < $minimum){
            $this->launchMulti($file, $minimum - $running, $pids);
        }
        
        //Return number of new tasks started
        return $minimum - $running;
    }
    
    
    //Check all process listed in config file
    public function checkAllProcesses($process_list){
        //Don't run again if in process of checking
        if ($this->checkingNow) return 0;
        
        //Set flag
        $this->checkingNow = true;
        
        $notRunning = array();
        foreach ($process_list as $pname => $p){
            $file = $p['file'];
            $minimum = $p['min'];
            $this->isProcessUsingNewestFile($file);
            $notRunning[$pname] = $this->areProcessesRunning($file, $minimum);
        }
        
        //Clear flag
        $this->checkingNow = false;
        
        $totalNotRunning = array_sum($notRunning);
        return $totalNotRunning;
    }
    
    
    //Check if process is using latest version of file
    public function isProcessUsingNewestFile($file) {
        $latest = $this->fileLastModified($file);
        $running = $this->getRunningProcessVersion($file);
        
        //Case where it is running latest version
        if ($running >= $latest) {
            return true;
        }
        //Case where there is a new one
        else {
            $this->scheduleProcessExitByFile($file);
            return false;
        }
    }
    
    
    //Schedules a task to exit
    function scheduleProcessExit($pid) {
        if (!$this->mongodb) $this->mongoConnect();
        $collection = $this->mongodb->workers;
        $pid=$pid+0;
        $res = $collection->update(array('process' => 'kill'),array('$push' => array('pids' => $pid)),array('upsert' => true));
        $this->mongoDisconnect();
        return $res;
    }
    
    
    //Schedules all processes for a specific task to exit
    function scheduleProcessExitByFile($file) {
        //Get pids that are supposed to be running
        $pids = $this->getProcessIds($file);
        
        foreach ($pids as $pid) {
            $this->scheduleProcessExit($pid);
        }
        return true;
    }
    
    
    //Schedules all processes to exit
    function scheduleProcessExitAll($process_list) {
        foreach ($process_list as $p){
            $file = $p['file'];
            $this->scheduleProcessExitByFile($file);
        }
        return true;
    }
    
    
    //Runs all process defined in the process list
    //Used when starting out from scratch
    public function startAllProcesses($process_list){
        $activeProcesses = array();
        foreach ($process_list as $pname => $parray){
            $activeProcesses[$pname] = $this->launchMulti($parray['file'], $parray['min']);
        }
        return $activeProcesses;
    }
    
    
    public function pr($in){
        echo '<pre>';
        print_r($in);
        echo '</pre>';
    }
    
    
}

?>
