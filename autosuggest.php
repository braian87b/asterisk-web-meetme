<?php
include ('lib/phones.php');
include ('lib/fillauto.php');
include ('lib/database.php');

session_start(); 
if ((defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
    || !isset ($_GET['term']) || !isset ($_GET['field']))
 exit;

$cnx = ldap_connect ('127.0.0.1');
ldap_bind ($cnx);

header ('Cache-Control: no-cache');
header ('Pragma: no-cache');
header ('Content-type: text/plain');

$matches = match_field ($db, $cnx, $_GET['field'], $_GET['term'], false);
if ($_GET['field'] == 'email')
  $result = array_unique (array_values ($matches));
else
  {
    $result = array ();
    foreach ($matches as $key=>$val)
    $result[] = array ('label' => "$key ($val)", 'value' => $key);
  }

echo json_encode ($result);
?>
