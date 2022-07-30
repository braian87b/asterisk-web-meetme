<?php

function in_group ($user, $group)
{
  $result = exec ("groups $user");
  if (substr($result,0,strlen($user)+3) != $user." : ")
    return false;

  return strpos ($result, " ".$group) !== false;
}

function get_yp_names($user)
{
  $result = explode (":", exec ("getent passwd $user"));
  if (count ($result) < 5)
    return array ();

  $name = $result[4];
  $pos = strpos ($name, '(');
  if ($pos)
    $name = trim (substr ($name, 0, $pos));

  $names = explode (" ", $name);
  if (count ($names) < 2)
    return array ();

  return array ($names[0], $names[count($names)-1]);
}

function get_priv ($user)
{
  if (defined ('AUTO_CREATE_ADMIN_GROUP')
      && in_group ($user, AUTO_CREATE_ADMIN_GROUP))
    return "Admin";
  elseif (defined ('AUTO_CREATE_MANAGER_GROUP')
	  && in_group ($user, AUTO_CREATE_MANAGER_GROUP))
    return "Manager";
  else
    return "User";
}

if (defined ('AUTH_TYPE') && defined ('AUTO_CREATE_DOMAIN')
    && !isset ($_SESSION['auth'])
    && isset ($_SERVER['REMOTE_USER']) && isset ($_SERVER['AUTH_TYPE']))
  {
    $user = $_SERVER['REMOTE_USER'];
    if (defined ('AUTO_CREATE_VALID_GROUP')
	&& !in_group ($user, AUTO_CREATE_VALID_GROUP))
      {
	echo 'Not a valid user of this system.';
	exit;
      }

    if (defined ('AUTO_CREATE_DOMAIN_FUNCTION'))
      {
	$fcn = AUTO_CREATE_DOMAIN_FUNCTION;
	$email = $fcn ($user);
      }
    else
      $email = $user."@".AUTO_CREATE_DOMAIN;

    $query = "SELECT id, admin, password FROM user WHERE email = '".$email."'";
    $result = $db->query ($query);
    if (!$result->numRows ())
      {
	$priv = get_priv ($user);
	$fields = "email, admin, password";
	$values = "'".$email."', '".$priv."', 'NIS'";
	$name_ar = get_yp_names ($user);
	if (count ($name_ar))
	  {
	    $fields .= ", first_name, last_name";
	    $values .= ", '".$name_ar[0]."', '".$name_ar[1]."'";
	  }

	$add_query = "INSERT INTO user (".$fields.") VALUES (".$values.")";
	$result = $db->query ($add_query);
	$result = $db->query($query);
      }
    if ($result->numRows ())
      {
	$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
	if (!$row['password'])
	  {
	    $sets = "SET password='NIS', admin='".get_priv ($user)."'";
	    $name = get_yp_names ($user);
	    if (count ($name))
	      $sets .= ", first_name='$name[0]', last_name='$name[1]'";
	    $update = "UPDATE user $sets WHERE email='$email'";
	    $result = $db->query ($update);
	  }

	$expires = time () + AUTH_TIMEOUT*3600;
	$_SESSION['privilege'] = $row['admin'];
	$_SESSION['clientid'] = $row['id'];
	$_SESSION['userid']=$email;
	$_SESSION['auth']="true";
	$_SESSION['lifetime']=$expires;
	unset ($_SESSION['failure']);
      }
  }

function ldap_get_email ($user)
{
  $cnx = ldap_connect ('ldap.gnat.com');
  ldap_bind ($cnx);
  $sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com', "uid=$user",
		     array ('mail'));
  $info = ldap_get_entries ($cnx, $sr);
  return ($info['count'] != 1 || !isset ($info[0]['mail'])
	  ? $user . '@' . AUTO_CREATE_DOMAIN
	  : $info[0]['mail'][0]);
}    
?>
