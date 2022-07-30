<?php
include ('lib/phones.php');
include ('lib/fillauto.php');
include ('lib/database.php');

session_start (); 
if ((defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
    || !isset ($_GET['value']) || !isset ($_GET['field']))
  exit;

$value = $_GET['value'];
$field = $_GET['field'];

$cnx = ldap_connect ('127.0.0.1');
ldap_bind ($cnx);

header ('Cache-Control: no-cache');
header ('Pragma: no-cache');
header ('Content-type: text/plain');

if ($field == 'telephone')
  $value = pack_phone ($value);

$results = match_field ($db, $cnx, $field, $value, true);
if (count ($results) != 1)
  {
    echo '[]';
    exit;
  }

$emaila = array_values ($results);
$real_email = $emaila[0];

/* See if in database.  */
$sql = "SELECT email, admin, first_name, last_name, id FROM user WHERE email = '$real_email'";
$result = $db->query ($sql);

if (PEAR::isError ($result)
    || ! ($row = $result->fetchRow (DB_FETCHMODE_ASSOC)))
  {
    $row = array ();
    $row['email'] = $real_email;
    $row['id'] = 0;
    $row['admin'] = 'Participant';
    $sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com',
		       "mail=$real_email", array ('givenname', 'sn'));
    $info = ldap_get_entries ($cnx, $sr);
    if ($info['count'] == 1)
      {
	$row['first_name'] = $info[0]['givenname'][0];
	$row['last_name'] = $info[0]['sn'][0];
      }
    else
      {
	$list = str_replace ('@adacore.com', '', $real_email);
	$sr = ldap_search ($cnx, 'ou=EmailLists,dc=adacore,dc=com',
			   "(cn=$list)");
	$info = ldap_get_entries ($cnx, $sr);
	if ($info['count'] == 1)
	  {
	    $parts = explode (' ', $info[0]['description'][0]);
	    $row['first_name']
	      = implode (' ', array_slice ($parts, 0,
					   intval (count ($parts) / 2)));
	    $row['last_name']
	      = implode (' ', array_slice ($parts,
					   intval (count ($parts) / 2)));
	  }
	else
	  {
	    $sr = ldap_search ($cnx, 'ou=GoogleGroups,dc=adacore,dc=com',
			       "(name=$real_email)");
	    $info = ldap_get_entries ($cnx, $sr);
	    if ($info['count'] == 1)
	      {
		$parts = explode (' ', $info[0]['cn'][0]);
		$row['first_name']
		  = implode (' ', array_slice ($parts, 0,
					       intval (count ($parts) / 2)));
		$row['last_name']
		  = implode (' ', array_slice ($parts,
					       intval (count ($parts) / 2)));
	      }
	    else
	      {
		echo '[]';
		exit;
	      }
	  }
      }

  }

$phones = enumerate_phones ($db, $real_email, $row['id']);
if (isset ($phones[0]))
  $row['telephone'] = expand_phone ($phones[0][1]);
unset ($row['id']);

if ($field == 'telephone')
  $value = expand_phone ($value);

$row[$field] = $value;
$row['email'] = $real_email;

foreach ($row as $key => &$val)
  if (strlen ($val) == 0)
    $val = '[none]';

echo json_encode ($row);
?>
