<?php

function match_field ($db, $cnx, $field, $input, $exact)
{
  $sqlwild = $exact ? '' : '%';
  $ldapwild = $exact ? '' : '*';

  $results = array ();

  if ($field == 'telephone')
    {
      $input = pack_phone ($input);
      $select = 'SELECT p.phone, u.email FROM phones p, user u';
      $likes = "phone LIKE '1$input$sqlwild'";
      if ($input != '1')
	$likes .= " OR phone LIKE '$input$sqlwild'";
      $res = $db->query ("$select WHERE u.id = p.user_id AND ($likes)");
      if (!PEAR::isError ($res))
	while ($row = $res->fetchRow (DB_FETCHMODE_ASSOC))
	  $results[expand_phone ($row['phone'])] = $row['email'];
    }
  else
    {
      $input = strtolower ($input);
      $query = "SELECT $field, email FROM user WHERE $field LIKE '$input$sqlwild'";
      $res = $db->query ($query);
      if (!PEAR::isError ($res))
	while ($row = $res->fetchRow (DB_FETCHMODE_ASSOC))
	  $results[$row[$field]] = $row['email'];
    }

  if ($field == 'email')
    $attrs = array ('mail' => '', 'adacorealtemail' => '');
  else if ($field == 'first_name')
    $attrs = array ('givenname' => '');
  else if ($field == 'last_name')
    $attrs = array ('sn' => '');
  else
    $attrs = array ('homephone' => '+', 'mobile' => '+',
		    'adacorecustomnumber' => '+',
		    'adacoreextension' => '/', 'adacorealtextension' => '/',
		    'labeleduri' => ':');

  $filter = '';
  foreach ($attrs as $attr=>$modifier)
    {
      if ($modifier == '+')
	{
	  $term = "($attr=+1$input$ldapwild)";
	  if ($input != '1')
	    $term .= "($attr=+$input$ldapwild)";
	}
      else if ($modifier == '/')
	  $term = "($attr=$input)";
      else if ($modifier == ':')
	  $term = "($attr=skype:$input$ldapwild)";
      else
	$term = "($attr=$input$ldapwild)";

      $filter = "$filter$term";
    }

  $sr = ldap_search ($cnx, 'ou=People,dc=adacore,dc=com',
		     "(&(adacoreactive=TRUE)(|$filter))",
		     array_merge (array_keys ($attrs), array ('mail', 'uid')));
  $info = ldap_get_entries ($cnx, $sr);
  for ($ent = 0; $ent < $info['count']; $ent++)
    {
      if (isset ($info[$ent]['mail']))
	$email = $info[$ent]['mail'][0];
      elseif (defined ('AUTO_CREATE_DOMAIN'))
	$email = $info[$ent]['uid'][0] . '@' . AUTO_CREATE_DOMAIN;

      foreach ($attrs as $at=>$mod)
	if (isset ($info[$ent][$at]))
	  for ($i = 0; $i < $info[$ent][$at]['count']; $i++)
	    {
	      if ($field == 'telephone')
		{
		  $pack = pack_phone ($info[$ent][$at][$i]);
		  if (strpos ($pack, '/'))
		    $pack = substr ($pack, 0, strpos ($pack, '/'));
		  $pos = strpos ($pack, $input);
		  if (($mod == '/' && $pack == $input)
		      || ($mod == '+' && $pos === 1
			  && substr ($pack, 0, 1) == '1')
		      || ($mod != '/' && $pos === 0
			  && ! ($mod == '+' && $input == '1')))
		    $results[expand_phone ($pack)] = $email;
		}
	      else if (0 === strpos (strtolower ($info[$ent][$at][$i]),
				     $input))
		$results[$info[$ent][$at][$i]] = $email;
	    }
    }

  if ($field == 'email')
    {
      $list = str_replace ('@adacore.com', '', $input);
      $sr = ldap_search ($cnx, 'ou=EmailLists,dc=adacore,dc=com',
			 "(cn=$list$ldapwild)");
      $info = ldap_get_entries ($cnx, $sr);
      for ($ent = 0; $ent < $info['count']; $ent++)
	$results[$info[$ent]['cn'][0]. '@adacore.com']
	  = $info[$ent]['cn'][0]. '@adacore.com';

      $sr = ldap_search ($cnx, 'ou=GoogleGroups,dc=adacore,dc=com',
			 "(name=$input$ldapwild)");
      $info = ldap_get_entries ($cnx, $sr);
      for ($ent = 0; $ent < $info['count']; $ent++)
	if (isset ($info[$ent]['name']))
	  $results[$info[$ent]['name'][0]] = $info[$ent]['name'][0];
    }

  $newresults = array ();
  foreach ($results as $result => $email)
    {
      if ($field == 'first_name' || $field == 'last_name')
	$newresults[ucfirst ($result)] = $email;
      else if ($field == 'email')
	$newresults[strtolower ($result)] = $email;
      else
	$newresults[$result] = $email;
    }
	 
  ksort ($newresults);
  return $newresults;
}
?>
