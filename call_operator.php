<?php

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/phones.php';
include '/var/www/phpagi/phpagi-asmanager.php';

getpost_ifset (array ('confno', 'name', 'invite_num', 'pin'));
if (!isset ($pin))
  $pin = '';

session_start ();
if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth'])
    && ! (isset ($_SESSION['call_sha'])
	  && ($_SESSION['call_sha']
	      == sha1 ("fdhj5478:$confno:$name:$invite_num:$pin"))))
 exit;

echo json_encode (callout ($confno, $name, $invite_num, $pin));
?>
