<?php

// i've already setup this to run in cpanel /home/battleship/public_html/battleship/cron/cron.php
// you just have to uncomment the # exec lines to run every 5 sec.

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 15); //per exec shouldn't keep it busy for longer than 15 sec otherwise kill it.
$now = date('Y-m-d H:i:s');
echo "Start: $now\n";

//list all scripts here to run in intervals
	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');

sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";

	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";

	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";

	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";

	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";
	
	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	 
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";
	
	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	 
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";
	
	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	 
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";
	
	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	 
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";
	
	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	 
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";
	
	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');
	 
sleep(5); //this is in seconds
$now = date('Y-m-d H:i:s');
echo "$now\n";
	
	#exec('/usr/local/bin/php -q /home/battleship/public_html/battleship/cron/timer.php');

echo 'Cron ran successfully!';

?>