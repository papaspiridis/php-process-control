# php-process-control
A PHP process control system for launching and monitoring continuously running workers
(This code was written in 2011, it's not really useful anymore.)

Uses MongoDb for registering processes

To get started use the worker sample as a template for your workers.
Register the workers in the config.php file, and how many instances you want to run.

Fire up the manager

To test it out, modify a worker, and it should reload.
Modify the config file, and everything should reload.

To stop all processes, 
1) kill the manager
2) run stopWorkers.php from the managers folder