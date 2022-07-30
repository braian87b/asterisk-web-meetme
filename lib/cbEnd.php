#!/usr/bin/php -q
<?php
ob_implicit_flush (true);
set_time_limit (0);

require '/var/www/phpagi/phpagi-asmanager.php';
require 'lib/defines.php';
require 'lib/functions.php';
require 'lib/database.php';

function wmm_cdr ($ecode, $data, $server, $port)
{
  print_r ($data);
  global $db;
  global $dsn;

  $db->disconnect ();
  $db = DB::connect ($dsn);

  $query = 'SELECT bookId FROM booking WHERE ';
  $query .= 'starttime<now() + interval 10 minute AND ';
  $query .= "endtime>now() - interval 5 minute AND confno='$data[Conference]'";
  $bookId = $db->getOne ($query);
  $CIDname = $data['CallerIDName'];
  $CIDnum = $data['CallerIDNum'];
  $dur = intval ($data['Duration']);
  $query = "INSERT INTO cdr VALUES ('$bookId','$dur','$CIDname','$CIDnum')";
  if ($bookId && $CIDname != "END WARNING")
    $db->query ($query);
}

while (true)
  {
    $as = new AGI_AsteriskManager ();
    $res = $as->connect ();
    if ($res)
      {
	$as->add_event_handler ('confbridgeleave', 'wmm_cdr');

	while (true)
	  {
	    $res = $as->Command ('Ping');
	    if(!$res)
	      break;
	    sleep (5);
	  }
      }

    print "Asterisk unavailable.  Waiting for it to return!\r\n";
    $res = $as->disconnect ();
    sleep(5);
  }
?>
