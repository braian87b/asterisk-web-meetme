<?php
include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';


session_start ();
if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

getpost_ifset (array ('confno', 'bookId'));

$query = 'SELECT confOwner, recordingfilename, recordingformat from ' . DB_TABLESCHED . ' WHERE bookid=?';
$data = array ($bookId);

$result = $db->query ($query, $data);
$row = $result->fetchRow (DB_FETCHMODE_ASSOC);

if ($_SESSION['auth'] && ($_SESSION['privilege'] == "Admin"
			  || $row['confOwner'] == $_SESSION['userid']))
  {
    if (is_numeric ($confno) && is_numeric ($bookId))
      {
	$playfile = "$row[recordingfilename].$row[recordingformat]";
	header ('Content-Length: ' . filesize ($playfile));
	header ('Content-type: audio/x-wav');
	header('Cache-Control: private');
	header('Pragma: private');
	header('Content-Disposition: attachment; filename='
	       . basename ($playfile));
	readfile ($playfile);
      }
  }
?>
