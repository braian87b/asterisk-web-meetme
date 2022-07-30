<?php
require_once 'gcal_interface.php';

function getpost_ifset ($test_vars)
{
  if (!is_array ($test_vars))
    $test_vars = array ($test_vars);

  foreach ($test_vars as $test_var)
    {
      if (isset ($_POST[$test_var]))
	{
	  global $$test_var;
	  $$test_var = $_POST[$test_var]; 
	}
      elseif (isset ($_GET[$test_var]))
	{
	  global $$test_var; 
	  $$test_var = $_GET[$test_var];
	}
    }
}

/* Convert time into a canonical form.  We can either be passed a string
   or an array component of a Google Event, which can have either a field
   'dateTime' or 'date'.  */

function canon_time ($time)
{
  if (is_array ($time))
    $time = isset ($time['dateTime']) ? $time['dateTime'] : $time['date'];

  return date ('Y-m-d H:i:s', strtotime ($time));
}

function getConfDate ($time = NULL)
{
  if ($time == NULL)
    $time = time ();

  $date = getDate ($time - get_tz_offset ());
  foreach ($date as $item => $value)
    if ($value < 10)
      $date[$item] = "0$value";

  return "$date[year]-$date[mon]-$date[mday] $date[hours]:$date[minutes]:00";
}

function getNextHour ()
{
  $date = getDate ();

  if ($date['minutes'] > 45 && $date['hours'] < 22)
    $date['hours']++;

  if ($date['hours'] < 23)
    $date['hours']++;
  $date['minutes'] = 0;

  foreach($date as $item => $value)
    if ($value < 10)
      $date[$item] = "0$value";

  return "$date[year]-$date[mon]-$date[mday] $date[hours]:$date[minutes]:00";
}

function display_date ($time)
{
  if ($time == '------')
    return '------';

  return str_replace (' ', '&nbsp;',
		      str_replace (' 0', '  ',
				   date ('M d, Y h:i a',
					 strtotime ($time)
					 + get_tz_offset ())));
}

function delete_conf ($db, $bookId, $deleteGcal = true)
{
  $data = array ($bookId);
  $query = "SELECT recordingfilename, recordingformat, gcal_id, confOwner, emailopts, endtime FROM booking WHERE bookId=?";
  $result = $db->query ($query, $data);
  $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
  if ($row['recordingfilename'])
    unlink ("$row[recordingfilename].$row[recordingformat]");

  if ($row['gcal_id'] && $deleteGcal)
    delete_gcal_event ($db, $row['gcal_id'], $row['confOwner']);

  if (strchr ($row['emailopts'], 's') && strtotime ($row['endtime']) > time ())
    send_email ($db, $bookId, '', false, true, true);

  // Delete the Conference.
  $query = "DELETE FROM booking WHERE bookId =?";
  $result = $db->query ($query, $data);

  // Delete the CDR records for this conference.
  $query = "DELETE FROM cdr WHERE bookId =?";
  $result = $db->query ($query, $data);

  // Delete the list of participants.
  if (defined ('AUTH_TYPE') && AUTH_TYPE == 'sqldb')
    {
      $query = "DELETE FROM participants WHERE book_id =?";
      $result = $db->query ($query, $data);
    }
}

function arraytostring ($array)
{
  $arraystring = '';

  foreach ($array as $item => $value)
    $arraystring .= "$value";

  return $arraystring;
}

function checkEmail ($email)
{
  if (preg_match ('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i', $email))
    return true;

  return false;
}

class userSec {
	function authenticate ($user,$password)
	{
	  switch (AUTH_TYPE)
	    {
	    case "adLDAP":
	      $adldap = new adLDAP ();

	      if ($adldap -> authenticate ($user, $password))
		{
		  $expires = time () + AUTH_TIMEOUT * 3600;
		  $_SESSION['userid'] = $user;
		  $_SESSION['auth'] = "true";
		  $_SESSION['privilege'] = "User";
		  $_SESSION['lifetime'] = $expires;
		  if ($adldap -> user_ingroup ($user, ADMIN_GROUP))
		    $_SESSION['privilege'] = "Admin";
		}
	      break;

	    case "sqldb":
	      if ($uid = authsql ($user,$password))
		{
		  $expires = time () + AUTH_TIMEOUT * 3600;
		  $_SESSION['userid'] = $user;
		  $_SESSION['auth'] = "true";
		  $_SESSION['lifetime'] = $expires;
		  $_SESSION['clientid'] = $uid;
		  unset ($_SESSION['failure']);
		}
	      else
		$_SESSION['failure'] = 1;
	      break;
	    }
        }

        function isAdmin($user)
	{
	  switch (AUTH_TYPE)
	    {
	    case "adLDAP":
	      break;
	      
	    }
        }
}

function get_tz_human ()
{
  $short = date ('T');
  $arr = explode ('/', timezone_name_from_abbr ($short));
  if (isset ($arr[1]))
    {
      $new = str_replace ('_', ' ', $arr[1]);
      return "$new: $short";
    }
  else
    return "$short time";
}

function callout ($confno, $name, $num, $pin, $async = false)
{
  syslog (LOG_NOTICE, "Calling $name at $num for $confno");

  $as = new AGI_AsteriskManager ();
  $res = $as->connect ();
  if (!$res)
    return array ('Response' => 'Error', 'Message' => "Can't connect to AMI");

  $priority = 1;
  $context = OUT_CONTEXT;
  $callerid = OUT_CALL_CID;

  $exten = $confno;
  $num = pack_phone ($num);
  if (strlen ($num) > 5 && is_numeric ($num))
    $num = "+$num";
  else
    $callerid = OUT_CALL_CID_EXT;
    
  $variable = "CID=$num,CNAM=$name";
  if ($pin)
    $variable .= ",PIN=$pin";

  if (CHAN_TYPE == "Local")
    $channel = CHAN_TYPE . "/$num@Conf_Invite";
  else
    $channel = "OOH323/$num@" . OUT_PEER;

  $res = $as->Originate ($channel, $exten, $context, $priority, NULL, NULL,
			 NULL, $callerid, $variable, NULL,
			 $async ? true : NULL);

  $as->disconnect ();
  return $res;
}

function merge_two ($c1, $c2)
{
  if ($c1 && $c2)
    return "$c1 AND $c2";
  else if ($c1)
    return $c1;
  else if ($c2)
    return $c2;
  else
    return '';
}

function merge_where_clauses ($c1, $c2, $c3 = '', $c4 = '')
{
  $result = merge_two ($c1, merge_two ($c2, merge_two ($c3, $c4)));
  if ($result)
    $result = "WHERE $result";

  return $result;
}

function make_callout_urls ($db, $email, $bookId, $uid, $admin)
{
  $phones = enumerate_phones ($db, $email, $uid);
  $result = '';
  foreach ($phones as $phone_ent)
    {
      $phone = $phone_ent[1];
      $idx = $phone_ent[2];

      if (is_numeric ($phone) && strlen ($phone) < 6)
	$phone = "Ext $phone";
      else
	$phone = expand_phone ($phone);

      $result
	.= "To be called at $phone and added to the conference, click:\n    ";
      $result
	.= 'http://' . HOST_FOR_URL . "/call.php?b=$bookId&u=$uid&i=$idx&h=";
      $result .= substr (sha1 ("$bookId:$uid:$idx:callPW"), 0, 4);
      if ($admin)
	$result .= '&a=1';

      $result .= "\n";
    }

  return $result;
}

function send_email ($db, $bookId, $emailText = '', $remind = false,
		     $cal = true, $delete = false, $update = false,
		     $recurNum = 1, $recurInt = 0, $list = false,
		     $selectedEmails = false)
{
  global $dialin_numbers;

  $action = $remind ? 'Reminder' : ($delete ? 'Cancellation' : 'Conference');

  $query = 'SELECT b.confDesc, b.confOwner, b.confno, b.pin, b.adminpin, ';
  $query .= 'b.starttime, b.endtime, b.adminopts, b.maxUser, b.cal_uid, ';
  $query .= 'b.emailopts, b.gcal_id, u.first_name AS ofn, ';
  $query .= 'u.last_name AS oln, u.email AS oem, u.id as uid ';
  $query .= 'FROM booking b, user u ';
  $query .= "WHERE bookId = '$bookId' AND b.clientId = u.id";
  extract ($db->query ($query)->fetchRow (DB_FETCHMODE_ASSOC));

  if (!strchr ($emailopts, 's'))
    return '';

  if (!strchr ($emailopts, 'c'))
    $cal = false;
  if (strchr ($emailopts, 'l'))
    $list = true;

  if (!strchr ($emailopts, 't'))
    $emailText = '';
  else if (!$emailText)
    {
      $query = "SELECT emailText FROM booking WHERE bookId = '$bookId'";
      extract ($db->query ($query)->fetchRow (DB_FETCHMODE_ASSOC));
    }

  if ($confOwner != $oem)
    {
      $query = "SELECT last_name, first_name, id AS uid FROM user WHERE email = '$confOwner'";
      $result = $db->query ($query);
      $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
      if (isset ($row['last_name']))
	{
	  extract ($row);
	  $oem = $confOwner;
	}
    }

  $starttime = strtotime ($starttime) + get_tz_offset ();
  $endtime = strtotime ($endtime) + get_tz_offset ();
  $duration = $endtime - $starttime;
  $hours = floor ($duration / 3600);
  $minutes = floor (($duration % 3600) / 60);

  if ($hours == 1)
    $durString = '1 hour';
  else if ($hours)
    $durString = "$hours hours";
  else
    $durString = '';

  if ($minutes)
    {
      if ($hours)
	$durString .= ' and ';

      $durString .= "$minutes minutes";
    }

  $shortstarttime = date ('M d, Y h:i A T', $starttime);
  $fullstarttime = date ('l, M d, Y h:i A T \(\G\M\TP\)', $starttime);

  if (isset ($adminpin) && $adminpin)
    $admpwline = _("Admin PIN").":  $adminpin\n";

  $headers = "From: $oem\n";
  $headers .= 'X-AdaCore-Conference-System: ';
  $headers .= ($remind ? 'reminder'
	       : ($delete ? 'cancellation'
		  : ($update ? 'update' : 'invitation')));
  $headers .= "\n";

  $msg_body = '';
  if ($remind)
    $msg_body .= "This is a reminder that you were invited to a conference";
  else if ($delete)
    $msg_body .= "$ofn $oln has cancelled the conference";
  else if ($update)
    $msg_body .= "$ofn $oln has updated the conference";
  else
    $msg_body .= "$ofn $oln has invited you to a conference";

  if ($emailText)
    {
      $msg_body
	.= " on $shortstarttime\nabout '$confDesc'. Details are below.\n\n";
      $msg_body .= $emailText . "\n--------------------------------\n\n";
    }
  else
    $msg_body .= ":\n\n";

  $msg_body .= "Topic: $confDesc\n";
  $msg_body .= _('Time') . ": $fullstarttime\n";
  $msg_body .= _('Duration') . ": $durString\n";
  if (isset ($maxUser) && $maxUser)
    $msg_body .= _('Max Participants') . ": $maxUser\n";

  if (!$delete)
    {
      $msg_body
	.= "\nTo join the conference, dial any of the following:\n";
      foreach ($dialin_numbers as $type=>$num)
	$msg_body .= "    $num ($type)\n";

      if (isset ($pin) && $pin)
	$msg_body
	  .= "\nYou may be prompted for the conference and pin numbers.\n";
      else
	$msg_body
	  .= "\nYou may be prompted for the conference number.\n";
      
      
      $msg_body .= "    Conference Number: $confno\n";
      if (isset ($adminpin) && $adminpin)
	$msg_body .= $admpwline;

      if (isset ($pin) && $pin)
	$msg_body .= "    Conference PIN: $pin\n";

      $msg_body .= "\n";

      if ($recurNum > 1)
	{
	  $msg_body .= "This conference will also occur at:\n";
	  for ($i = 1; $i < $recurNum; $i++)
	    {
	      $newstart = $starttime + ($i * $recurInt);
	      $fullstarttime = date ('l, M d, Y h:i A T \(\G\M\TP\)',
				       $newstart);

	      $msg_body .= "\t$fullstarttime\n";
	    }

	  $msg_body .= "\nFor the same duration and with the same call-in ";
	  $msg_body .= "and conference numbers.\n\n";
	}

      $call_lines = make_callout_urls ($db, $oem, $bookId, $uid, 1);

      if (!$call_lines)
	$call_lines = "[Placeholder for lines to call user]\n";

      $msg_body .= "\n$call_lines\n";

      if ($list)
	{
	  $first = true;
	  $query = "SELECT u.first_name, u.last_name, u.email, p.id FROM user u, participants p WHERE u.id = p.user_id AND p.book_id = '$bookId'";
	  $result = $db->query ($query);
	  while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
	    {
	      if ($first)
		{
		  $first = false;
		  $msg_body .= "Participants are:\n";
		}

	      extract ($row);
	      if ($first_name)
		$msg_body .= "\t$first_name $last_name ($email)";
	      else
		$msg_body .= "\t$email";

	      if ($email == $oem)
		$msg_body .= ', Organizer';

	      $msg_body .= "\n";
	    }
	}
    }

  if (!$delete && strchr ($adminopts, "r"))
    {
      $msg_body .= "\nThis conference will be recorded. ";
      $msg_body .= "After the conference is complete,\n";
      $msg_body .= "you may listen to the recording at:\n";
      $msg_body .= "\thttp://" . HOST_FOR_URL;
      $msg_body .= "/play.php?confno=$confno&bookid=$bookId";

      if (isset ($pin) && $pin)
	$msg_body .= "&pin=$pin";
    }

  $msg_body .= "\n";

  if ($cal)
    {
      if (!$cal_uid)
	{
	  $cal_uid
	    = (date ('Ymd') . 'T' . date ('His') . '-' . rand ()
	       . '@adacore.com');
	  $query
	    = "UPDATE booking SET cal_uid='$cal_uid' WHERE bookId='$bookId'";
	  $result = $db->query ($query);
	}

      $mime_boundary = '----Meeting Booking----' . md5 (time ());
      $headers .= "MIME-Version: 1.0\n";
      $headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
      $headers .= "Content-class: urn:content-classes:calendarmessage\n";

      $msg_prefix = "\n";
      $msg_prefix .= "--$mime_boundary\n";
      $msg_prefix .= "Content-Type: text/plain; charset=UTF-8\n";
      $msg_prefix .= "Content-Transfer-Encoding: 8bit\n\n";

      if (strlen ($emailText))
	$icalDesc = split_text ("DESCRIPTION:$confDesc\n$emailText");
      else
	$icalDesc = "DESCRIPTION:$confDesc\n";

      $ical = "BEGIN:VCALENDAR\n";
      $ical .= "PRODID:-//Microsoft Corporation//Outlook 11.0 MIMEDIR//EN\n";
      $ical .= "VERSION:2.0\n";
	  
      if ($delete)
	$ical .= "METHOD:CANCEL\n";
      else
	$ical .= "METHOD:PUBLISH\n";

      for ($i = 0; $i < $recurNum; $i++)
	{
	  $st = $starttime + ($i * $recurInt);

	  $ical .= "BEGIN:VEVENT\n";
	  $ical .= "ORGANIZER;CN=$ofn $oln:MAILTO:$oem\n";

	  $query = "SELECT u.first_name, u.last_name, u.email, p.id FROM user u, participants p WHERE u.id = p.user_id AND p.book_id = '$bookId'";
	  $result = $db->query ($query);
	  while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
	    {
	      extract ($row);
	      $ical .= "ATTENDEE;CN=$first_name $last_name:MAILTO:$email\n";
	    }

	  $ical .= 'DTSTART:' . gmdate ('Ymd\THis\Z', $st) . "\n";
	  $ical .= ('DTEND:'
		    . gmdate ('Ymd\THis\Z', $st + $duration) . "\n");
	  $ical .= "LOCATION: ${dialin_numbers['US direct']} $confno\n";
	  $ical .= "TRANSP:OPAQUE\n";
	  $ical .= "SEQUENCE:0\n";
	  $ical .= "UID:$cal_uid\n";
	  $ical .= 'DTSTAMP:' . gmdate ('Ymd\THis\Z') . "\n";
	  $ical .= $icalDesc;
	  $ical .= "SUMMARY:$confDesc\n";
	  $ical .= "PRIORITY:5\n";
	  if ($delete)
	    $ical .= "STATUS:CANCELLED\n";

	  $ical .= "CLASS:PUBLIC\n";
	  $ical .= "END:VEVENT\n";
	}

      $ical .= "END:VCALENDAR\n";

      $msg_suf = "--$mime_boundary\n";	
      $msg_suf .= "Content-Type: text/calendar;name=\"meeting.ics\";method=";
      if ($delete)
	$msg_suf .= "CANCEL\n";
      else
	$msg_suf .= "PUBLISH\n";

      $msg_suf .= "Content-Transfer-Encoding: 8bit\n\n"; 
      $msg_suf .= $ical;            
      $msg_suf .= "--$mime_boundary--\n";
    }
  else
    {
      $msg_prefix = '';
      $msg_suf = '';
    }

  $recipient = "\"$ofn $oln\" <$oem>";
  if (!is_array ($selectedEmails) || isset ($selectedEmails[$oem]))
    mail ($recipient, _('Leader') . " ($action): $confDesc ($shortstarttime)",
	  $msg_prefix . $msg_body . $msg_suf, $headers);

  if (!$delete && $adminpin)
    $msg_body = str_replace ($admpwline, '', $msg_body);

  $cnx = ldap_connect ('127.0.0.1');
  ldap_bind ($cnx);
  $emails = get_emails_in_conf ($cnx, $db, $bookId);
  ldap_unbind ($cnx);
  foreach ($emails as $uem=>$data)
    {
      $first_name = $data ? $data[0] : '';
      $last_name = $data ? $data[1] : '';
      $uid = $data ? $data[2] : 0;
      $new_calls = make_callout_urls ($db, $uem, $bookId, $uid, 0);

      if ($delete)
	$new_body = $msg_body;
      else
	$new_body = str_replace ($call_lines, $new_calls, $msg_body);

      if (!is_array ($selectedEmails) || isset ($selectedEmails[$uem]))
	mail ("\"$first_name $last_name\" <$uem>",
	      "$action: $confDesc ($shortstarttime)",
	      $msg_prefix . $new_body . $msg_suf, $headers);
    }

  return $msg_body;
}

function split_text ($in)
{
  $out = '';
  $i = 0;
  $pos = 0;
  $prefix = '';

  while ($i < strlen ($in))
    {
      for (; $i < strlen ($in) && substr ($in, $i, 1) == ' '; $i++)
	;

      for ($word = ''; $i < strlen ($in) && substr ($in, $i, 1) != "\n"
	     && substr ($in, $i, 1) != ' ';
	   $i++)
	$word .= substr ($in, $i, 1);

      if ($pos + strlen ($word) > 70)
	{
	  $out .= "\n";
	  $pos = 0;
	}

      $out .= "$prefix$word";
      $prefix = ' ';
      $pos += strlen ($word) + 1;
      if ($i < strlen ($in) && substr ($in, $i, 1) == "\n")
	{
	  $i++;
	  if ($i != strlen ($in))
	    {
	      $out .= '\n' . "\n";
	      $pos = 0;
	    }
	}
    }

  if (substr ($out, -1, 1) != "\n")
    $out .= "\n";

  return $out;
}

function get_tz_offset ()
{
  if (!isset ($_COOKIE['tz']))
    return 0;

  $origin_dtz = new DateTimeZone ($_COOKIE['tz']);
  $remote_dtz = new DateTimeZone (TIMEZONE);
  $origin_dt = new DateTime ("now", $origin_dtz);
  $remote_dt = new DateTime ("now", $remote_dtz);
  return ($origin_dtz->getOffset ($origin_dt)
	  - $remote_dtz->getOffset ($remote_dt));
}

/* Given a Google Calendar "attendee" or "originator" data, return "rows"
   consisting of email address, first and last name, and telephone.  */

function gcal_to_rows ($cnx, $db, $gdata)
{
  if ((isset ($gdata['self']) && $gdata['self'])
      || (isset ($gdata['resource']) && $gdata['resource']))
    return false;

  if (! $cnx)
    {
      $bound = 1;
      $cnx = ldap_connect ('127.0.0.1');
      ldap_bind ($cnx);
    }

  $members = expand_group($cnx, $gdata["email"]);

  // If address was not a group, and is has an associated displayName,
  // then it will be used as a default if we don't find it in LDAP.

  if (count($members) == 1 && $members[0] == $gdata["email"] && isset($gdata["displayName"])) {
    $default_name = explode (' ', $gdata['displayName']);
  } else {
    $default_name = array();
  }

  $rows = array();

  foreach ($members as $member) {
    $res = array ('email' => $member);
    $sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com',
		       "(|(mail=$res[email])(adacorealtemail=$res[email]))",
		       array ('mail', 'givenname', 'sn'));
    $info = ldap_get_entries ($cnx, $sr);
    if ($info['count'] == 1)
      {
        $res['first_name'] = $info[0]['givenname'][0];
        $res['last_name'] = $info[0]['sn'][0];
        $res['email'] = $info[0]['mail'][0];
      }
  
    if (!isset ($res['first_name']) && count ($default_name) > 1)
      $res['first_name'] = $default_name[0];
    if (!isset ($res['last_name']) && count ($default_name) > 1)
      $res['last_name'] = $default_name[count ($default_name) - 1];
  
    $query
      = "SELECT first_name,last_name,id FROM user WHERE email='$res[email]'";
    $result = $db->query ($query);
    while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
      {
        $phones = enumerate_phones ($db, $res['email'], $row['id']);
        if (count ($phones))
	  $res['telephone'] = expand_phone ($phones[0][1]);
  
        if (!isset ($res['first_name']))
	  $res['first_name'] = $row['first_name'];
        if (!isset ($res['last_name']))
	  $res['last_name'] = $row['last_name'];
      }
  
    foreach (array ('first_name', 'last_name', 'telephone') as $fld)
      if (!isset ($res[$fld]))
        $res[$fld] = '';
  
    if (isset ($bound))
      ldap_unbind ($cnx);
  
    $rows[] = $res;
  }
  return $rows;
}

/* Lookup email address, first name, and last name from DN */

function lookup_user($cnx, $dn) {
    $sr = ldap_search($cnx, $dn, "objectClass=*", array('mail', 'givenName', 'sn'));
    if (!$sr)
        return FALSE;

    // Create an array from the returned entry (this is
    // assumed to return exactly one).

    $res = ldap_get_entries($cnx, $sr);
    return array('mail'       => $res[0]['mail'][0],
                 'first_name' => $res[0]['givenname'][0],
                 'last_name'  => $res[0]['sn'][0]);
}

/* Map an email address to a user ID, creating one if it isn't already
  there.  */

function id_from_email ($db, $email, $first_name, $last_name)
{
  $result = $db->query ("SELECT id FROM user WHERE email='$email'");
  $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
  if ($row)
    return $row['id'];
  else
    {
      $db->query ("INSERT into user (email,first_name,last_name) values ('$email','$first_name','$last_name')");
      return id_from_email ($db, $email, $first_name, $last_name);
    }
}

/* If address denotes a Google Group, return array of members,
   else return array containing just the original address.
 */

function expand_group($cnx, $addr) {
  $res = array();

  $sr = ldap_search ($cnx, 'ou=GoogleGroups,dc=adacore,dc=com',
			   "(name=$addr)", array ('member'));
  if ($sr) {
    $info = ldap_get_entries ($cnx, $sr);
    for ($member_index = 0;
         $info['count'] &&
           $member_index < $info[0]['member']['count'];
         $member_index++)
    {
      $member_dn = $info[0]['member'][$member_index];
      $member_info = lookup_user($cnx, $member_dn);
      if ($member_info) {
	$res[] = $member_info['mail'];
      }
    }
  }

  if (!($sr && $info['count']))
    $res[] = $addr;

  return $res;
}

/* Return an array whose keys as the email addresses of users in the
   conference given by $bookId and whose values are a triple of
   first name, last name, and uid, expanding any email address
   that designates a Google Group into its individual members.
 */

function get_emails_in_conf ($cnx, $db, $bookId)
{
  $users = array ();

  $query = "SELECT u.first_name, u.last_name, u.email AS uem, u.id AS uid, p.id FROM user u, participants p WHERE u.id = p.user_id AND p.book_id = '$bookId'";
  $result = $db->query ($query);

  // Scan attendees list for names of Google Groups to expand

  while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
    {
      extract ($row);

      $sr = ldap_search ($cnx, 'ou=GoogleGroups,dc=adacore,dc=com',
			 "(name=$uem)", array ('member'));
      if ($sr) {
          $info = ldap_get_entries ($cnx, $sr);
      } else {
          $info = FALSE;
      }
      if ($info && $info["count"] > 0) {
	for ($member_index = 0; $member_index < $info[0]['member']['count']; $member_index++) {
	  $member_dn = $info[0]['member'][$member_index];
          $member_info = lookup_user($cnx, $member_dn);
	  if ($member_info) {
	    $users[$member_info['mail']] =
              array($member_info['first_name'],
                    $member_info['last_name'],
                    id_from_email($db,
                                  $member_info['mail'],
                                  $member_info['first_name'],
                                  $member_info['last_name']));
          }
        }
      } else {
        $users[$uem] = array ($first_name, $last_name, $uid);
      }
    }

  return $users;
}
?>
