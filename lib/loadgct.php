<?php

include 'lib/defines.php';
include 'lib/database.php';
include 'lib/functions.php';
include 'lib/phones.php';

$cnx = ldap_connect ('127.0.0.1');
ldap_bind ($cnx);

$events = gcal_events ();
print_r ($events);
exit;

foreach ($events['items'] as $event)
{
  $fields = 'confno, dateMod, maxUser, adminopts, opts, remind, emailopts';
  $query = "SELECT bookId, $fields FROM booking WHERE gcal_id='$event[id]'";
  $row = $db->query ($query)->fetchRow (DB_FETCHMODE_ASSOC);
  if (!$row)
    {
      $bookId = false;
      $under = strpos ($event['id'], '_');
      $confno = false;
      if ($under)
	{
	  $part = substr ($event['id'], 0, $under);
	  $query = "SELECT confno FROM booking WHERE ";
	  $query .= "gcal_id LIKE '$part%' ORDER BY endtime DESC";
	  $row2 = $db->query ($query)->fetchRow ();
	  if ($row2)
	    $confno = $row2[0];
	}

      if (!$confno)
	{
	  $confno = mt_rand (1000000, 9999999);
	  while ($db->query ("SELECT * FROM booking WHERE confno=$confno")
		 ->fetchRow ())
	    $confno = mt_rand (1000000, 9999999);
	}
    }
  else if (strtotime ($event['updated']) > strtotime ($row['dateMod']) + 1)
    extract ($row);
  else
    continue;

  $rows = gcal_to_rows($cnx, $db, $event['organizer']);
  if (!$rows)
    continue;

  // The organizer is assumed to be a single user

  $row = $rows[0];

  if (isset($event['location']))
    $location = $event['location'];
  else
    $location = '';
  if (!strpos ($location, $confno))
    {
      $location = str_replace ('US conferencing system',
			       "US conferencing system ($confno)", $location);
      patch_gcal_event ($event['creator']['email'], $event['id'],
			array ('location' => $location));
    }

  $owner = $row['email'];
  $orig_bookId = $bookId;
  $fields = array ('confno' => $confno,
		   'clientId' => id_from_email ($db, $owner,
						$row['first_name'],
						$row['last_name']),
		   'dateReq' => canon_time ($event['created']),
		   'dateMod' => canon_time ($event['updated']),
		   'starttime' => canon_time ($event['start']),
		   'endtime' => canon_time ($event['end']),
		   'maxUser' => isset ($maxUser) ? $maxUser : 0,
		   'status' => 'A', 'confOwner' => $owner,
		   'confDesc' => $event['summary'],
		   'adminopts' => isset ($adminopts) ? $adminopts: 'aAosTc',
		   'opts' => isset ($opts) ? $opts : 'osTc',
		   'remind' => isset ($remind) ? $remind : 'm',
		   'emailopts' => isset ($emailopts) ? $emailopts : 'sl',
		   'gcal_id' => $event['id']);

  if (isset ($event['description']) && $event['description'])
    {
      $fields['emailText'] = $event['description'];
      $fields['emailopts'] .= 't';
    }
  else
    $fields['emailopts'] = str_replace ('t', '', $fields['emailopts']);

  foreach ($fields as $name=>&$value)
    if (!is_numeric ($value))
      $value = $db->quoteSmart ($value);

  if (!$bookId)
    $query = ("INSERT INTO booking (" . implode (',', array_keys ($fields))
	      . ") VALUES (" . implode (',', array_values ($fields)). ")");
  else
    {
      $sets = array ();
      foreach ($fields as $fld=>$val)
	$sets[] = "$fld=$val";

      $query = ("UPDATE booking SET " . implode (',', $sets)
		. " WHERE bookId=$bookId");
    }
		
  $db->query ($query);
  if (!$bookId)
    {
      $query = "SELECT bookId FROM booking WHERE gcal_id='$event[id]'";
      $row = $db->query ($query)->fetchRow (DB_FETCHMODE_ASSOC);
      $bookId = $row['bookId'];
    }

  $participants = array ();
  $newemails = array ();
  foreach ($event["attendees"] as $attendee)
    {
      $grows = gcal_to_rows ($cnx, $db, $attendee);
      if (!$grows)
	continue;

      foreach ($grows as $grow)
        $participants[] = id_from_email ($db, $grow['email'],
				         $grow['first_name'],
				         $grow['last_name']);

      // Here $row is always set. If this is a new booking, it
      // is the row return by the SELECT query for the newly
      // inserted bookId, else this is still the row for the
      // conference organizer. This looks very dubious since
      // in any case we make no reference to the extracted
      // fields???

      if ($row)
      	extract ($row);
      else if ($orig_bookId)
        // Never reached, see above???
	$newemails[$attendee['email']] = true;
    }

  $db->query ("DELETE FROM participants WHERE book_id=$bookId");
  foreach ($participants as $id)
    $db->query ('INSERT INTO participants (user_id,book_id) '
		. " VALUES ($id,$bookId)");

  // count($newemails) is always 0???
  if (!$orig_bookId || count ($newemails))
    send_email ($db, $bookId, false, false, true, false, $orig_bookId,
		1, 0, false, $orig_bookId ? $newemails : false);
}

/* Look for conferences that have been deleted in Google Calendar.  */
$query = 'SELECT gcal_id, bookId FROM booking WHERE gcal_id IS NOT NULL ';
$query .= 'AND STARTTIME > NOW()';
$result = $db->query ($query);
while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
  {
    $event = gcal_get_event ($row['gcal_id']);
    if (isset ($event['status']) && $event['status'] == 'cancelled')
      delete_conf ($db, $row['bookId'], false);
  }

unlink ('/tmp/googleCalSync');
?>
