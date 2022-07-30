<?php

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';
include 'locale.php';

session_start ();
if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

include 'lib/header_vars.php';

getpost_ifset (array ('s', 't', 'order', 'sens', 'current_page', 'add',
		      'update', 'delete', 'uuid', 'fname', 'lname',
		      'userName', 'userPass', 'verifyUserPass', 'phone',
		      'type', 'userEmail', 'userType'));

if (!isset ($userType))
  $userType = 'Participant';

$FG_TABLE_NAME=DB_TABLEUSERS;

if ((isset ($add) || isset ($update)) && $userPass != $verifyUserPass)
  $Error = "Passwords don't match.";

$conflict = 0;

if (isset ($add) && !isset ($Error))
{
  if ($_SESSION['privilege'] == 'Admin')
    {
      if (checkEmail ($userEmail))
	{
	  $FG_TABLE_CLAUSE="email='$userEmail'";
	  $conflict = $db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE");

	  if (!intval ($conflict))
	    {
	      $userPass = md5 ($userPass);
	      $data = array ($userEmail, $userPass, $fname, $lname, $userType);
	      $query = 'INSERT INTO $FG_TABLE_NAME (email, password, first_name, last_name, admin) VALUES (?,?,?,?,?)';
	      $result = $db->query ($query, $data);
	    }

	  $result = $db->query ("SELECT id FROM user WHERE email='$userEmail'");
	  $row = $db->fetchRow (DB_FETCHMODE_ASSOC);
	  $uuid = $row['uuid'];
	}
      else
	$Error = 'You have entered an invalid email address.';
    }
 }

if (isset ($update) && ! isset ($Error))
  {
    if ($_SESSION['privilege'] == 'Admin' || $_SESSION['clientid'] == $uuid)
      {
	if (checkEmail ($userEmail))
	  {
	    $FG_TABLE_CLAUSE = "id='$uuid'";
	    $conflict = $db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE");
	    $query = "UPDATE $FG_TABLE_NAME SET email='$userEmail'";
	    if($userPass && strlen ($userPass) != 32 && $userPass != 'NIS')
	      {
		$userPass = md5 ($userPass);
		$userPass = "$userPass";
		$query .= ", password='$userPass'";
	      }

	    if (intval ($conflict) == 1)
	      {
		if ($userType)
		  $query .= ", admin='$userType'";
		if ($fname)
		  $query .= ", first_name='$fname'";
		if ($lname)
		  $query .= ", last_name='$lname'";
		$query .= " WHERE $FG_TABLE_CLAUSE";
		$result = $db->query ($query);
		$conflict = 0;
	      }
	  }
	else
	  $Error = 'You have entered an invalid email address.';
      }
}

if ((isset ($add) || isset ($update)) && isset ($uuid))
  update_phones ($db, $userEmail, $uuid, $phone, $type);

if (isset ($delete) && $_SESSION['privilege'] == 'Admin')
  {
    $db->query ("DELETE FROM $FG_TABLE_NAME WHERE id=$uuid");
    $db->query ("DELETE FROM phones WHERE user_id=$uuid");
    $db->query ("DELETE FROM participants WHERE user_id=$uuid");
}

if (isset ($error))
  $message = $error;
else if (isset ($add))
  $message = "User Added";
else if (isset ($update))
  $message = "User Updated";
else
  $message = "User Deleted";
?>
<script>
$(function () {
  processResult ("<?php echo $message ?>",
		 <?php echo (isset ($error) ? 1 : 0) ?>,
		 '<?php echo $s ?>', '<?php echo $t ?>', 0);
});
</script>
