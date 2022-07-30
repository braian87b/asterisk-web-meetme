<?php

print <<<LEFTNAV1
<div id="side-left">
<ul id="side-nav">
LEFTNAV1;

$nkey = array_keys ($array);
$i = 0;
while($i < sizeof ($nkey))
  {
    $op_strong = ($i == $s) && (!strlen ($t)) ? '<strong>' : '';
    $cl_strong = ($i == $s) && (!strlen ($t)) ? '</strong>' : '';
    if (is_array ($array[$nkey[$i]]))
      {
	$op_span = (isset ($hints[$nkey[$i]]))
	  ? "<SPAN title=\"" . $hints[$nkey[$i]] . "\">" : '';
	$cl_span = isset($hints[$nkey[$i]]) ? '</SPAN>' : '';
	echo "\n\t<li>$op_strong<a href=\"$racine?s=$i\">$op_span$nkey[$i]$cl_span</a>$cl_strong<ul>";
	$j = 0;
	while($j < sizeof ($array[$nkey[$i]]))
	  {
	    $op_strong = (($i == $s) && (strlen ($t)) && ($j == intval ($t))
			  ? '<strong>' : '');
	    $cl_strong = (($i == $s) && (strlen ($t)) && ($j == intval ($t))
			  ? '</strong>' : '');
	    $op_span = (isset ($hints[$array[$nkey[$i]][$j]])
			? ("<SPAN title=\"" . $hints[$array[$nkey[$i]][$j]]
			   . "\">")
			: '');
	    $cl_span = isset ($hints[$array[$nkey[$i]][$j]]) ? '</SPAN>' : '';
	    echo "\n\t<li>$op_strong<a href=\"$racine?s=$i&t=$j\">$op_span&nbsp;" . $array[$nkey[$i]][$j] . "$cl_span</a>$cl_strong";
	    $j++;						
	  }
	    echo '</ul>';
      }
    else
      {					
	$op_span = (isset ($hints[$array[$nkey[$i]]])
		    ? "<SPAN title=\"" . $hints[$array[$nkey[$i]]] . "\">"
		    : '');
	$cl_span = isset ($hints[$array[$nkey[$i]]]) ? '</SPAN>' : '';
	echo ("\n\t<li>$op_strong<a href=\"$racine?s=$i\">$op_span"
	      . $array[$nkey[$i]] . "$cl_span</a>$cl_strong");
      }

    echo "</li>\n";
    $i++;
  }
echo '</ul></div>';
?>
