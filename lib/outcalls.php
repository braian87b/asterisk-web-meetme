<?php

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';
include '../../phpagi/phpagi-asmanager.php';
include '../../php/confdata.php';

/* Here we do any needed callouts.  Collect the list.  */

$now = getConfDate ();
$early = getConfDate (time () + 3 * 60);
$rows = array ();
$query = 'SELECT c.id, c.callout, c.user_id, u.email, ';
$query .= 'u.first_name, u.last_name, b.confno, b.pin, b.adminpin ';
$query .= 'FROM callouts c, user u, booking b ';
$query .= "WHERE b.starttime<='$early'AND b.endtime>='$now' ";
$query .= 'AND u.id=c.user_id AND c.book_id=b.bookId';
$result = $db->query ($query);
while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
  $rows[] = $row;

do_callouts ($rows, $db);
?>
