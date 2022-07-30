<?php
include 'lib/defines.php';
include 'lib/functions.php';

session_start (); 
if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

getpost_ifset ('type');
include 'lib/header_vars.php';

if (isset ($_COOKIE['ReportNow']))
  $current = "$_COOKIE[ReportNow]";

if (!isset ($current) || strlen ($current) == 0)
  $current = date ('Y-m-d');

$currentTime = strtotime ($current);
$displayTime = date ($type == 'yearly' ? 'Y' : ($type == 'monthly'
						? 'F Y' : 'F j, Y'));
?>                  
<script language="javascript">
var curtype = '<?php echo $type ?>';
var st = 's=<?php echo $conf_sel?>&t=<?php echo $conf_past_sel?>';

$(function ()
  { setupUI ($('#reportResult'));
    changeReportType (true); });
</script>
<DIV ID="tip" CLASS="tip ui-tooltip"></DIV>
<b id="headerMsg"><?php print 'Total Conferences Scheduled and Held '. ($type == 'daily' ? 'on': 'in'); ?>&nbsp;</b>
<select NAME="year" id="year"  onChange="yearChange ()">
<?php $select_year = intval (date ('Y', $currentTime));
  $first_year = $select_year - 10;
  $last_year = min ($select_year + 10, intval (date ('Y')));
  for ($i = $last_year; $i >= $first_year; $i--)
    printf ('<OPTION %s VALUE=%d>%d', $i == $select_year ? 'SELECTED' : '',
	    $i, $i);
?>
</SELECT>
<input NAME="date" id="date" size=20 value="<?php echo $displayTime; ?>">
<input NAME="now" id="now" value="<?php echo $current;?>" style="display:none">
<br/>
<img src="report-gen.php?now=<?php echo $current ?>&type=<?php echo $type ?>" id="ReportPNG" border=1 usemap="#<?php echo $type?>Map">
<?php 
  echo '<map name="dailyMap">';
  for ($i = 0; $i <= 23; $i++)
    printf ("<area shape=\"rect\" coords=\"%d,20,%d,685\" onClick=\"downSelect ('hourly', '%02d')\" value=\"%d\">",
	    $i * 24 + 30, $i * 24 + 45, $i, $i);
  echo '</map>';
  echo '<map name="monthlyMap">';
  for ($i = 1; $i <= 31; $i++)
    printf ("<area shape=\"rect\" coords=\"%d,20,%d,685\" onClick=\"downSelect ('daily', '%02d')\" value=\"%d\">",
	    $i * 24 + 6, $i * 24 + 21, $i, $i); 
  echo '</map>';
  echo '<map name="yearlyMap">';
  for ($i = 1; $i <= 12; $i++)
    printf ("<area shape=\"rect\" coords=\"%d,20,%d,685\" onClick=\"downSelect ('monthly', '%02d')\" value=\"%d\">",
	    $i * 36 - 6, $i * 36 + 9, $i, $i); 
  echo '</map>';
?>
