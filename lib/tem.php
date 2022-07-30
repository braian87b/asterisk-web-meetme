<?php

include 'lib/defines.php';
include 'lib/database.php';
include 'lib/functions.php';
include 'lib/phones.php';

$cnx = ldap_connect ('127.0.0.1');
ldap_bind ($cnx);

$res = get_emails_in_conf ($cnx, $db, 4400);
print_r ($res);
?>
