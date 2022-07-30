<?php

function xml_parse_into_assoc ($file)
{
  $data = file_get_contents ($file);
  if (!$data)
    return array ();
  return xml_parse_string_into_assoc ($data);
}

function xml_parse_string_into_assoc ($s)
{
  $p = xml_parser_create ();
  
  xml_parser_set_option ($p, XML_OPTION_CASE_FOLDING, 0);
  xml_parser_set_option ($p, XML_OPTION_SKIP_WHITE, 1);
  
  xml_parse_into_struct ($p, $s, $vals, $index);
  xml_parser_free ($p);

  $levels = array (null);
  
  foreach ($vals as $val)
    {
      if ($val['type'] == 'open' || $val['type'] == 'complete')
	if (!array_key_exists ($val['level'], $levels))
	  $levels[$val['level']] = array ();
    
      $prevLevel =& $levels[$val['level'] - 1];
      $parent = $prevLevel[sizeof ($prevLevel) - 1]; 
    
      if ($val['type'] == 'open')
	{
	  $val['children'] = array ();
	  array_push ($levels[$val['level']], $val);
	  continue;
	}
    
    else if ($val['type'] == 'complete' && isset ($val['value']))
      $parent['children'][$val['tag']] = $val['value'];
    
    else if ($val['type'] == 'close')
      {
	$pop = array_pop ($levels[$val['level']]);
	$tag = $pop['tag'];
	$children = $pop['children'];

	foreach ($children as $ctag => $cval)
	  if (is_array ($cval) && count ($cval) == 1 && isset ($cval[0]))
	    $children[$ctag] = $cval[0];
      
	if ($parent)
	  {
	    if (!array_key_exists ($tag, $parent['children']))
	      $parent['children'][$tag][0] = $children;
	    else if (is_array ($parent['children'][$tag]))
	      $parent['children'][$tag][] = $children; 
	  }
	else
	  return array($pop['tag'] => $children);
      }
    
    $prevLevel[sizeof($prevLevel) - 1] = $parent;
  }
}
?>
