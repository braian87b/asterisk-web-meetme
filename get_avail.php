<?php

include 'lib/defines.php';
include 'lib/gcal_interface.php';

session_start(); 
if ((defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
    || !isset ($_GET['date']) || !isset ($_GET['email']))
 exit;

function email_to_uid ($cnx, $email)
{
  $emails = array ();
  $listname = str_replace ('@adacore.com', '', $email);
  $sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com',
		     "(|(mail=$email)(adacorealtemail=$email)(adacoreemaillist=$listname))",
		     array ('uid'));
  $info = ldap_get_entries ($cnx, $sr);
  for ($i = 0; $i < $info['count']; $i++)
    $emails[$info[$i]['uid'][0] . '@adacore.com'] = true;

  if (!count ($emails))
    $emails = array ($email => true);

  return $emails;
}

$cnx = ldap_connect ('127.0.0.1');
ldap_bind ($cnx);

date_default_timezone_set ($_SESSION['timezone']);

$minNum = strtotime ($_GET['date'] . ' 00:00');
$maxNum = $minNum + (1440*60);

$ids = array ();
foreach ($_GET['email'] as $email)
  $ids += email_to_uid ($cnx, $email);

$cals = array ();
foreach ($ids as $id=>$one)
  $cals[] = array ('id' => $id);

$access_token = gcal_access_token ();

$data = array ('timeMin' => date (DATE_ATOM, $minNum),
	       'timeMax' => date (DATE_ATOM, $maxNum),
	       'timeZone' => $_SESSION['timezone'],
	       'calendarExpansionMax' => 1000,
	       'items' => $cals);

$options = array ('http' => array ('method'  => 'POST',
				   'content' => json_encode ($data),
				   'header'
				  =>  ("Content-Type: application/json\r\n" .
				       "Accept: application/json\r\n" .
				       "Authorization: Bearer $access_token\r\n")));
$context  = stream_context_create ($options);
$response = json_decode (file_get_contents ('https://www.googleapis.com/calendar/v3/freeBusy',
					    false, $context), true);;

$avails = array ();
for ($i = floor ($minNum / 300); $i < floor ($maxNum / 300); $i++)
  $avails[$i] = 1;

$errors = array ();

foreach ($response['calendars'] as $id=>$cal)
{
  if (isset ($cal['errors']))
    $errors[] = $id;

  foreach ($cal['busy'] as $time)
    for ($i = floor (strtotime ($time['start']) / 300);
	 $i < floor (strtotime ($time['end']) / 300); $i++)
      $avails[$i] = 0;
}

$was = 0;
$intervals = array ();
foreach ($avails as $td=>$avail)
{
  if (!$was & $avail)
    $start = ($td * 5) - ($minNum / 60);
  else if ($was && !$avail)
    $intervals[] = array ($start, ($td * 5) - ($minNum / 60));

  $was = $avail;
}

if ($was)
  $intervals[] = array ($start, ($maxNum / 60) - ($minNum / 60));

echo json_encode (array ('unknowns' => $errors, 'intervals' => $intervals));
?>
