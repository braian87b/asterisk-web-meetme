<?php

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';
include 'locale.php';

session_start ();
if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

include ('lib/header_vars.php');

$clientId = $_SESSION['clientid'];
getpost_ifset (array ('confno', 'pin', 'adminpin', 'confOwner', 'confDesc',
		      'starttime', 'Duration', 'maxUser', 'add',
		      'bookId', 'update', 'recur', 'recurLbl', 'recurPrd',
		      'confopts', 'opts', 'updateSeries', 'Extend', 'fname',
		      'lname', 'email', 'phone', 'pass', 'delete',
		      'remind', 'emailopts', 'emailText', 'gcalId'));

// The variable FG_TABLE_NAME define the table name to use
$FG_TABLE_NAME = DB_TABLESCHED;

$f = fopen ('/tmp/ca', 'w');
fwrite ($f, print_r ($_POST, true));
fclose ($f);

if (isset ($add))
  {
    $dowork = true;
    list ($ConfHour, $ConfMin) = explode (':', $Duration);
    $tmpTime = (strtotime ($starttime) + (3600 * $ConfHour) + (60 * $ConfMin));
    $tmpTime = $tmpTime - ($tmpTime % 60);
    $endtime = date ('Y-m-d H:i:s', $tmpTime);
    $dateReq = date ('Y-m-d H:i:s');
    if (strtotime ($starttime) < time () - get_tz_offset ())
      $error = "Can't schedule a conference in the past.";
    else
      {
	if (intval ($confno) == 0)
	  $error = 'Conference number must be numeric.';

	if (!isset ($remind))
	  $remind = '';
	else if (is_array ($remind))
	  $remind = arraytostring ($remind);

	if (!isset ($confopts))
	  $confopts = '';
	else if (is_array ($confopts))
	  $confopts = arraytostring ($confopts);

	if (!isset ($opts))
	  $opts = '';
	else if (is_array ($opts))
	  $opts = arraytostring ($opts);

	if (!isset ($emailopts))
	  $emailopts = '';
	else if (is_array ($emailopts))
	  $emailopts = arraytostring ($emailopts);

	if (!strchr ($emailopts, 't') || !isset ($emailText))
	  $emailText = '';

	$adminopts = SAFLAGS . $confopts;
	$opts = SUFLAGS . $confopts . $opts;

	if ($pin && !intval ($pin))
	  $error = 'Conference PINs must be all numeric.';
	else if ($adminpin && !intval ($adminpin))
	  $error = 'Conference PINs must be all numeric.';
	else if (intval ($adminpin)
		 && (intval ($pin) == intval ($adminpin)))
	  $error = 'Moderator and user PINs must not be equal.';
	else if (!$adminpin && strchr ($opts, 'w'))
	  $error = "Moderator PIN required if 'Wait for Leader is set'.";

	if (intval ($maxUser) && intval ($maxUser) < 2)
	  $error = 'You must reserve at least 2 seats in this conference.';

	$stemp = strtotime ($starttime) - get_tz_offset ();
	$etemp = strtotime ($endtime) - get_tz_offset ();
	$tstarttime = date ('Y-m-d H:i:s', $stemp);
	$tendtime = date ('Y-m-d H:i:s', $etemp);
	$FG_TABLE_CLAUSE="confno='$confno' AND ((starttime<='$tstarttime' AND endtime>='$tstarttime') OR (starttime<='$tstarttime' AND endtime>='$tendtime') OR (starttime>='$tstarttime' AND endtime<='$tendtime') OR (starttime<='$tendtime' AND endtime>='$endtime'))";

	// Placeholder for original Start and End times.
	$st = $starttime;
	$et = $endtime;

	// Look for another conference with the same id at the same time.
	if (isset ($recur) && intval ($recur))
	  {
	    for ($i = 0; $i < count ($recurLabel); $i++)
	      if ($recurLbl == $recurLabel[$i])
		$recurInt = intval ($recurInterval[$i]);

	    for ($i = 0; $i < intval ($recurPrd); $i++)
	      {
		$FG_TABLE_CLAUSE="confno='$confno' AND ((starttime<='$tstarttime' AND endtime>='$tstarttime') OR (starttime<='$tstarttime' AND endtime>='$tendtime') OR (starttime>='$tstarttime' AND endtime<='$tendtime') OR (starttime<='$tendtime' AND endtime>='$tendtime'))";
		$ctemp = $db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE");
		$conflict += intval ($ctemp);
		$stemp += $recurInt;
		$etemp += $recurInt;
                $tstarttime = date ('Y-m-d H:i:s', $stemp);
                $tendtime = date ('Y-m-d H:i:s', $etemp);
	      }
	  }
	else
	  {
	    $ctemp = $db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE");
	    $conflict = intval ($ctemp);
	    $recurPrd = 1;
	  }

	// If the conference is unique, we can insert it.
	if (!isset ($error))
	  {
	    if ($conflict != 0)
	      $error = 'Conference is not unique.  Specify a different start time or ID.';
	    else
	      {
		$stemp = strtotime ($st) - get_tz_offset ();
		$etemp = strtotime ($et) - get_tz_offset ();
		$starttime = date ('Y-m-d H:i:s', $stemp);
		$endtime = date ('Y-m-d H:i:s', $etemp);
		$status = 'A';
		$dateMod = getConfDate ();
		$startHour = substr ($starttime, 10, 18); 
		$endHour = substr ($endtime, 10, 18); 

		if (!isset ($recurInt))
		  $recurInt = 0;
	
		for ($i = 0; $i < intval ($recurPrd); $i++)
		  {
		    $param_columns = '';
		    $param_update = '';
		    if ($clientId)
		      {
			$param_columns = 'clientId,';
			$param_update = "'$clientId',";
		      }

		    $emailText = $db->quoteSmart ($emailText);
		    $param_columns .= 'confno,pin,adminpin,starttime,endtime,dateReq,dateMod,maxUser,status,confOwner,confDesc,adminopts,opts,remind,sequenceNo,recurInterval,emailopts,emailText'; 
		    $param_update .= "'$confno','$pin','$adminpin','$starttime','$endtime','$dateReq','$dateMod','$maxUser','$status','$confOwner','$confDesc','$adminopts','$opts','$remind','$i','$recurInt','$emailopts',$emailText"; 
		    if (isset ($gcalId))
		      {
			$param_columns .= ',gcal_id';
			$param_update .= ",'$gcalId'";
		      }

		    $query = "INSERT INTO $FG_TABLE_NAME($param_columns) VALUES ($param_update)";
		    $result = $db->query ($query);
		    $stemp += $recurInt;
		    $etemp += $recurInt;
		    $starttime = date ('Y-m-d ', $stemp) . $startHour;
		    $endtime = date ('Y-m-d ', $etemp) . $endHour;
		    $query = 'SELECT max(bookId) AS mbid FROM booking';
		    $result = $db->query ($query);
		    $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
		    $bookId = $row['mbid'];
		    $query = "SELECT confno FROM booking WHERE bookId='$bookId'";
		    $result = $db->query ($query);
		    $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
		    if ($row['confno'] != $confno)
		      {
			echo "Bogus INSERT!!\n";
			exit;
		      }

		    if ($i == 0)
		      $em_bookId = $bookId;

		    $invitees = !isset ($email) ? 0 : count ($email);
		    for ($j = 0; $j < $invitees; $j++)
		      {
			if (trim ($email[$j]))
			  {
			    $query = "SELECT id, last_name, first_name, password FROM user WHERE email =? ";
			    $data = array ($email[$j]);
			    $result = $db->query ($query, $data);
			    if($result->numRows ())
			      {
				$row = $result->fetchRow (DB_FETCHMODE_ASSOC);
				$puid = $row['id'];

				if (!$row['password']
				    || ($row['password'] == 'NIS'
					&& $row['last_name'] == ''
					&& $row['first_name'] == ''))
				  {
				    if ($lname[$j])
				      $lname[$j] = addslashes ($lname[$j]);
				    if ($fname[$j])
				      $fname[$j] = addslashes ($fname[$j]);

				    if ($row['last_name'] != $lname[$j])
				      {
					$query = "UPDATE user SET last_name = '".$lname[$j]."' WHERE id=".$puid;
					$result = $db->query ($query);
				      }

				    if ($row['first_name'] != $fname[$j])
				      {
					$query = "UPDATE user SET first_name = '".$fname[$j]."' WHERE id=".$puid;
					$result = $db->query ($query);
				      }

				    update_phones ($db, $email[$j], $puid,
						   array ($phone[$j]),
						   array ('default'));
				  }
			      }
			    else
			      {
				$query = "INSERT INTO user (first_name, last_name, email) VALUES ('$fname[$j]','$lname[$j]','$email[$j]')";
				$result = $db->query ($query);
				$query = "SELECT id FROM user WHERE email =? ";
				$data = array ($email[$j]);
				$result = $db->query ($query, $data);
				$row = $result->fetchRow (DB_FETCHMODE_ASSOC);
				$puid = $row['id'];
				update_phones ($db, $email[$j], $puid,
					       array ($phone[$j]),
					       array ('default'));
			      }

			    $query = "SELECT user_id FROM participants WHERE user_id =? AND book_id =?";
			    $data = array ($puid, $bookId);
			    $result = $db->query ($query, $data);
			    if(!$result->numRows ())
			      {
				$query = "INSERT INTO participants (user_id, book_id) VALUES ('$puid', '$bookId')";
				$result = $db->query ($query);
			      }
			  }
		      }
		
		    if (strchr ($confopts, 'r'))
		      {
			$recordingfilename = "/recordings/$confno-$bookId";
			$recordingformat = 'wav';
			$query = "UPDATE booking SET recordingfilename='$recordingfilename', recordingformat='$recordingformat' WHERE bookId=$bookId";
			$result = $db->query ($query);
		      }
		  }

		if (strchr ($emailopts, 's'))
		  $sent_email = send_email ($db, $em_bookId, '', false,
					    true, false, false, $recurPrd,
					    $recurInt);

		if (!isset ($gcalId))
		  add_gcal_event ($db, $em_bookId, $email, false, $recurPrd,
				  $recurInt);
	      }
	  }
      }
  }

if (isset ($update))
  {
    $dowork = true;
    $loopCount = 1;
    $em_bookId[0] = $bookId;

    $query = "SELECT confno,starttime,dateReq FROM booking WHERE bookId =?";
    $data = array ($bookId);
    $result = $db->query ($query, $data);
    $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
		
    $searchconfno = $row['confno'];

    // Only update future conferences.
    $searchTime = $row['starttime'];
    $dateReq = $row['dateReq'];

    $query = "SELECT bookId,starttime,sequenceNo,recurInterval FROM booking WHERE confno =? AND dateReq =? AND starttime >=? ORDER BY sequenceNo";
    $data = array ($searchconfno, $dateReq, $searchTime);
    $result = $db->query ($query, $data);
    $i = 0;

    while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
      {
	$em_bookId[$i] = $row['bookId'];
	$em_sT[$i] = $row['starttime'];
	$em_sqNo[$i] = intval ($row['sequenceNo']);
	$em_rIntv[$i++] = intval ($row['recurInterval']);
      }

    $recurInt = $em_rIntv[0];
    $recurPrd = intval ($result->numRows ());
    $loopCount = $recurPrd;
    list ($ConfHour, $ConfMin) = explode (':', $Duration);
    $tmpTime = strtotime ($starttime) + (3600 * $ConfHour) + (60 * $ConfMin);
    $tmpTime = $tmpTime - ($tmpTime % 60);
    $endtime = date ('Y-m-d H:i:s', $tmpTime);

    if (strtotime ($starttime) < time () - get_tz_offset ())
      $error = "Can't update a conference in the past.";
    else
      {
	if (intval ($confno) == 0)
	    $confno = mt_rand (1000000, 9999999);

	if (!isset ($remind))
	  $remind = '';
	else if (is_array ($remind))
	  $remind = arraytostring ($remind);

	if (!isset ($confopts))
	  $confopts = '';
        else if (is_array ($confopts))
	  $confopts = arraytostring ($confopts);

	if (!isset ($opts))
	  $opts = '';
        else if (is_array ($opts))
	  $opts = arraytostring ($opts);

	if (!isset ($emailopts))
	  $emailopts = '';
	else if (is_array ($emailopts))
	  $emailopts = arraytostring ($emailopts);

	if (!strchr ($emailopts, 't') || !isset ($emailText))
	  $emailText = '';

	if (!strchr ($emailopts, 's'))
	  $remind = '';


	$adminopts = SAFLAGS . $confopts;
	$opts = SUFLAGS . $confopts . $opts;

	if ($pin && !intval ($pin))
	  $error = 'Conference PINs must be all numeric.';
	else if ($adminpin && !intval($adminpin))
	  $error = 'Conference PINs must be all numeric.';
	else if (intval ($adminpin) && intval($pin) == intval ($adminpin))
	  $error = 'Moderator and user PINs must not be the same.';
	else if (!$adminpin && strchr($opts, 'w'))
	  $error = "Moderator PIN required if 'Wait for Leader' set.";

	if (intval ($maxUser) != 0 && intval ($maxUser) < 2)
	  $error = 'You must reserve at least 2 seats in this conference';

	$stemp = strtotime ($starttime) - get_tz_offset ();
	$etemp = strtotime ($endtime) - get_tz_offset ();
	$tstarttime = date ('Y-m-d H:i:s', $stemp);
	$tendtime = date ('Y-m-d H:i:s', $etemp);
	$FG_TABLE_CLAUSE="confno='$confno' AND bookId<>'$bookId' AND ((starttime<='$tstarttime' AND endtime>='$tstarttime') OR (starttime<='$tstarttime' AND endtime>='$tendtime') OR (starttime>='$tstarttime' AND endtime<='$tendtime') OR (starttime<='$tendtime' AND endtime>='$endtime'))";

        if (isset ($updateSeries) && intval ($updateSeries))
	  {
	    $stemp = strtotime ($starttime);
	    $etemp = strtotime ($endtime);
	    for ($i = 0; $i < $loopCount; $i++)
	      {
		$FG_TABLE_CLAUSE="confno='$confno' AND bookId<>'$bookId' AND ((starttime<='$tstarttime' AND endtime>='$tstarttime') OR (starttime<='$tstarttime' AND endtime>='$tendtime') OR (starttime>='$tstarttime' AND endtime<='$tendtime') OR (starttime<='$tendtime' AND endtime>='$tendtime'))";
		$ctemp = $db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE");
                $conflict += intval ($ctemp);
                $stemp = strtotime ($starttime) + ($em_sqNo[$i] * $recurInt);
                $etemp = strtotime ($endtime) + ($em_sqNo[$i] * $recurInt);
                $tstarttime = date ('Y-m-d H:i:s', $stemp);
                $tendtime= date ('Y-m-d H:i:s', $etemp);
	      }
	  }
	else
	  {
	    $conflict = $db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE");
	    $loopCount = 1;
	  }

	if (!isset ($error))
	  {
	    if (intval ($conflict) != 0)
	      $error = 'Conference is not unique.  Specify a different start time or ID.';
	    else
	      {
		$stemp = strtotime ($starttime) - get_tz_offset ();
		$etemp = strtotime ($endtime) - get_tz_offset ();
		$starttime = date ('Y-m-d H:i:s', $stemp);
		$endtime = date ('Y-m-d H:i:s', $etemp);
	        $startHour = substr ($starttime, 10, 18); 
	        $endHour = substr ($endtime, 10, 18); 

		for ($i = 0; $i < $loopCount; $i++)
		{
		  $stemp = (strtotime ($starttime)
			    + (($em_sqNo[$i] - $em_sqNo[0]) * $recurInt));
		  $etemp = (strtotime ($endtime) +
			    (($em_sqNo[$i] - $em_sqNo[0]) * $recurInt));
		  $starttime = date ('Y-m-d ', $stemp) . $startHour;
		  $endtime = date ('Y-m-d ', $etemp) . $endHour;
		  $FG_EDITION_CLAUSE = " bookId='$em_bookId[$i]' ";

		  $emailText = $db->quoteSmart ($emailText);
		  $param_update = "confno='$confno',pin='$pin',adminpin='$adminpin', starttime='$starttime', endtime='$endtime', maxUser='$maxUser', confOwner='$confOwner', confDesc='$confDesc', adminopts='$adminopts', opts='$opts', remind='$remind', emailopts='$emailopts', emailText=$emailText"; 
		  $query = "UPDATE $FG_TABLE_NAME SET $param_update WHERE $FG_EDITION_CLAUSE";
		  $result = $db->query ($query);

		  $invitees = count ($email);
		  $query = "DELETE FROM participants WHERE book_id =?";
		  $data = array ($bookId);
		  $result = $db->query ($query, $data);

		  for ($j = 0; $j < $invitees; $j++)
		    {
		      if (trim ($email[$j]))
			{
			  $query = "SELECT id, last_name, first_name, password  FROM user WHERE email =? ";
			  $data = array ($email[$j]);
			  $result = $db->query ($query, $data);
			  if ($result->numRows ())
			    {
			      $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
			      $puid = $row['id'];
			      if (!$row['password'])
				{
				  if ($lname[$j])
				    $lname[$j] = addslashes ($lname[$j]);
				  if ($fname[$j])
				    $fname[$j] = addslashes ($fname[$j]);
						    
				  if ($row['last_name'] != $lname[$j])
				    {
				      $query = "UPDATE user SET last_name = '".$lname[$j]."' WHERE id=".$puid;
				      $result = $db->query ($query);
				    }
				  if ($row['first_name'] != $fname[$j])
				    {
				      $query = "UPDATE user SET first_name = '".$fname[$j]."' WHERE id=".$puid;
				      $result = $db->query ($query);
				    }
				  
				    update_phones ($db, $email[$j], $puid,
						   array ($phone[$j]),
						   array ('default'));
				}
			    }
			  else
			    {
			      $query = "INSERT INTO user (first_name, last_name, email) VALUES ('$fname[$j]','$lname[$j]','$email[$j]')";
			      $result = $db->query ($query);
			      $query = "SELECT id FROM user WHERE email =?";
			      $data = array ($email[$j]);
			      $result = $db->query ($query, $data);
			      $row = $result->fetchRow (DB_FETCHMODE_ASSOC);
			      $puid = $row['id'];
			      update_phones ($db, $email[$j], $puid,
					     array ($phone[$j]),
					     array ('default'));
			    }

			  $query = "SELECT user_id FROM participants WHERE user_id =? AND book_id =?";
			  $data = array ($puid, $bookId);
			  $result = $db->query ($query, $data);
			  if(!$result->numRows ())
			    {
			      $query = "INSERT INTO participants (user_id, book_id) VALUES ('$puid', '$bookId')";
			      $result = $db->query ($query);
			    }
			}
		    }

		  if (strchr ($confopts, 'r'))
		    {
		      $recordingfilename = "/recordings/$confno-$bookId";
		      $recordingformat = 'wav';
		      $query = "UPDATE booking SET recordingfilename='$recordingfilename', recordingformat='$recordingformat' WHERE bookId=$bookId";
		      $result = $db->query ($query);
		    }
		} 

		if (strchr ($emailopts, 's'))
		  $sent_email = send_email ($db, $em_bookId[0], '', false,
					    true, false, true, $loopCount,
					    $recurInt);

		add_gcal_event ($db, $em_bookId[0], $email, true,
				$loopCount, $recurInt);
	      }
	  }
      }
  }

if (isset ($Extend) && $Extend)
  {
    $now = getConfDate ();

    $FG_TABLE_CLAUSE = "confno='$confno'AND starttime<='$now' AND endtime>='$now'";
    $FG_COL_QUERY = 'bookId, endtime';
    $query = "SELECT $FG_COL_QUERY FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE";
    $result = $db->query ($query);
    
    $recordset = $result->fetchRow (DB_FETCHMODE_ASSOC);
    $bookId = $recordset['bookId'];
    $endtime = $recordset['endtime'];

    $FG_EDITION_CLAUSE =" bookId='$bookId' ";

    $tmpTime = strtotime ($endtime) + 900;
    $endtime = date ('Y-m-d H:i:s', $tmpTime);
    $param_update = "endtime='$endtime'";
    $query = "UPDATE $FG_TABLE_NAME SET $param_update WHERE $FG_EDITION_CLAUSE";
    $result = $db->query ($query);
    exit;
  }

if (isset ($delete))
  {
    $dowork = 1;
    delete_conf ($db, $bookId);
    $invitees = 0;
  }

if (isset ($error))
  $message = $error;
else if (isset ($add))
  $message = "Conference Scheduled";
else if (isset ($update))
  $message = "Conference Updated";
else
  $message = "Conference Deleted";
?>
<script language="JavaScript" type="text/JavaScript">
$(function () {
  processResult ("<?php echo $message ?>",
		 <?php echo (isset ($error) ? 1 : 0) ?>,
		 '<?php echo $conf_sel ?>',
		 '<?php if (!isset ($delete)) echo $conf_future_sel ?>',
		 <?php echo (isset ($sent_email) && $sent_email ? 1 : 0) ?>);
  setupUI ($('#conf_add_response'));
});
</script>
<?php
if (isset ($sent_email) && $sent_email) { ?>
<font face="arial" size="2"><b></b></font>
<p>The following message has been sent to all invited participants:</p>
<pre style="font-size: 1.2em; font-family:'Lucida Console'"><?php echo $sent_email; ?></pre>
<?php } ?>
