<?php
include ('lib/defines.php');
include ('lib/database.php');
include ('lib/functions.php');

session_start(); 
if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

getpost_ifset (array ('now', 'type'));

$total = 0;
$max = 0;
$count = array ();
$used_count = array ();

if (!isset ($type))
  $type = 'daily';

if ($type == 'daily')
  {
    $first = 0;
    $last = 23;
    $delim = ' ';
 }
else if ($type == 'yearly')
  {
    $first = 1;
    $last = 12;
    $now = substr ($now, 0, 4);
    $delim = '-';
  }
else
 {
   $first = 1;
   $last = intval (date ('t', strtotime ($now)));
   $now = substr ($now, 0, 7);
   $delim = '-';
}

for ($t = $first; $t <= $last; $t++)
  {
    if ($t < 10)
      $like = $now . $delim . '0' . $t;
    else
      $like = $now . $delim . $t;

    $query = ('SELECT bookId FROM ' . DB_TABLESCHED
	      . " WHERE starttime LIKE '%$like%'");
    $rows = $db->query ($query);

    $i = 0;
    $count[$t] = $rows->numRows ();
    $total = $total +  $count[$t];

    $used_count[$t] = 0;
    while ($rows->fetchInto ($result))
      {
	$query
	  = 'SELECT * FROM ' . DB_TABLECDR . " WHERE bookId = '$result[0]'";
	$used_conf = $db->query ($query);
	if ($used_conf->numRows ())
	  $used_count[$t]++;
      }
  }

for ($t = $first; $t <= $last; $t++)
  {
    if ($count[$t] > $max)
      $max = $count[$t];
  }

$im_height = 600;
$hinc = ($type == 'yearly') ? 36 : 24;
$im_width = ($last - $first) * $hinc + 58;
$im_key = 70;
$hpos = 30;

$im = imagecreate ($im_width, $im_height + $im_key);
$white = imagecolorallocate ($im, 255, 255, 255);
$blue = imagecolorallocate ($im, 0, 0, 255);
$green = imagecolorallocate ($im, 0, 100, 0);
$red = imagecolorallocate ($im, 255, 0, 0);
$black = imagecolorallocate ($im, 0, 0, 0);

imageline ($im, 20, $im_height - 14, $im_width, $im_height - 14, $black);
imageline ($im, 0, $im_height, $im_width, $im_height, $black);
imageline ($im, 20, 0, 20, $im_height - 14, $black);
imageline ($im, 0, $im_height, 20, $im_height - 14, $black);

if ($type == 'yearly')
  $legend = "in $now";
else if ($type == 'monthly')
  $legend = ('in ' . $months[intval (substr ($now, 5, 2) - 1)] . ' '
	     . substr ($now, 0, 4));
else
  $legend = ('on ' . $months[intval (substr ($now, 5, 2) - 1)] . ' '
	     . (substr ($now, 8, 1) == '0' ? '' : substr ($now, 8, 1))
	     . substr ($now, 9, 1) . ', '
	     . substr ($now, 0, 4));

imagestring ($im, 6, 5, $im_height + 10,
	     "Legend:          Conferences $legend", $black);
imagestring ($im, 6, 30, $im_height + 35, 'Conferences Scheduled', $red);
imagestring ($im, 6, 30, $im_height + 50, 'Conferences Held', $blue);

if ($max > 0)
  {
    $vscale = ($im_height - 20) / $max;
    for ($i = 0; $i < $max; $i++)
      {
	$str = ($max - $i) < 10 ? ' ' . ($max - $i) : $max - $i;
	imagestring ($im, 4, 2, ($vscale / 2) + ($i * $vscale), $str, $green);
	imageline ($im, 20, 20 + ($i * $vscale), $im_width,
		   20 + ($i * $vscale), $black);
      }

    for ($t = $first; $t <= $last; $t++)
      {
	if ($type == 'monthly')
	  $str = $t;
	else if ($type == 'yearly')
	  $str = $months[$t - 1];
	else
	  {
	    if ($t == 0)
	      $str = '12A';
	    elseif ($t == 12)
	      $str = '12P';
	    elseif ($t < 12)
	      $str = $t . 'A';
	    else
	      $str = ($t - 12) . 'P';
	  }

	imagestring ($im, 2, $hpos + 3, $im_height - 12, $str, $green);
	if ($count[$t] > 0)
	  {
	    $vpos = $im_height - ($count[$t] * $vscale);
	    imagerectangle ($im, $hpos, $vpos, $hpos + 15, $im_height - 15,
			    $black);
	    imagefilledrectangle ($im, $hpos, $vpos, $hpos + 15,
				  $im_height - 15, $red);
	  }

	if ($used_count[$t] > 0)
	  {
	    $vpos = $im_height - ($used_count[$t] * $vscale);
	    imagefilledrectangle ($im, $hpos, $vpos, $hpos + 15,
				  $im_height - 15, $blue);
	  }

	$hpos += $hinc;
      }
}
 else
   imagestring ($im, 10, $im_width / 3, $im_height / 2,
		'No Conferences Held or Scheduled', $red);

imagepng ($im);
imagedestroy ($im);
?>
