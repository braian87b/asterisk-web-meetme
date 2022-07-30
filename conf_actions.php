<?php

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include '/var/www/phpagi/phpagi-asmanager.php';

session_start (); 

getpost_ifset (array ('confno', 'action', 'id'));
if ((defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
    || ! isset ($confno) || !is_numeric ($confno))
  exit;

$as = new AGI_AsteriskManager ();
$res = $as->connect ();
if (!$res)
  exit;
	
if ($action == 'unmute')
  $db->query ("DELETE FROM handup WHERE channel='$id'");

if ($action == 'mute' || $action == 'unmute' || $action == 'kick')
  $as->Command ("ConfBridge $action $confno $id");
else if ($action == 'lock' || $action == 'unlock')
  $as->Command ("ConfBridge $action $confno");
else if ($action == 'end')
  {
    $xml = `/usr/local/bin/meetout`;
    $array
      = json_decode (json_encode ((array) simplexml_load_string ($xml)), 1);
    $confs = array ();
    if (isset ($array['confroom']))
      {
	$confs = $array['confroom'];
	if (!isset ($confs[0]))
	  $confs = array (0 => $confs);
      }

    foreach ($confs as $conf)
      if ($conf['room'] == $confno)
	{
	  $callers = $conf['caller'];
	  if (!isset ($callers[0]))
	    $callers = array (0 => $callers);

	  foreach ($callers as $caller)
	    $as->send_request ('ConfbridgeKick',
			       array ('Channel' => $caller['channel'],
				      'Conference' => $conf['room']));
	}

    if (FORCE_END == 'YES')
      {
	$now_datetime = getConfDate ();
	$FG_TABLE_NAME = DB_TABLESCHED;
	$query = "UPDATE $FG_TABLE_NAME SET endtime=? WHERE confno=?";
	$data = array ($now_datetime, $confno);
	$db->query ($query, $data); 
      }
  }
?>
