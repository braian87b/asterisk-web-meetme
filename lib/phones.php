<?php
function pack_phone ($number)
{
  if (substr ($number, 0, 1) == '+')
    $number = substr ($number, 1);
  else if (substr ($number, 0, 1) == '('
	   || (strlen ($number) == 12
	       && (substr ($number, 3, 1) == '-'
		   || substr ($number, 3, 1) == ' '
		   || substr ($number, 3, 1) == '.')
	       && (substr ($number, 7, 1) == '-'
		   || substr ($number, 7, 1) == ' '
		   || substr ($number, 7, 1) == '.')))
    $number = "1$number";
  else if (!strcasecmp (substr ($number, 0, 6), 'skype:'))
    return substr ($number, 6);

  return str_replace (array ('(', ')', '-', ' ', '.', '/'), array (), $number);
}

function expand_phone ($number)
{
  if (ctype_alpha ($number[0]))
    return "Skype:$number";
  else if (strlen ($number) == 5 && substr ($number, 0, 2) == '48')
    return substr ($number, 2);
  else if (strlen ($number) < 5)
    return $number;

  if (substr ($number, 0, 4) == '+011')
    $number = substr ($number, 4);
  else if (substr ($number, 0, 1) == '+')
    $number = substr ($number, 1);

  $forms = array ('/^1/' => array (1, 3, 3, 4),
		  '/^20[23]/' => array (2, 1, 7),
		  '/^20/' => array (2, 2, 7),
		  '/^212/' => array (3, 2, 3, 4),
		  '/^213[567]/' => array (3, 3, 2, 2, 2),
		  '/^213/' => array (3, 2, 2, 2, 2),
		  '/^216/' => array (3, 2, 3, 3),
		  '/^218/' => array (3, 8),
		  '/^22[01]/' => array (3, 3, 4),
		  '/^22[2348]/' => array (3, 4, 4),
		  '/^22[56]/' => array (3, 2, 2, 2, 2),
		  '/^22[79]/' => array (3, 2, 3, 3),
		  '/^23[08]/' => array (3, 3, 4),
		  '/^231/' => array (3, 4, 4),
		  '/^232/' => array (3, 2, 3, 3),
		  '/^23[37]/' => array (3, 2, 3, 4),
		  '/^234[19]/' => array (3, 1, 3, 4),
		  '/^23470/' => array (3, 2, 4, 4),
		  '/^2348[01]/' => array (3, 2, 4, 4),
		  '/^234/' => array (3, 2, 3, 4),
		  '/^23[56]/' => array (3, 2, 2, 2, 2),
		  '/^239/' => array (3, 2, 5),
		  '/^240/' => array (3, 3, 3),
		  '/^2410[567]/' => array (3, 2, 3, 3),
		  '/^241/' => array (3, 3, 3),
		  '/^242/' => array (3, 2, 3, 4),
		  '/^243[89]/' => array (3, 2, 3, 4),
		  '/^243/' => array (3, 1, 3, 3),
		  '/^2449/' => array (3, 3, 3, 3),
		  '/^24[49]/' => array (3, 2, 3, 4),
		  '/^245/' => array (3, 3, 4),
		  '/^248/' => array (3, 1, 2, 2, 2),
		  '/^250/' => array (3, 3, 3),
		  '/^251/' => array (3, 2, 3, 4),
		  '/^2529/' => array (3, 2, 3, 3),
		  '/^252/' => array (3, 3, 4),
		  '/^253/' => array (3, 2, 2, 2, 2),
		  '/^2547/' => array (3, 3, 3),
		  '/^254/' => array (3, 2, 5),
		  '/^2557/' => array (3, 3, 3, 3),
		  '/^255/' => array (3, 2, 3, 4),
		  '/^25[68]/' => array (3, 2, 3, 4),
		  '/^257/' => array (3, 4, 4),
		  '/^2609/' => array (3, 2, 3, 4),
		  '/^26[06]/' => array (3, 2, 3, 3),
		  '/^261/' => array (3, 2, 2, 3, 2),
		  '/^262/' => array (3, 3, 3),
		  '/^263/' => array (3, 2, 3, 4),
		  '/^264/' => array (3, 2, 3, 4),
		  '/^26577/' => array (3, 2, 3, 4),
		  '/^26588/' => array (3, 2, 3, 4),
		  '/^26599/' => array (3, 2, 3, 4),
		  '/^265/' => array (3, 1, 3, 3),
		  '/^2677[1-6]/' => array (3, 2, 3, 3),
		  '/^26[79]/' => array (3, 3, 4),
		  '/^268/' => array (3, 4, 4),
		  '/^27/' => array (2, 2, 3, 4),
		  '/^290/' => array (3, 2, 2),
		  '/^291/' => array (3, 1, 3, 3),
		  '/^297/' => array (3, 3, 4),
		  '/^29[89]/' => array (3, 3, 3),
		  '/^2/' => array (3, 8),
		  '/^3021/' => array (2, 2, 4, 4),
		  '/^30/' => array (2, 4, 4, 4),
		  '/^311[035]/' => array (2, 2, 3, 4),
		  '/^312[0346]/' => array (2, 2, 3, 4),
		  '/^313[03568]/' => array (2, 2, 3, 4),
		  '/^314[0356]/' => array (2, 2, 3, 4),
		  '/^315[0358]/' => array (2, 2, 3, 4),
		  '/^316/' => array (2, 1, 4, 4),
		  '/^317/' => array (2, 2, 3, 4),
		  '/^31/' => array (2, 3, 3, 3),
		  '/^32[2349]/' => array (2, 1, 3, 2, 2),
		  '/^32/' => array (2, 2, 3, 2, 2),
		  '/^33/' => array (2, 1, 2, 2, 2, 2),
		  '/^34/' => array (2, 3, 2, 2, 2),
		  '/^361/' => array (2, 1, 3, 4),
		  '/^36/' => array (2, 2, 3, 4),
		  '/^3780549/' => array (3, 4, 7),
		  '/^3790549/' => array (3, 4, 7),
		  '/^3851/' => array (3, 1, 3, 4),
		  '/^385/' => array (3, 2, 3, 4),
		  '/^3[578]/' => array (3, 8),
		  '/^390[26]/' => array (2, 2, 8),
		  '/^390[1345789][0159]/' => array (2, 3, 7),
		  '/^390[1345789][234678]/' => array (2, 4, 6),
		  '/^393/' => array (2, 3, 7),
		  '/^40/' => array (2, 3, 3, 3),
		  '/^41/' => array (2, 3, 2, 2),
		  '/^420/' => array (3, 3, 3, 3),
		  '/^4212/' => array (3, 1, 3, 3, 2),
		  '/^421[3-9]/' => array (3, 2, 3, 4),
		  '/^423[3-9]/' => array (3, 3, 2, 2),
		  '/^42[4-9]/' => array (3, 5),
		  '/^431/' => array (2, 1, 5),
		  '/^43/' => array (2, 3, 5),
		  '/^441.1/' => array (2, 3, 3, 4),
		  '/^4411/' => array (2, 3, 3, 4),
		  '/^4413873/' => array (2, 5, 4),
		  '/^4415242/' => array (2, 5, 4),
		  '/^441539[456]/' => array (2, 5, 4),
 		  '/^441697[347]/' => array (2, 5, 4),
		  '/^441768[347]/' => array (2, 5, 4),
		  '/^4419467/' => array (2, 5, 4),
		  '/^441/' => array (2, 4, 6),
		  '/^442/' => array (2, 2, 4, 4),
		  '/^447/' => array (2, 4, 6),
		  '/^44/' => array (2, 3, 3, 4),
		  '/^45/' => array (2, 2, 2, 2),
		  '/^468/' => array (2, 1, 3, 3, 2),
		  '/^46[12345679]/' => array (2, 3, 5),
		  '/^47/' => array (2, 2, 2, 2),
		  '/^48/' => array (2, 3, 3, 3),
		  '/^4930/' => array (2, 2, 5, 3),
		  '/^4940/' => array (2, 2, 5, 3),
		  '/^4969/' => array (2, 2, 5, 3),
		  '/^4989/' => array (2, 2, 5, 3),
		  '/^49228/' => array (2, 3, 3, 5),
		  '/^49421/' => array (2, 3, 3, 5),
		  '/^49371/' => array (2, 3, 3, 5),
		  '/^49221/' => array (2, 3, 3, 5),
		  '/^49355/' => array (2, 3, 3, 5),
		  '/^49211/' => array (2, 3, 3, 5),
		  '/^49361/' => array (2, 3, 3, 5),
		  '/^49201/' => array (2, 3, 3, 5),
		  '/^49335/' => array (2, 3, 3, 5),
		  '/^49365/' => array (2, 3, 3, 5),
		  '/^49345/' => array (2, 3, 3, 5),
		  '/^49511/' => array (2, 3, 3, 5),
		  '/^49431/' => array (2, 3, 3, 5),
		  '/^49261/' => array (2, 3, 3, 5),
		  '/^49341/' => array (2, 3, 3, 5),
		  '/^49391/' => array (2, 3, 3, 5),
		  '/^49621/' => array (2, 3, 3, 5),
		  '/^49395/' => array (2, 3, 3, 5),
		  '/^49911/' => array (2, 3, 3, 5),
		  '/^49331/' => array (2, 3, 3, 5),
		  '/^49381/' => array (2, 3, 3, 5),
		  '/^49385/' => array (2, 3, 3, 5),
		  '/^49711/' => array (2, 3, 3, 5),
		  '/^49611/' => array (2, 3, 3, 5),
		  '/^49561/' => array (2, 3, 3, 5),
		  '/^49/' => array (2, 4, 4, 2),
		  '/^500/' => array (3, 5),
		  '/^501/' => array (3, 1, 5),
		  '/^502/' => array (3, 2, 5),
		  '/^503/' => array (3, 4, 4),
		  '/^504/' => array (3, 3, 4),
		  '/^505/' => array (3, 2, 5),
		  '/^506/' => array (3, 4, 4),
		  '/^507/' => array (3, 1, 5),
		  '/^508/' => array (3, 5),
		  '/^509/' => array (3, 3, 4),
		  '/^511/' => array (2, 1, 3, 4),
		  '/^511/' => array (2, 2, 2, 4),
		  '/^5255/' => array (2, 4, 4),
		  '/^52/' => array (3, 3, 4),
		  '/^5411/' => array (2, 2, 4, 4),
		  '/^5422/' => array (2, 3, 3, 4),
		  '/^5426[14]/' => array (2, 3, 3, 4),
		  '/^5434[1235]/' => array (2, 3, 3, 4),
		  '/^5435[18]/' => array (2, 3, 3, 4),
		  '/^5438[57]/' => array (2, 3, 3, 4),
		  '/^544/' => array (2, 3, 3, 4),
		  '/^54/' => array (2, 4, 2, 4),
		  '/^5511/' => array (2, 2, 5, 4),
		  '/^55/' => array (2, 2, 4, 4),
		  '/^562/' => array (2, 1, 3, 4),
		  '/^56/' => array (2, 2, 3, 4),
		  '/^57/' => array (2, 1, 5),
		  '/^58/' => array (2, 3, 5),
		  '/^59/' => array (3, 1, 5),
		  '/^601/' => array (2, 2, 3, 4),
		  '/^608/' => array (2, 2, 2, 4),
		  '/^60/' => array (2, 1, 3, 4),
		  '/^614/' => array (2, 3, 3, 4),
		  '/^61/' => array (2, 1, 4, 4),
		  '/^62[4569]/' => array (2, 1, 5),
		  '/^62/' => array (2, 2, 5),
		  '/^632/' => array (2, 1, 3, 4),
		  '/^63/' => array (2, 2, 3, 4),
		  '/^64/' => array (2, 1, 3, 4),
		  '/^65/' => array (2, 2, 4, 4),
		  '/^662/' => array (2, 1, 3, 4),
		  '/^66/' => array (2, 2, 3, 4),
		  '/^6[789]/' => array (3, 5),
		  '/^7/' => array (1, 3, 2, 2),
		  '/^81[36]/' => array (2, 1, 4, 4),
		  '/^811/' => array (2, 2, 3, 4),
		  '/^812[256]/' => array (2, 2, 3, 4),
		  '/^814[23459]/' => array (2, 2, 3, 4),
		  '/^815[2458]/' => array (2, 2, 3, 4),
		  '/^817[358]/' => array (2, 2, 3, 4),
		  '/^818[2479]/' => array (2, 2, 3, 4),
		  '/^819[23679]/' => array (2, 2, 3, 4),
		  '/^81/' => array (2, 3, 2, 4),
		  '/^822/' => array (2, 1, 3, 4),
		  '/^82/' => array (2, 2, 5),
		  '/^84[48]/' => array (2, 1, 5),
		  '/^84/' => array (2, 2, 5),
		  '/^8[578]/' => array (3, 5),
		  '/^8610/' => array (2, 2, 4, 3),
		  '/^861/' => array (2, 3, 4, 3),
		  '/^862/' => array (2, 2, 4, 3),
		  '/^86/' => array (2, 3, 4, 3),
		  '/^90/' => array (2, 3, 2, 2),
		  '/^91[789]/' => array (2, 4, 5),
		  '/^91/' => array (2, 3, 7),
		  '/^9[2345]/' => array (2, 5),
		  '/^96/' => array (3, 5),
		  '/^972/' => array (2, 1, 3, 4),
		  '/^9[79]/' => array (3, 5),
		  '/^9821/' => array (2, 2, 4, 4),
		  '/^9826/' => array (2, 2, 4, 4),
		  '/^9825/' => array (2, 2, 4, 4),
		  '/^9835/' => array (2, 2, 4, 4),
		  '/^98/' => array (2, 3, 4, 4));

  $result = '+';
  $first = true;
  $sep = '';
  foreach ($forms as $prefix => $form)
    {
      if (preg_match ($prefix, $number))
	{
	  foreach ($form as $part)
	    {
	      if (strlen ($number) < $part)
		$part = strlen ($number);

	      $result .= $sep . substr ($number, 0, $part);
	      if ($first)
		{
		  $sep = substr ($number, 0, 1) == '1' ? '-' : ' ';
		  $first = false;
		}

	      $number = substr ($number, $part);
	    }

	  if (strlen ($number))
	    $result .= $number;

	  return $result;
	}
    }

  return '+' . $number;
}

function enumerate_phones ($db, $email, $uuid)
{
  $result = array ();
  $cnx = ldap_connect ('127.0.0.1');
  ldap_bind ($cnx);
  $sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com',
		     "(|(mail=$email)(adaCoreAltEmail=$email))");
  $info = ldap_get_entries ($cnx, $sr);

  if ($info['count'] == 1)
    {
      if (isset ($info[0]['adacoreextension']))
	$result[] = array ('extension', $info[0]['adacoreextension'][0], 'ex');
      else if (isset ($info[0]['phonenumber']))
	$result[] = array ('office', $info[0]['phonenumber'][0], 'ph');

      if (isset ($info[0]['mobile']))
	for ($i = 0; $i < $info[0]['mobile']['count']; $i++)
	  $result[] = array ('cell', $info[0]['mobile'][$i],
			     $i ? ('c' . $i) : 'cl');
      if (isset ($info[0]['homephone']))
	$result[] = array ('home', $info[0]['homephone'][0], 'ho');
      if (isset ($info[0]['adacorecustomnumber'])
	  && isset ($info[0]['adacorecustomtype']))
	$result[] = array ($info[0]['adacorecustomtype'][0],
			   $info[0]['adacorecustomnumber'][0], 'cu');

      if (isset ($info[0]['adacorealtextension']))
	for ($i = 0; $i < $info[0]['adacorealtextension']['count']; $i++)
	  {
	    $vals = explode ('/', $info[0]['adacorealtextension'][$i]);
	    $result[] = array ($vals[1] . ' extension', $vals[0], 'a' . $i);
	  }
    }

  ldap_unbind ($cnx);

  if ($db && $uuid)
    {
      $query = "SELECT id, type, phone FROM phones WHERE user_id=$uuid";
      $res = $db->query ($query);
      while ($row = $res->fetchRow (DB_FETCHMODE_ASSOC))
	$result[] = array ($row['type'], $row['phone'], $row['id']);
    }

  foreach ($result as &$val)
    $val[1] = pack_phone ($val[1]);

  return $result;
}

function update_phones ($db, $email, $uuid, $phones, $types)
{
  $db->query ("DELETE FROM phones WHERE user_id='$uuid'");
  $old = enumerate_phones ($db, $email, $uuid);

  for ($i = 0; $i < count ($phones); $i++)
    {
      $phone = addslashes (pack_phone ($phones[$i]));
      $type = addslashes ($types[$i]);
      $match = false;
      foreach ($old as $oldval)
	if ($oldval[1] == $phone)
	  $match = true;

      if (!$match && $phone != '')
	$db->query ("INSERT INTO phones VALUES (0, $uuid, '$type', '$phone')");
    }
}

/* This function sets up to call a list of people to add them to the
   conference.  $rows are a set of row from the database (pointed to by $db)
   that gives the people to call.  */

function do_callouts ($rows, $db)
{
  if (count ($rows) == 0)
    return;

  for ($try = 0; $try < 3; $try++)
    {
      $caller_map = array ();
      foreach ($rows as $row)
	{
	  $found = false;
	  extract ($row);
	  $query = "DELETE FROM callouts WHERE id='$id'";
	  $db->query ($query);
	  if (substr ($callout, 2) == 'A')
	    $pin = $adminpin;

	  if (!isset ($caller_map[$confno]))
	    $caller_map[$confno] = get_caller_list ($confno, $conf);

	  $phones = enumerate_phones ($db, $email, $user_id);
	  foreach ($phones as $phone)
	    foreach ($caller_map[$confno] as $caller)
	      if ($caller['number'] == $phone[1]
		  || (strlen ($caller['number']) > 8
		      && (substr ($phone[1], - strlen ($caller['number']))
			  == $caller['number'])))
		$found = true;

	  if (!$found)
	    {
	      foreach ($phones as $phone)
		if ($phone[2] == substr ($callout, 0, 2))
		  callout ($confno, "$first_name $last_name", $phone[1],
			   $pin, true);
	    }
	}

      sleep (90);
    }
}

function canon_phone ($phone, $country)
{
  include 'countries.php';

  $phone = trim ($phone);
  if (substr ($phone, 1, 1) == ',')
    $phone = '+' . substr ($phone, 0, 1) . substr ($phone, 2);

  $phone_parts = explode (' or ', $phone);
  if (strlen ($phone_parts[0]) > 3)
    $phone = trim ($phone_parts[0]);

  $phone_parts = explode ('@@', $phone);
  $phone = trim ($phone_parts[0]);
  $phone_parts = explode (',', $phone);
  $phone = trim ($phone_parts[0]);
  $phone_parts = explode ('//', $phone);
  $phone = trim ($phone_parts[0]);
  $phone_parts = explode ('ext', $phone);
  if (count ($phone_parts) == 1)
    $phone_parts = explode ('Ext', $phone);
  if (count ($phone_parts) == 1)
    $phone_parts = explode ('EXT', $phone); 
  if (count ($phone_parts) == 1)
    $phone_parts = explode ('x', $phone);
  if (count ($phone_parts) == 1)
    $phone_parts = explode ('X', $phone);
 
  if (strlen ($phone) == 0)
    return '';

  $phone = trim ($phone_parts[0]);
  if (substr ($phone, 0, 2) == '00')
    $phone = '+' . substr ($phone, 2);

  if (substr ($phone, 0, 1) == '+')
    $phone = str_replace ('(0)', '', $phone);

  $phone = str_replace ('/', '', $phone);
  if (preg_match ('/^\(\+([ 0-9]+)\)(.*)$/', $phone, $matches))
    $phone = "+$matches[1]$matches[2]";

  if (preg_match ('/^\(([2-9][0-9])\)(.*)$/', $phone, $matches))
    $phone = "+$matches[1]$matches[2]";

  if (preg_match ('/^\[([2-9][0-9])\](.*)$/', $phone, $matches))
    $phone = "+$matches[1]$matches[2]";

  if (substr ($phone, 0, 1) == '0'
      && isset ($country_codes[strtolower ($country)]))
    $phone = ('+' . $country_codes[strtolower ($country)]
	      . substr ($phone, 1));

  if (preg_match ('/^[2-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$/',
		  $phone))
    $phone = "+1$phone";

  $phone = pack_phone ($phone);
  if (strlen ($phone) == 0)
    return '';

  $phone = expand_phone ($phone);
  if (substr ($phone, 0, 4) != '+39 ' && substr ($phone, 0, 5) != '+378 '
      && substr ($phone, 0, 5) != '+379 ')
  if (preg_match ('/^\+([0-9]+) 0(.*)$/', $phone, $matches))
    $phone = expand_phone (pack_phone ("$matches[1]$matches[2]"));

  if (isset ($phone_parts[1]))
    {
      if (substr ($phone_parts[1], 0, 1) == '.')
	$phone_parts[1] = substr ($phone_parts[1], 1);

      $phone = "$phone x" . trim ($phone_parts[1]);
    }

  return $phone;
}
?>
