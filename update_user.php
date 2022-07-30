<?php

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';
include 'locale.php';

session_start ();
getpost_ifset (array ('s', 't', 'order', 'sens', 'current_page', 'uuid'));

if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

include 'lib/header.php';
include 'lib/leftnav.php';

$query = "SELECT first_name, last_name, email, admin, password FROM user WHERE id =?";
$data = array ($uuid);
$result = $db->query ($query, $data);
$row = $result->fetchRow (DB_FETCHMODE_ASSOC);

if (is_array ($row))
  extract ($row);

if ($uuid != $_SESSION['clientid'] && $_SESSION['privilege'] != 'Admin'
    && $admin != 'Participant')
{
  echo "Can't update another user.";
  exit;
}

$names_locked = 0;
$cnx = ldap_connect ('127.0.0.1');
ldap_bind ($cnx);
$sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com', "(|(mail=$email)(adacorealtemail=$email))",
		   array ('mail', 'givenname', 'sn'));
$info = ldap_get_entries ($cnx, $sr);
if ($info['count'] == 1 && $first_name == $info[0]['givenname'][0]
    && $last_name == $info[0]['sn'][0])
  $names_locked = 1;
?>
<!-- content BEGIN -->
<div id="main">
<div id="content" class="content">
<!-- ** ** ** ** ** Part to update the user ** ** ** ** ** -->
<FORM METHOD=POST NAME="WMAdd" onsubmit=" return onSubmitUser ('<?php echo AUTH_TYPE; ?>', <?php echo ($_SESSION['privilege'] == 'Admin') ?>)" >
<INPUT TYPE="hidden" NAME="s" value="<?php echo $s?>">
<INPUT TYPE="hidden" NAME="t" value="<?php echo $t?>">
<INPUT TYPE="hidden" NAME="sens" value="<?php echo $sens?>">
<INPUT TYPE="hidden" NAME="order" value="<?php echo $order?>">
<INPUT TYPE="hidden" NAME="current_page" value="<?php echo $current_page?>">
<INPUT TYPE="hidden" NAME="uuid" value="<?php echo $uuid; ?>">
	<table class="borders" align="center">
		<tbody>
                <tr>
		<td class="legend btl">
		<SPAN title="Email addresss">Email</SPAN></td>
		<td class="bar-search btr">
		<SPAN title="Email addresss"><INPUT TYPE="text" NAME="userEmail" <?php if (isset ($email)) echo 'readonly style="background-color:LightGrey"' ?> value="<?php print $email ?>" size=40></span></td>
		</tr>
		<tr>
		<td class="legend">
		<SPAN title="First name of user">First Name</SPAN></td>
			<td class="bar-search">
			<SPAN title="First name of user"><INPUT TYPE="text" NAME="fname" <?php if ($names_locked) echo 'readonly style="background-color:LightGrey"' ?> value="<?php print $first_name ?>" size=30></span></td>
		</tr>
		<tr>
		<td class="legend">                     
		<SPAN title="Last name of user">Last Name</SPAN>
			</td>
			<td class="bar-search">
			<SPAN title="Last name of user"><INPUT TYPE="text" NAME="lname" <?php if ($names_locked) echo 'readonly style="background-color:LightGrey"' ?> value="<?php print $last_name ?>" size=30></span></td>
		</tr>
                <tr id="PasswordRow" <?php if ($admin == 'Participant' || (defined('AUTO_CREATE_DOMAIN') && $password == "NIS")) echo 'style="display:none"'; ?>>
 		<td class="legend">Password</td>
			<td class="bar-search">
			<INPUT TYPE="password" NAME="userPass" value="<?php print $password ?>"></td>
		</tr>
                <tr id="VerifyPasswordRow" <?php if ($admin == 'Participant' || (defined('AUTO_CREATE_DOMAIN') && $password == "NIS")) echo 'style="display:none"'; ?>>
 		<td class="legend">Verify password</td>
			<td class="bar-search">
			<INPUT TYPE="password" NAME="verifyUserPass" value="<?php print $password ?>"></td>
		</tr>
		<tr>
                <td class="legend" title="Add a phone number for this user">
<button type="button" class="bstandard" onclick="addPhoneToTable('phones');">Add phone</button></td>
		<td class="bar-search">
		<table id="phones">
		<?php
		$phones = enumerate_phones ($db, $email, $uuid);
		if (count ($phones))
		  { ?>
		<thead><tr><th><b><center>Telephone</center></b></th>
		<th><b><center>Type</center></b></th></tr></thead><tbody>
		<?php }  foreach ($phones as $phoneval)
		  { ?>
		<tr><td><input type=text name=phone[] id="telephone" size=35 value="<?php echo expand_phone($phoneval[1])?>" <?php if (!is_numeric ($phoneval[2])) echo "readonly style=\"background-color:LightGrey\"" ?> ></td>
		<td><input type=text name=type[] id="type" size=20 value="<?php echo $phoneval[0]?>" <?php if (!is_numeric ($phoneval[2])) echo "readonly style=\"background-color:LightGrey\"" ?> ><input type=hidden name=from_ldap[] value=1></td>
		<td><span title="Delete this phone number"><button class=warn <?php if (!is_numeric ($phoneval[2])) echo "style=\"background-color:LightGrey\""; else echo "onClick=\"deleteCurrentRow(this)\""; ?> >Delete</button></span></td>
		</tr>
		<?php } ?>
		</tbody></table>
		</td>
		</tr>
		<?php if ($_SESSION['privilege'] == "Admin"
			  && (! isset ($email)
			      || !defined('AUTO_CREATE_DOMAIN')
			      || strpos ($email, AUTO_CREATE_DOMAIN))) { ?>
		<tr>
		<td class="legend">
		<SPAN title="Privilege level of user">Type</SPAN></td>
			<td class="bar-search"><b>
			<SPAN title="Caller Only"><label for="Participant">Caller</label>
			<INPUT TYPE="radio" NAME="userType" id=".participant" value="Participant" <?php if($admin == "Participant") echo 'checked'; ?>></span>
			<SPAN title="Ordinary user"><label for="User">&nbsp;&nbsp;&nbsp;&nbsp;User</label>
			<INPUT TYPE="radio" NAME="userType" id="user" <?php if (defined('AUTO_CREATE_DOMAIN')) { ?> onClick="displayUserPass(this) <?php } ?>" value="User" <?php if($admin == "User") echo 'checked'; ?>></span>
			<SPAN title="Can view or modify any conference."><label for="User">&nbsp;&nbsp;&nbsp;&nbsp;Manager</label>
			<INPUT TYPE="radio" NAME="userType" id="Manager" <?php if (defined('AUTO_CREATE_DOMAIN')) { ?> onClick="displayUserPass(this) <?php } ?>" value="Manager" <?php if($admin == "Manager") echo 'checked'; ?>></span>
			<SPAN title="Can modify user accounts"><label for="Admin">&nbsp;&nbsp;&nbsp;&nbsp;Administrator</label>
			<INPUT TYPE="radio" NAME="userType" id="Admin" <?php if (defined('AUTO_CREATE_DOMAIN')) { ?> onClick="displayUserPass(this) <?php } ?>" value="Admin" <?php if($admin == "Admin") echo 'checked'; ?>></span>
			</b></td>
		<?php } ?>
		<tr class="lastRow">
		<td class="legend bbl"> </td>
			<td class="bar-search bbr" align="center">
			<span class="actionResult" id="actionResult"></span>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php if ($email) { ?>
				<input type="Submit"  id="_button" name="update" align="top" border="0" value="Update User">
                                <input type="Submit"  id="_delete" class="warn" name="delete" align="top" border="0" value="Delete User" onClick="if(!confirm('Are you sure you want to delete this user?')) return false; else this.deleting = 1;">
	<?php } else { ?>
				<input type="Submit"  id="_button" name="add" align="top" border="0" value="Add User">
	<?php } ?>
			</td>
	        </tr>

	</tbody></table>
</FORM>
</div><div id="addUserResult" style="display: none"></div>
</body>
</html>
