<?php
ob_implicit_flush (true);
set_time_limit (0);

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';

date_default_timezone_set (TIMEZONE);
$now = getConfDate ();
$sentIds = array ();

/* Here we send each type of reminder.  */

foreach ($Remind_Options as $opt)
  {
    $future = getConfDate (time () + $opt[2]);
    $upper = strtoupper ($opt[1]);
    $query = "SELECT remind, bookId FROM booking WHERE endtime>'$now' ";
    $query .= "AND starttime<='$future' AND remind LIKE '%$opt[1]%' ";
    $query .= "AND remind NOT LIKE '%$upper%' COLLATE latin1_bin ";
    $query .= "AND emailopts LIKE '%s%' ";
    $result = $db->query ($query);
    while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
      {
	extract ($row);
	if (!isset ($sentIds[$bookId]))
	  send_email ($db, $bookId, '', 1, $opt[2] > 900 ? 1 : 0);

	$remind .= $upper;
	$db->query
	  ("UPDATE booking SET remind='$remind' WHERE bookId='$bookId'");
	$sentIds[$bookId] = 1;
      }
  }
?>
