<?php
include 'gcal_interface.php';
include 'defines.php';

$access_token = gcal_access_token ();
$calid = GCAL_RESOURCE;
$url = "https://www.googleapis.com/calendar/v3/calendars/$calid/events/watch";
$content = array ('id' => 'ConfSysCal', 'type' => 'web_hook',
		  'address' => 'https://asterisk.gnat.com/calpush.php');
$options = array ('http' =>
		  array ('method'  => 'POST',
			 'content' => json_encode ($content),
			 'ignore_errors' => true,
			 'header'
			 =>  ("Content-Type: application/json\r\n" .
			      "Accept: application/json\r\n" .
			      "Authorization: Bearer $access_token\r\n")));

$context  = stream_context_create ($options);
$result = json_decode (file_get_contents ($url, false, $context), true);
?>
