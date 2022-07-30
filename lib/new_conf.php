<script>
setupNewConf ();
</script>
<!-- ** ** ** ** ** Part to add the conference ** ** ** ** ** -->
<?php
/* Write part of table for a participant.  */

function add_party ($email, $first_name, $last_name, $telephone, &$first,
		    $write = false)
{
  if ($first)
    {
      $first = 0;
      echo "<tr>";
      echo "<td><b><center>Email</center></b></td>";
      echo "<td><b><center>First Name</center></b></td>";
      echo "<td><b><center>Last Name</center></b></td>";
      echo "<td><b><center>Telephone</center></b></td>";
      echo "</tr>";
    }

  $ro = $write ? '' : ' readonly';
  echo "<tr>";
  echo "<td><input type=text name=email[] class='email' size=30 value=\"$email\" readonly></td>";
  echo "<td><input type=text name=fname[] class='first_name' size=9 value=\"$first_name\"$ro></td>";
  echo "<td><input type=text name=lname[] class='last name' size=11 value=\"$last_name\"$ro></td>";
  echo "<td><input type=text name=phone[] size=16 class='telephone' value=\"$telephone\"$ro></td>";
  echo "<td><span title=\"Delete this participant\"><input type=\"button\" value=\"Delete\" class=warn onclick=\"deleteCurrentRow(this)\"></span></td>";
  echo "</tr>";
}

getpost_ifset (array ('bookId', 'clone'));

if (!isset($bookId) || !is_numeric($bookId))
  $bookId = 0;
$orig_bookId = $bookId;

if ($bookId){
	$FG_COL_QUERY='confno, confDesc, starttime, endtime, dateReq, maxUser, bookId, pin, confOwner, adminpin, adminopts, opts, remind, emailopts, emailText, gcal_id';
	$result = $db->query("SELECT $FG_COL_QUERY FROM booking WHERE bookId='$bookId'");
	$recordset = $result->fetchRow(DB_FETCHMODE_ASSOC);
	$confno = $recordset['confno'];
	$confDesc = $recordset['confDesc'];

	if (!isset ($clone))
	  {
	    $starttime = $recordset['starttime'];
	    $endtime = $recordset['endtime'];
	    $bookId = $recordset['bookId'];
	    if ($recordset['gcal_id'])
	      $gcal_id = $recordset['gcal_id'];

	    echo '<h1>Change details about (or delete) this conference</h1>';
	  }
	else
	  {
	    $bookId = 0;
	    echo '<h1>Clone this conference</h1>';
	  }

	$dateReq = $recordset['dateReq'];
	$maxUser = $recordset['maxUser'];
	if ($recordset['pin'])
	  $pin = $recordset['pin'];
	$confOwner = $recordset['confOwner'];
	if ($recordset['adminpin'])
	    $adminpin = $recordset['adminpin'];
	$adminopts = $recordset['adminopts'];
	$opts = $recordset['opts'];
	$remind = $recordset['remind'];
	$emailopts = $recordset['emailopts'];
	$emailText = $recordset['emailText'];
}
else
{
	while (!isset ($confno) || !$confno || intval ($db->getOne ("SELECT COUNT(*) FROM booking WHERE confno='$confno'")))
	    $confno = mt_rand (1000000, 9999999);
	$adminopts = 'c';
	$remind = 'm';
	$emailopts = 's';
	if (isset ($emailText))
	  $emailopts .= 't';
	if (isset ($gcal_id))
	  $emailopts .= 'l';
}
?>
<div class="bar-status">
<FORM METHOD=POST NAME="WMAdd" id="WMAdd" onSubmit=" return onSubmitConf ()" >
<INPUT TYPE="hidden" NAME="s" ID="s" value="<?php echo $s?>">
<INPUT TYPE="hidden" NAME="t" ID="t" value="<?php echo $t?>">
<INPUT TYPE="hidden" NAME="current_page" value="<?php echo $current_page?>">
<table class="borders" align="center">
		<tbody>
		<tr>
		<td class="legend btl" title="Descriptive name of conference">Conference Name</td>
			<td class="bar-search btr">
			<SPAN title="Conference title (description)"><INPUT TYPE="text" NAME="confDesc" value="<?php if (isset($confDesc)) echo $confDesc; ?>" size=50></span></td>
		</tr><tr>
		<td class="legend" title="Email address of user creating conference">Owner</td>
			<td class="bar-search">
	<?php	if (! isset ($confOwner)) $confOwner = $_SESSION['userid']; ?>
			 <SPAN title="Email address of user creating conference"><INPUT TYPE="text" size=35 NAME="confOwner" ID="confOwner" value="<?php if (isset ($confOwner)) echo $confOwner; ?>" <?php if ($_SESSION['privilege'] == 'User') echo 'readonly'; ?>></span></td>
		</tr><tr>
		<td class="legend" title="Conference room number (normally accept the default)">Number</td>
			<td class="bar-search">
			<SPAN title="Conference room number (normally accept the default)"><INPUT TYPE="text" NAME="confno" value="<?php echo $confno; ?>" size=7><span></td>
		</tr><tr>
		<td class="legend" title="Options for entire conference">Options</td>
			<td class="bar-search">
			<TABLE class="options"><TBODY><TR>
                        <?php 
			for ($i=0; $i < count($Conf_Options); $i++){
				print "<TD>";
				if ($Conf_Options[$i][2])
			    		print ("<SPAN title=\"".$Conf_Options[$i][2]."\">");
				print "<INPUT CLASS='confOptions' TYPE=CHECKBOX NAME=confopts[] ";
				print "ID='CO_".$Conf_Options[$i][1]."' ";
				print "VALUE=\"".$Conf_Options[$i][1]."\"";
				if (strchr($adminopts, $Conf_Options[$i][1]))
					print " CHECKED";
  				print "><LABEL for='CO_".$Conf_Options[$i][1]."'>";
				print "<b>";
				print $Conf_Options[$i][0] . "</LABEL></b>";
				if ($Conf_Options[$i][2])
				    print ("</SPAN>");
				print "</TD>\n";
			}
			?>
			<TD><SPAN title="Conference has PINs for leader and participants">
                        <b>
			<INPUT TYPE=CHECKBOX NAME="pass" ID="pass" value = "1" onclick="togglePass(this);" <?php if (isset ($adminpin)) echo " CHECKED"; ?>>
			<label for="pass">PINs</label></b></SPAN></TD>
			<TD><SPAN title="Limit number of participants in conference">
			<b>
			<INPUT TYPE=CHECKBOX NAME="limited" ID="limited" value="1" onclick="toggleLimit(this);"  <?php if (isset($maxUser) && $maxUser!="0") echo " CHECKED" ?>>
			&nbsp;<LABEL id="limitLabel" for="limited">Limited<?php if (isset($maxUser) && $maxUser!="0") echo " to" ?></LABEL></b></SPAN>
			<SPAN id="MaxParticipantsRow" <?php if (!isset($maxUser) || $maxUser=="0") echo 'style="display:none"' ?>>
			<SPAN title="Maximum number of participants in conference"><INPUT TYPE="text" NAME="maxUser" id="maxUsers" id="maxUsers" value=<?php echo isset ($maxUser) ? $maxUser : 0; ?> size=3></span></span></td></tr>
		        <tr id="UserOptionsRow" <?php if (!isset ($adminpin)) echo 'style="display:none"' ?>>
                        <?php
			for ($i=0; $i < count($User_Options); $i++){
			  	print "<TD colspan=" . (strlen ($User_Options[$i][0]) > 12 ? "2" : "1") . ">";
				if ($User_Options[$i][2])
			    		print ("<SPAN title='".$User_Options[$i][2]."'>");
  				print "<b>";
				print "<INPUT TYPE=CHECKBOX NAME=opts[] ";
				print "ID='UO_".$User_Options[$i][1]."' ";
				print "VALUE='".$User_Options[$i][1]."'";
				if(isset($opts) && strchr($opts, $User_Options[$i][1]))
					print " CHECKED";
				print "><LABEL for='UO_".$User_Options[$i][1]."'>";
				print $User_Options[$i][0] . "</label></b>\n";
				if ($User_Options[$i][2])
					print ("</SPAN>");
				print "</TD>";
			} ?>
			</tr></tbody></table></td>
		</tr>
	        <tr id="PinRow" <?php if (!isset($adminpin)) echo 'style="display:none"' ?>>
		<td class="legend" title="Numeric passwords">PINs</td>
			<td class="bar-search">
			<b><SPAN title="Numeric password for leader"><INPUT TYPE="text" NAME="adminpin" ID="adminpin" size=4 value="<?php if (isset($adminpin)) echo $adminpin; ?>"></span>&nbsp;&nbsp;Leader&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<SPAN title="Numeric password for normal callers"><INPUT TYPE="text" NAME="pin" ID="pin" value="<?php if (isset($pin)) echo $pin; ?>" size=4></span>&nbsp;&nbsp;&nbsp;User</b></td>
		</tr>
		<tr>
		<td class="legend" title="Schedule for conference (in your local time)"><?php echo 'Schedule (' . get_tz_human () . ')'; ?>
		<br/><span title="Find time when all participants are available.">
		<button type="button" class="bstandard" onclick="$('#findTime').show ()" value="Find Time">Find Time</span></td>
			<td class="bar-search">
<?php if (isset($starttime)){
        $starttime=strtotime($starttime) + get_tz_offset ();
   } else {
	$starttime=strtotime(getNextHour());
} ?>
			<b>&nbsp;&nbsp;Start:&nbsp;&nbsp;</b>
			<span title="Starting time for the conference.">
			<input type="text" name="fmtStartTime" id="startTime" value="<?php echo date ('l, F j, Y g:i A', $starttime); ?>" size=40></span>
&nbsp&nbsp&nbsp
                        <input type="hidden" name="starttime" id="altTime" value="<?php echo date ('Y-m-d G:i', $starttime) ?>">
<?php if (isset($endtime)){
        $tmp= strtotime ($endtime) + get_tz_offset () - $starttime;
	$Min=(($tmp/60)%60);
	$Hour=((($tmp/60)-(($tmp/60)%60))/60);
	if ($Min < 10){
		$Min="0$Min";
	}
   } else {
	$Hour="1";
	$Min="00";
} ?>
		<b>Duration:&nbsp;&nbsp;</b>
		<SPAN Title="Duration of Conference">
		<INPUT name="Duration" ID="duration" size=4 value="<?php echo "$Hour:$Min" ?>"></span>
<?php if(!isset($endtime)){ ?>
			<SPAN title="Check if conference will repeat at a regular interval">&nbsp;&nbsp;
			<b>
			<INPUT TYPE=CHECKBOX  onclick="recurHide(this)" NAME="recur" ID="recur" VALUE="1">&nbsp;<LABEL for="recur">Recurs?</label></b></SPAN>
			<SPAN id="recurData" style="display:none"><br>
			 <b>&nbsp;&nbsp;Conference repeats&nbsp;&nbsp;</b>
                        <SPAN title="How often the conference is to repeat">
			 
			<SELECT NAME="recurLbl">
			    <?php for ($i=0; $i < count($recurLabel); $i++){ ?>
				<OPTION VALUE="<?php echo $recurLabel[$i]; ?>"><?php echo $recurLabel[$i]; ?>
			    <?php } ?>
			</select></span><b>&nbsp;&nbsp;for a total of&nbsp;&nbsp;</b>
			<SPAN title="How many times the conference is to repeat">
			<INPUT name="recurPrd" ID="recurPrd" TYPE="text" VALUE="2" size=3>
			 <b>&nbsp;&nbsp;times.</b></span></span>
<?php } ?>
			</td>
		</tr>
		<tr><td class="legend" title="Options for email about conference">Email</td>
		<td class="bar-search">
			<b>
			   <?php for ($i = 0; $i < count ($Email_Options); $i++) {
			      if ($i == 1)
				{
				  echo '<span id="EmailOptionsRow"';
				  if (!strchr ($emailopts, 's'))
				    echo 'style="display:none"';
				  echo '>';
				} ?>
		           <SPAN title="<?php echo $Email_Options[$i][2]?>">
			   <b>
			   <INPUT TYPE=CHECKBOX CLASS='emailOptions' NAME=emailopts[] VALUE="<?php echo $Email_Options[$i][1] ?>"
			   ID="EO_<?php echo $Email_Options[$i][1] ?>"
			   <?php if (isset ($Email_Options[$i][3])) { ?>
                               onClick="<?php echo $Email_Options[$i][3] ?>(this)"
			     <?php }
			   if (strchr ($emailopts, $Email_Options[$i][1]))
			     print " CHECKED"; ?>
			   >
			   &nbsp;<LABEL for="EO_<?php echo $Email_Options[$i][1] ?>">
			   <?php echo $Email_Options[$i][0] ?>
                           </label></b></SPAN>&nbsp;&nbsp;&nbsp;
 		       <?php } ?>
		       </span></td></tr>
		       <tr id="RemindOptionsRow" <?php if (!strchr ($emailopts, 's')) echo 'style="display:none"' ?>>
		       <td class="legend" title="When to send reminder email about conference">Remind</td>
		<td class="bar-search">
                        <?php 
			for ($i=0; $i < count($Remind_Options); $i++){
			    	print ("<SPAN title=\"Send reminder email ".$Remind_Options[$i][0]." in advance of the conference\">");
				print "<b>";
				print '<INPUT TYPE=CHECKBOX NAME=remind[] ';
				print 'ID="RO_' . $Remind_Options[$i][1] . '" ';
				print 'CLASS="remindOptions" VALUE="' . $Remind_Options[$i][1];
				if (!isset ($clone)
				    && strchr ($remind,
					       strtoupper ($Remind_Options[$i][1])))
				  print strtoupper ($Remind_Options[$i][1]);
				print '"';
				if (strchr($remind, $Remind_Options[$i][1]))
					print " CHECKED";
				print '>&nbsp;<LABEL for="RO_' . $Remind_Options[$i][1] . '">';
				print $Remind_Options[$i][0];
				print "</LABEL></b></SPAN>&nbsp;&nbsp;\n";
			}
			?>
                       </span></td>
		</tr>
		<tr id="EmailMessage"<?php if (!strchr ($emailopts,'t')) echo '  style="display:none"'?>>
  		<td class="legend" title="Text to be added to email sent to participants">Extra Email Text</td>
			<td class="bar-search">
			<textarea rows=5 cols=70 name="emailText"><?php
			if (isset ($emailText)) echo $emailText; ?></textarea>
                </td></tr>
<?php if (AUTH_TYPE == "sqldb"){ ?>
		<tr>
                <td class="legend" title="Add a participant to the conference">
<button type="button" class="bstandard" onclick="addEmailToTable('invite');">Add participant</td>
		<td class="bar-search">
	<table id="invite"><tbody>
<?php 
	if ($orig_bookId)
	  {
	    $query = "SELECT u.first_name, u.last_name, u.email, u.id, u.admin FROM user u, participants p WHERE u.id = p.user_id AND p.book_id = '$orig_bookId'";
	    $result = $db->query ($query);
	    $first = 1;
	    while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
	      {
		$telephone = '';
		$phones = enumerate_phones ($db, $row['email'], $row['id']);
		if (count ($phones))
		  $row['telephone'] = expand_phone ($phones[0][1]);
		extract($row);
		add_party ($email, $first_name, $last_name, $telephone,
			   $first, $row['admin'] == 'Participant');
	      }
	  }
	elseif (isset ($attendees))
	  {
	    $cnx = ldap_connect ('127.0.0.1');
	    ldap_bind ($cnx);
	    
	    $first = 1;
	    foreach ($attendees as $attendee)
	      {
                $rows = gcal_to_rows ($cnx, $db, $attendee);
		foreach($rows as $row)
		  add_party ($row['email'], $row['first_name'],
			     $row['last_name'], $row['telephone'], $first);
	      }

	    ldap_unbind ($cnx);
	  }
	else
	  {
	    $first = 1;
	    $cnx = ldap_connect ('127.0.0.1');
	    ldap_bind ($cnx);
	    $sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com', "(|(mail=$confOwner)(adacorealtemail=$confOwner))",
			       array ('mail', 'givenname', 'sn'));
	    $info = ldap_get_entries ($cnx, $sr);
	    if ($info['count'] == 1)
	      {
		$first_name = $info[0]['givenname'][0];
		$last_name = $info[0]['sn'][0];
		$email = $info[0]['mail'][0];
		$phones = enumerate_phones ($db, $email, false);
		if (count ($phones))
		  $telephone = expand_phone ($phones[0][1]);

		add_party ($email, $first_name, $last_name, $telephone, 
			   $first);
	      }

	    ldap_unbind ($cnx);
	  }
?>
	</tbody></table>
		</td>
		</tr>
<?php } ?>
		<tr class="lastRow">
		<td class="legend bbl"></td>
		<td class="bar-search bbr">
		<INPUT TYPE="hidden" NAME="bookId" value=<?php echo $bookId; ?>>
		<INPUT TYPE="hidden" NAME="dateReq" value="<?php if (isset ($dateReq)) echo $dateReq; ?>">
<?php if (isset ($gcal_id)) { ?>
		<INPUT type="hidden" NAME="gcalId" value=<?php echo $gcal_id; ?>> <?php } ?>
		<span class="actionResult" id="actionResult"></span>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php if ($bookId) { ?>
			     <SPAN title="Update the conference with the above data">
			     <input type="Submit" id="_button" name="update" align="top" border="0" value="Update"/></span>
			     <SPAN title="Delete the conference">
				<input type="Submit" id="_delete" class="warn" name="delete" align="top" border="0" value="Delete" onClick="if(!confirm('Are you sure you want to delete this conference?')) return false; else this.deleting = 1;"></span>
			     <b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<SPAN title="Perform this update in all subsequent occurrences of this conference">
			      <input type="CHECKBOX"  name="updateSeries" align="top" border="0" value="1" id="updateSeries"/>
			      <label for="updateSeries">Update series</label>
			      </span></b>
	<?php } else { ?>
  				<SPAN title="Create a conference using the above data">
				<input type="Submit" id="_button" name="add" align="top" border="0" value="<?php print _("Schedule Conference"); ?>"></span>
	<?php } ?>
			</td>
	</tr>
		<tr id="resultEmail" style="display:none">
		<td class="legend bbl">Sent email</td>
		<td class="bar-search bbr">
		<span id="conf_add_response"></span></td></tr>
	</tbody></table>
</FORM>
</div></div><br/><br/>
<div class="content" id="findTime" style="display:none">
<div class="bar-status">
<div class="bar-search btl btr bbl bbr nopad">
<div class="listheader btl btr">Find a time that all participants are available.<span class="closeButton">
<BUTTON id="closeButton" onClick="findTimeClose ()" NAME="Close" VALUE="Close"></span></div>
<div class="ib" id="findTimeDP"></div>
<div class="ib" id="findTimeRight"><div id="findTimeErrors"></div>
<div id="findTimeMain"><h3>Click on a date to check for availability of all the participants on that date.</h3></div></div>
<div class="listheader bbl bbr clear">&nbsp;</div>
</div></div></div></div>
<div class="findTimeTooltip" id="tip1" style="display:none"></div>
<div class="findTimeTooltip" id="tip2" style="display:none"></div>
