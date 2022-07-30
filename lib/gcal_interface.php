<?php

function gcal_access_token ()
{
  if (!isset ($_SESSION['gcal_access_token'])
      || !isset ($_SESSION['gcal_expires'])
      || intval ($_SESSION['gcal_expires']) <= time ())
    {
      $ch = curl_init ();
      curl_setopt ($ch, CURLOPT_URL,
		   'https://accounts.google.com/o/oauth2/token');
      curl_setopt ($ch, CURLOPT_POST, true);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt ($ch, CURLOPT_POSTFIELDS,
		   array ('refresh_token' => GCAL_REFRESH_TOKEN,
			  'client_id' => GCAL_ID,
			  'client_secret' => GCAL_SECRET,
			  'grant_type' => 'refresh_token'));
      $result = json_decode (curl_exec ($ch), true);
      curl_close ($ch);

      if (isset ($result['access_token']))
	{
	  $_SESSION['gcal_access_token'] = $result['access_token'];
	  $_SESSION['gcal_expires'] = intval ($result['expires_in']) + time ();
	}
    }

  return (isset ($_SESSION['gcal_access_token'])
	  ? $_SESSION['gcal_access_token'] : false);
}

function gcal_events ($months = 3)
{
  $access_token = gcal_access_token ();
  $calid = GCAL_RESOURCE;
  $min = date (DATE_ATOM);
  $max = date (DATE_ATOM, time () + $months * 30 * 24 * 60 * 60);
  $ch = curl_init ();
  curl_setopt ($ch, CURLOPT_URL,
	       "https://www.googleapis.com/calendar/v3/calendars/$calid/events?timeMin=$min&timeMax=$max&singleEvents=true");
  curl_setopt ($ch, CURLOPT_POST, false);
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt ($ch, CURLOPT_HTTPHEADER,
	       array ("Authorization: Bearer $access_token\r\n"));
  $events = json_decode (curl_exec ($ch), true);
  curl_close ($ch);

  return $events;
}

function gcal_get_event ($gcal_id)
{
  $access_token = gcal_access_token ();
  $calid = GCAL_RESOURCE;
  $ch = curl_init ();
  curl_setopt ($ch, CURLOPT_URL,
	       "https://www.googleapis.com/calendar/v3/calendars/$calid/events/$gcal_id");
  curl_setopt ($ch, CURLOPT_POST, false);
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt ($ch, CURLOPT_HTTPHEADER,
	       array ("Authorization: Bearer $access_token\r\n"));
  $event = json_decode (curl_exec ($ch), true);
  curl_close ($ch);

  return $event;
}

function email_to_attendee ($cnx, $email)
{
  $sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com',
		     "(|(mail=$email)(adacorealtemail=$email))",
		     array ('uid', 'sn', 'givenName'));

  $info = ldap_get_entries ($cnx, $sr);
  if ($info['count'] == 1)
    return array ('email' => $info[0]['uid'][0] . '@adacore.com',
		  'displayName' => ($info[0]['givenname'][0] . ' '
				    . $info[0]['sn'][0]));
  else
    return array ('email' => $email);
}

function add_gcal_event ($db, $bookId, $emails, $update = false, $recurPrd,
			 $recurInt)
{
  $cnx = ldap_connect ('127.0.0.1');
  ldap_bind ($cnx);
  $attendees = array (array ('email' => GCAL_RESOURCE,
			     'displayName' => 'US conferencing system'));

  foreach ($emails as $email)
    $attendees[] = email_to_attendee ($cnx, $email);

  $result = $db->query ("SELECT starttime,endtime,confOwner,confno,confDesc,emailopts,emailText,gcal_id FROM booking WHERE bookId=$bookId");
  $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
  if (strchr ($row['emailopts'], 't'))
    $conf['description'] = $row['emailText'];

  $access_tok = gcal_access_token ();
  $owner = email_to_attendee ($cnx, $row['confOwner']);
  $calid = $owner['email'];
  $sequence = 1;
  $url = "https://www.googleapis.com/calendar/v3/calendars/$calid/events";
  if (!$update && strchr ($row['emailopts'], 's'))
    $url .= '?sendNotifications=True';
  else if ($update && isset ($row['gcal_id']) && $row['gcal_id'])
    {
      $url .= "/$row[gcal_id]";
      $options = array ('http' =>
			array ('ignore_errors' => true,
			       'header'
			       => ("Content-Type: application/json\r\n" .
				   "Accept: application/json\r\n" .
				   "Authorization: Bearer $access_tok\r\n")));
      $context  = stream_context_create ($options);
      $event = json_decode (file_get_contents ($url, false, $context), true);
      if (isset ($event['sequence']))
	$sequence = $event['sequence'] + 1;
    }
  else
    $update = false;

  $conf = array ('start' => array ('dateTime' =>
				   date (DATE_ATOM,
					 strtotime ($row['starttime'])
					 + get_tz_offset ()),
				   'timeZone' => $_SESSION['timezone']),
		 'end' => array ('dateTime' =>
				 date (DATE_ATOM,
				       strtotime ($row['endtime'])
				       + get_tz_offset ()),
				 'timeZone' => $_SESSION['timezone']),
		 'summary' => $row['confDesc'],
		 'location' => "US Conferencing System ($row[confno])",
		 'attendees' => $attendees, 'sequence' => $sequence);

  if ($recurPrd > 1)
    {
      if ($recurInt >= 604800)
	{
	  $freq = 'WEEKLY';
	  $interval = $recurInt / 604800;
	}
      else
	{
	  $freq = 'DAILY';
	  $interval = $recurInt / 86400;
	}

      $recLine = "RRULE:FREQ=$freq";
      if ($interval != 1)
	$recLine .= ";INTERVAL=$interval";
      $recLine .= ";COUNT=$recurPrd";
      $conf['recurrence'] = array (0 => $recLine);
    }

  $options = array ('http' =>
		    array ('method'  => $update ? 'PUT' : 'POST',
			   'content' => json_encode ($conf),
			   'ignore_errors' => true,
			   'header'
			   =>  ("Content-Type: application/json\r\n" .
				"Accept: application/json\r\n" .
				"Authorization: Bearer $access_tok\r\n")));

  $context  = stream_context_create ($options);
  $result = json_decode (file_get_contents ($url, false, $context), true);
  if (isset ($result['id']))
    {
      $modTime = canon_time ($result['updated']);
      $db->query ("UPDATE booking SET gcal_id='$result[id]',dateMod='$modTime' WHERE bookId=$bookId");
    }
  ldap_unbind ($cnx);
}

function patch_gcal_event ($calid, $gcalId, $data)
{
  $access_tok = gcal_access_token ();
  $options = array ('http' =>
		    array ('method' => 'PATCH',
			   'content' => json_encode ($data),
			   'ignore_errors' => true,
			   'header'
			   =>  ("Content-Type: application/json\r\n" .
				"Accept: application/json\r\n" .
				"Authorization: Bearer $access_tok\r\n")));
  $context  = stream_context_create ($options);
  $url = "https://www.googleapis.com/calendar/v3/calendars/$calid/";
  $url .= "events/$gcalId";
  file_get_contents ($url, false, $context);
}

function delete_gcal_event ($db, $gcalId, $owner)
{
  $cnx = ldap_connect ('127.0.0.1');
  ldap_bind ($cnx);

  $access_token = gcal_access_token ();
  $owner = email_to_attendee ($cnx, $owner);
  $cal = $owner['email'];
  $url = "https://www.googleapis.com/calendar/v3/calendars/$cal/events/$gcalId";

  $options = array ('http' =>
		    array ('method'  => 'DELETE',
			   'header'
			   =>  ("Authorization: Bearer $access_token\r\n")));

  $context  = stream_context_create ($options);
  file_get_contents ($url, false, $context);
  ldap_unbind ($cnx);
}
?>
