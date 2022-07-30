<?php

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';
include 'locale.php';

$s = '';
$t = '';
$logoff_section = '';

getpost_ifset (array ('s', 't', 'order', 'sens', 'current_page'));

if (!is_numeric (substr ($s, 0, 1)))
  {
    $s = '';
    $t = '';
    $section = $logoff_section;
  }
 else
   $s = substr ($s, 0, 1);
			
if (!is_numeric (substr ($t, 0, 1)))
  {
    $t = '';
    $section = $logoff_section;
  }
else
  $t = substr ($t, 0, 1);

if (!isset ($order))
  $order = '';

if (!isset ($sens))
  $sens = 'ASC';

if (!isset ($current_page))
  $current_page = 0;

if (defined ('AUTH_TYPE'))
  {
    getpost_ifset (array ('AUTH_USER', 'AUTH_PW'));
    session_set_cookie_params (0, '/' );
    session_start ();

    if (isset ($_SESSION['auth']))
      {
	if (($_SESSION['lifetime']) <= time ())
	  {
	    unset ($_SESSION['auth']);
	    unset ($_SESSION['privilege']);
	    unset ($_SESSION['userid']);
	    unset ($AUTH_USER);
	    unset ($AUTH_PW);
	  }
      }

    if (isset ($AUTH_USER) && isset ($AUTH_PW))
      {
	$user = new userSec ();
	$user -> authenticate ($AUTH_USER, $AUTH_PW);
	$user -> isAdmin ($AUTH_USER);
      }

    include 'lib/login.php';
    if ( !($_SESSION['auth']) ) { 
      $section = "section99";
    } 
  }

include 'lib/header.php';
include 'lib/leftnav.php';

if (isset ($_COOKIE['tz']))
  date_default_timezone_set ($_COOKIE['tz']);
else
  date_default_timezone_set (TIMEZONE);

$_SESSION['timezone'] = date_default_timezone_get ();
?>
<div id="main">
<?php  if ($section=="section0" || $section=="section1" || $section==$conf_section){?>
<div id="content" class="content"><div class="bar-status"><div class="content">
<h1><?php  echo GUI_TITLE; ?> Conference Scheduler</h1>
<h2>Schedule and display conferences.</h2>
<p>Use this application to schedule and monitor conferences, view
the participants and listen to the recording of previous conferences,
modify the information from previously-scheduled conferences, and
notify conference participants.</p>

<p>When a conference is scheduled, there are a number of options, one of
which is that it be recorded.  Another is a limit to the number of
participants (the default is unlimited).  Using PINs for the conference,
which distinguishes the leader from the rest of the participants, allows
other options to be specified.  A regular series of conferences with the
same names and parameters can also be specified.</p>

<p>A conference can optionally list participants. Once a conference has been
created, text can be added to the email that will be sent.  The options and
parameters or the list of people to invite can be modified for conferences
that haven't occured yet and another invitation email can be sent.  Once a
conference is completed, its list of participants can be displayed and any
recording can be and download or played.</p>

<p>An active conference can be monitored to see who called in.  Buttons are
provided to end the conference early, extend the time, or mute or remove a
particular user.  A participant can also be called and automatically added
to the conference.  It can either be an invited user with a phone number in
the database that's selected with a pull-down or else be a number and name
manually entered.</p>

<p>Select an activity from the menu on the left.</p></div></div>

<?php }elseif ($section=="section10"){
getpost_ifset('confno'); ?>
<!-- ** ** ** ** ** Part to select the conference ** ** ** ** ** -->
<script>
var async_order = 'ASC';
var async_sens = 'Name';
$(function () { setInterval (function  ()
			     { dynamicLoad ('#confdisplay', 'conf_async.php?confno=<?echo $_REQUEST['confno'] ?>&order=' + async_order + '&sens=' + async_sens, true); }, 1000); });
</script>
<div id="content" class="content">
 <div class="bar-status">
	<?php
    		$early = getConfDate (time () + (15 * 60));
		$end_now = getConfDate (time () - (60 * 60));
                $query = "SELECT pin, bookId FROM booking WHERE ";
		$query .= "confno='$confno' AND starttime<='$early'";
		$query .= "AND endtime>'$end_now'";
		$result = $db->query ($query);
		$row = $result->fetchRow (DB_FETCHMODE_ASSOC);
		$pin = $row['pin'];
		$bookId = $row['bookId'];
	?>
	<table>
	    <tr>
	     <td class="listheader btr btl leftheader listpad"><SPAN title="Call the selected participant"><button id="callInvite" name="Invite" onclick="conf_invite (<?php echo $confno; if ($pin) echo ",$pin"; ?>)">Call</button></span>
	        <SPAN title="Select a participant to be automatically called and added to the conference">&nbsp&nbsp;&nbsp;<b>Participant to call</b>&nbsp&nbsp;&nbsp;</span>
                <select NAME="toCall" id="toCall" onchange="callChange (this)">
	        <option VALUE="">
	<?php
		$cnx = ldap_connect ('127.0.0.1');
		ldap_bind ($cnx);
		$emails = get_emails_in_conf ($cnx, $db, $bookId);
		$f = fopen ('/tmp/ind', 'w');
		fwrite ($f, "bookid = $bookId\n");
		fwrite ($f, print_r ($emails, true));
		fclose ($f);
 		ldap_unbind ($cnx);
		foreach ($emails as $uem=>$data)
		{
		  $first_name = $data ? $data[0] : '';
		  $last_name = $data ? $data[1] : '';
		  $uid = $data ? $data[2] : 0;
		  $phones = enumerate_phones ($db, $uem, $uid);
		  foreach ($phones as $ent)
		    {
		      $phone = expand_phone ($ent[1]);
		      $value = "$first_name $last_name ($ent[0]) [$phone]";
		      echo "<OPTION VALUE='".$value."'>".$value;
		    }
		}
		?>
		<OPTION VALUE="other">Other</SELECT></font></td></tr>
		   <tr id="otherData" class="listheader leftheader" style="display:none">
		   <td>
		   <b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<SPAN title="Phone number to reach person.  This can be an extension, US 10-digit number, international number with leading +, or 'Skype:' followed by a Skype account name.">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Phone Number&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b><input id="toCallPhone" type="text" size=15 name="phone"></span>
		   <b><SPAN title="Name of person to call (for conference display)">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Name&nbsp;&nbsp;</b><input id="toCallName" type="text" size=20 name="name"></span>
		   </td></tr></table>
<div id = "confdisplay"></div></div></body>
<?php } elseif ($section==$conf_add_section){
 echo "<div id='content' class='content'>\n";
  include 'lib/new_conf.php';
} elseif ($section==$conf_delete_section){?>
<div id="deleteResult" class="content"></div>
<script>
$(function ()
  { dynamicLoad ('#deleteResult', 'conf_delete.php'); });
</script>
<!-- ** ** ** ** ** Part to select the conference(s) to delete ** ** ** ** -->
<br/><br/><div id="select" class="content">
<center>
<FORM METHOD=POST NAME="WMDel" onsubmit="return loadResult ('#deleteResult', 'conf_delete.php')">
<INPUT TYPE="hidden" NAME="s" value="<?php echo $s?>">
<INPUT TYPE="hidden" NAME="t" value="<?php echo $t?>">
<INPUT TYPE="hidden" NAME="current_page" value="<?php echo $current_page?>">
<table class="bar-status borders">
		<tbody>
		<tr>
		<td class="legend btl">
    		&nbsp;&nbsp;<SPAN title="Specify part of a conference name or number for a search">Name or Number</span></td>
			<td class="bar-search btr">
			<SPAN title="Specify part of a conference name or number for a search."><INPUT TYPE="text" NAME="confno" value="<?php if (isset ($confno)) echo $confno; ?>"></span></td>
		</tr>
		<tr class="lastRow">
		<td class="legend bbl">Search for Conferences to delete</td>
			<td class="bar-search bbr">
				<input type="image" src="images/button-search.gif" />
	</td></tr></tbody></table>
</FORM>
</center>
</div>
<?php }elseif ($section == $conf_past_section
	       || $section == $conf_current_section
	       || $section == $conf_future_section
	       || $section == $conf_clone_section){?>
<!-- ** ** ** ** ** Part to Update the conference ** ** ** ** ** -->
<?php
if ($section == $conf_past_section)
     {
       getpost_ifset (array ('hour'));
       if (isset ($hour))
	 {
	   $view = "Hour:$hour";
	   $select_msg = '';
	   $haveCdr = true;
	 }
       else
	 {
	   $view = 'Past';
	   $select_msg = 'Search for Conference to Review';
	   $haveCdr = true;
	 }
     }
elseif ($section == $conf_current_section)
  $view = 'Current';
else if ($section == $conf_future_section)
  {
    $view = 'Future';
    $select_msg = 'Search for Conference to Modify';
  }
else
{
  $view = 'Clone';
  $select_msg = 'Search for Conference to Clone';
} ?>
<div id="updateResult" class="content"></div>
<script>
$(function ()
  { dynamicLoad ('#updateResult', 'conf_update.php?<?php echo "s=$s&t=$t&view=$view"; ?>'); });
</script>
<?php if (isset ($haveCdr)) { ?>
<span id="cdrHaveResult" style="display:none"><br/><br/>
<div id="cdrResult" class="content"></div></span>
<?php } ?>
<?php if (isset ($select_msg) && strlen ($select_msg)) { ?>
<br/><br/><div id="select" class="content">
<FORM METHOD=POST NAME="WMCPF" onsubmit="return loadResult ('#updateResult', 'conf_update.php')">
<INPUT TYPE="hidden" NAME="s" value="<?php echo $s?>">
<INPUT TYPE="hidden" NAME="t" value="<?php echo $t?>">
<INPUT TYPE="hidden" NAME="view"" value="<?php echo $view?>">
<INPUT TYPE="hidden" NAME="current_page" value="<?php echo $current_page?>">
<table class="bar-status borders" align="center">
<tbody>
<tr>
<td class="legend btl">
<SPAN title="Specify part of a conference name or number for search."><?php print _("Name or Number"); ?></SPAN>
</td>
<td class="bar-search btr">
<SPAN title="Specify part of a conference name or number for search."><INPUT TYPE="text" NAME="confno" value="<?php if (isset($confno)) echo "$confno"; ?>"></span></td>
</tr>
<tr class="lastRow">
<td class="legend bbl"><?php print $select_msg; ?>
</td>
<td class="bar-search bbr">
<input type="image" src="images/button-search.gif" />
</td>
</tr>
</tbody></table>
<?php } ?>
</FORM>
</div>
<?php }elseif (isset ($user_add_section) && $section == $user_add_section){?>

<!-- ** ** ** ** ** Part to add the user ** ** ** ** ** -->

<!--  This is unused when NIS/LDAP is used and duplicates code in 
      update_user.php anyway.  -->
<?php }elseif ($section==$user_update_section){?>
<div id="userEditResult" class="content"></div>
<script>
$(function ()
  { dynamicLoad ('#userEditResult', 'user_edit_<?php echo AUTH_TYPE; ?>.php?<?php echo "s=$s&t=$t&order=$order&sens=$sens&current_page=$current_page"?>'); });
</script>
<?php }elseif ($section == $user_section){?>
<div id="content" class="content">
<div class="bar-status"><div class="content">
<h1><center><?php  echo GUI_TITLE; ?> <?php print _("Control"); ?></center></h1>
<?php print _("<h2>Add and update users.</h2>
<p>This part of the application lets you add, delete, and modify users.</p>"); ?></div></div>
<?php }elseif ($section == $report_section) { ?>
<div id="content" class="content"><div class="bar-status"><div class="content">
<h1><?php  echo GUI_TITLE; ?> <?php print _("Control"); ?></h1>
<h2>Display utilization reports.</h2>
<p>Display reports of conference system utilization by year, month or day.
In the yearly view, you can click on a month to see a monthly view and
similarly for the monthly view.  Both the number of scheduled conferences and
the number of conferences that were actually held are shown in each graph.</p>

<p>On the daily view, you can click on an hour to see all the conferences
that occurred during that hour that you have permissions to see.  For
each of those conferences, you can see who the participants were and how
long they were connected.</p></div></div>

<?php } elseif ($section == $report_yearly_section
		|| $section == $report_monthly_section
		|| $section == $report_daily_section)
{
  $which = ($section == $report_yearly_section
	    ? 'yearly'
	    : ($section == $report_monthly_section ? 'monthly' : 'daily'));
?>
<div id="reportResult" class="content"></div>
<script>
$(function () { dynamicLoad ('#reportResult', 'daily.php?type=<?php echo $which?>')});
</script>
<?php }elseif ($section==$logoff_section)
{
	unset ($_SESSION['auth']);
	unset ($_SESSION['userid']);
	unset ($_SESSION['privilege']);
	unset ($AUTH_USER);
	unset ($AUTH_PW);
	echo "<div id=\"content\" class=\"content\">";
	echo _("You have successfully logged off. ");
}
elseif ($section=="section99"){

if (AUTH_TYPE == "sqldb"){
	$Logon_Lable = _("Email");
}
else
{
	$Logon_Lable = _("Logon Name");
} ?>

<h1><center><div id="content" class="content">
<?php  echo GUI_TITLE; ?> <?php print _("Login"); ?></center></h1>

<h2><?php if ($_SESSION['failure']) { ?>
<center>Email or password incorrect.<br/><br/></center><?php } ?>
<center><?php print _("Please enter username and password"); ?>:</h2></center>
<FORM METHOD=POST  NAME="WMLogon" ACTION=<?php echo $_SERVER['PHP_SELF']?>>
<INPUT TYPE="hidden" NAME="s" value="<?php echo $s?>">
<INPUT TYPE="hidden" NAME="t" value="<?php echo $t?>">
<INPUT TYPE="hidden" NAME="current_page" value="<?php echo $current_page?>">
<table class="bar-status borders">
                <tbody>
                <tr>
                <td class="legend">
		<?php echo $Logon_Lable; ?></td>
                        <td class="bar-search">
                        <INPUT tabindex="100" TYPE="text" NAME="AUTH_USER" value="" size=25></td>
                </tr>
                <td class="legend">
		&nbsp;&nbsp; <SPAN title="Your password goes here">Password</SPAN>
                        </td>
                        <td class="bar-search">
                        <INPUT tabindex="101" TYPE="password" NAME="AUTH_PW" value="" size=25></td>
                </tr>
		<tr>
		<td class="bar-search"> </td>
		<INPUT TYPE="hidden" NAME="bookId" value=<?php echo $bookId; ?>>
		<td class="bar-search">
		<input tabindex="102" type="Submit"  name="Login" align="top" border="0" value="Login "/> </td></tr>
        </tbody></table>
</FORM>
</center>
<?php }else{?>
<?php
echo $section;   
?>
<?php }
?>
</div>	
</body>
</html>
