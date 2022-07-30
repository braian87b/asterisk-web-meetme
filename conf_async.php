<?php
include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/phones.php';
include 'lib/database.php';
include '../../php/confdata.php';

session_start (); 
if (defined('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

getpost_ifset (array ('confno', 'order', 'sens'));
if (!isset ($sens) || ($sens != 'ASC' && $sens != 'DESC'))
  $sens = 'ASC';

if (!isset ($confno))
  exit;

$locked = 0;
if (defined ('AUTH_TYPE') && $_SESSION['privilege'] != 'User'
	  && $confno >= 201 && $confno <= 209)
   $FG_HTML_TABLE_TITLE = "Conference Room $confno";

else
  {
    if (defined ('AUTH_TYPE') && $_SESSION['privilege'] == 'User')
      {
	$FG_USER = $_SESSION['clientid'];
	$FG_TABLE_CLAUSE = " clientId='$FG_USER' AND confno='$confno'";
	$FG_TABLE_NAME = DB_TABLESCHED;
	if (!($db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE")))
	  exit;
      }

    // Conference exists and user is owner -> get Data	
    $FG_QUERY = 'confDesc, endtime, starttime, adminopts, adminpin';
    $FG_TABLE_NAME = DB_TABLESCHED;
    $FG_HTML_TABLE_TITLE = "Conference Number $confno";

    $query = "SELECT $FG_QUERY FROM $FG_TABLE_NAME WHERE confno=$confno";
    $result = $db->query ($query);
    while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
      {
	$startTime = strtotime ($row['starttime']) + get_tz_offset ();
	$endTime = strtotime ($row['endtime']) + get_tz_offset ();
	$now = time ();
	if ($startTime < $now + 600 && $endTime > $now - 600)
	  {
	    $time = date ('g:ia', $endTime);
	    $endopt = $endTime < $now ? "ended" : "ends";
	    $FG_HTML_TABLE_TITLE
	      = "$row[confDesc] ($confno): $endopt at $time";
	    if (strchr ($row['adminopts'], "r"))
	      $FG_HTML_TABLE_TITLE .= ' (recording)';
	    
	    $has_admin = $row['adminpin'] != 0;
	  }
      }
    
    if (!isset ($startTime))
      exit;
  }

$FG_TABLE_COL = array ();
$FG_TABLE_COL[] = array ('Name', 'name', 'center', 'SORT', '50');
$FG_TABLE_COL[] = array ('Number', 'callerid', 'center', 'SORT', '50');
$FG_TABLE_COL[] = array ('Duration', 'duration', 'center', 'SORT', '12');
$FG_TABLE_COL[] = array ('Mode', 'mode', 'center', '', '30');
if (isset ($has_admin) && $has_admin)
  $FG_TABLE_COL[] = array ('Type', 'type', 'center', 'SORT', '7');

// Number of column in the html table
$FG_NB_TABLE_COL = count ($FG_TABLE_COL);

$FG_VOICE_RIGHT = true;
$FG_KICKOUT = true;

if (!isset ($order) || !$order)
  $order = $FG_TABLE_COL[0][1];

$callers = get_caller_list ($confno, $conf);
$locked = $conf['locked'];

$list = array ();
foreach ($callers as $caller)
{
  $hand = 0;
  if (isset ($caller['channel']))
    {
      $result = $db->query ("SELECT value FROM handup WHERE channel='$caller[channel]'");
      if (($row = $result->fetchRow()))
	$hand = $row[0];
    }

  $number = expand_phone ($caller['number']);
  $list[] = array (0 => $caller['name'], 1 => $number,
		   2 => sprintf ("%02d:%02d:%02d",
				 $caller['duration'] / 3600,
				 ($caller['duration'] / 60) % 60,
				 $caller['duration'] % 60),
		   3 => ($hand ? 'Hand up'
			 : ($caller['muted'] ? 'Muted'
			    : ($caller['talking'] ? 'Talking'
			       : 'Not talking'))),
		   4 => $caller['admin'] ? 'Admin' : 'Caller',
		   5 => (isset ($caller['usernum'])
			 ? $caller['usernum'] : $caller['channel']),
		   6 => ($hand ? 'hand'
			 : ($caller['muted'] ? 'muted'
			    : ($caller['talking'] ? 'talk' : false))));
}

function rstrcasecmp ($a, $b)
{
  return - strcasecmp ($a, $b);
}

if ($order == 'callerid')
  $inum = 1;
elseif ($order == 'duration')
  $inum = 2;
elseif ($order == 'type')
  $inum = 4;
else
  $inum = 0;

$sorted_order = array ();
foreach ($list as $key => $val)
  $sorted_order[$key] = $val[$inum];

uasort ($sorted_order, $sens == 'ASC' ? 'strcasecmp' : 'rstrcasecmp');

$new_list = array ();
foreach ($sorted_order as $key => $val)
  array_push ($new_list, $list[$key]);

$list = $new_list;

if ($locked)
  $FG_HTML_TABLE_TITLE .= ' (locked)';
?>
<!-- ** ** ** ** ** Part to display the conference user ** ** ** ** ** -->
<script>
$(function () { setupUI ($('#confdisplay')); });
</script>
          <div class="listheader leftheader buttoncontainer"> 
  	  <span class="conftitle"><B><?php echo $FG_HTML_TABLE_TITLE; ?></B></span>
  		  <span class="confbuttons">
		  <?php if (count($list) > 0 && !$locked) { ?>
		  <button name="Lock" onClick="conf_action ('lock', '<?PHP echo $confno; ?>', '')" ><B><SPAN title="Prevent more people from entering this conference">Lock</SPAN></B></button>
		  <?php } else if (count($list) > 0 && $locked) { ?>
		  <button name="Unlock" onClick="conf_action ('unlock', '<?PHP echo $confno; ?>', '')"><B><SPAN title="Allow more people to enter this conference">Unlock</SPAN></B></button>
    		  <?php } if (isset ($startTime)) { ?>
		  <button name="End" onClick="conf_action ('end', '<?PHP echo $confno; ?>', '')"><B><SPAN title="End this conference immediately">End Now</SPAN></B></button>
		  <button name="Extend" onClick="$.get ('conf_add.php', 's=1&t=0&Extend=1&confno=<?php echo $confno; ?>')"><B><SPAN title="Extend the duration of this conference by 15 minutes">Extend 15 mins</SPAN></B></button>
                  <?php } ?>
		  </span></div>
        <TABLE>
        <thead>
                <TR class="color1"> 
                  <?php
 			if (is_array ($list) && count ($list) > 0){
 			   for($i = 0; $i < $FG_NB_TABLE_COL; $i++){  ?>
                  <TH>
                    <center><strong> 
                    <?php if (strtoupper ($FG_TABLE_COL[$i][3]) == 'SORT'){?>
                    <SPAN title="Sort by <?php echo $FG_TABLE_COL[$i][1]?>"><a href=javascript:void(0) onClick="async_order = '<?php echo $FG_TABLE_COL[$i][1]?>'; async_sens = '<?php if ($sens == 'ASC') echo 'DESC'; else echo 'ASC' ?>'"> 
                    <span class="liens"><?php } ?>
                    <?php echo $FG_TABLE_COL[$i][0]; ?> 
                    <?php if ($order == $FG_TABLE_COL[$i][1] && $sens=='ASC'){?>
                    &nbsp;<img src="images/icon_up_12x12.gif"> 
                    <?php }elseif ($order == $FG_TABLE_COL[$i][1] && $sens == 'DESC'){?>

                    &nbsp;<img src="images/icon_down_12x12.gif"> 
                    <?php }?>
                    <?php  if (strtoupper ($FG_TABLE_COL[$i][3]) == 'SORT'){?>
                    </span></span></a> 
                    <?php }?>
                    </strong></center></TH>
				   <?php } ?>
				   <?php if ($FG_VOICE_RIGHT || $FG_KICKOUT){ ?>
                  		<TH></TH>                  
					<?php } ?>	
				</TR></thead><tbody>
				<?php
					 $ligne_number = -1;
				  	 foreach ($list as $recordset)
					 { 
						$ligne_number++;
				?>
					<?php if ($recordset[4] == 'Admin'){ ?>
						<TR class="coloradmin">
					<?php }else{ ?>
               		 	<TR class="color<?php echo $recordset[6] ? $recordset[6] : $ligne_number%2; ?>">
					<?php }
					  for ($i = 0; $i < $FG_NB_TABLE_COL; $i++){
					 $record_display = $recordset[$i];
							if ( is_numeric($FG_TABLE_COL[$i][4]) && (strlen($record_display) > $FG_TABLE_COL[$i][4])  ){
								$record_display = substr($record_display, 0, $FG_TABLE_COL[$i][4]-3)."";
				} ?>
                 		 <TD align="<?php echo $FG_TABLE_COL[$i][2]; ?>"><?php echo stripslashes($record_display); ?></TD>
				 		 <?php } ?>
	    	             <TD align="right" class="pad">
					<?php if (($FG_VOICE_RIGHT || $FG_KICKOUT )){ ?>
						 <?php if ($FG_VOICE_RIGHT){ ?>
						     &nbsp;&nbsp;&nbsp;
						 	<?php if ($recordset[3]=='Muted' || $recordset[3] == 'Hand up'){ ?>
				<button name="unmute" onClick="conf_action('unmute','<?PHP echo $confno; ?>', '<?PHP echo $recordset[5]; ?>')"><b><SPAN title="Unmute this participant">Unmute</SPAN></b></button>
							<?php }else{ ?>
				<button name="mute" onClick="conf_action('mute','<?PHP echo $confno; ?>', '<?PHP echo $recordset[5]; ?>')"><b><SPAN title="Mute this participant">Mute</SPAN></b></button>
							<?php } } ?>
						<?php if ($FG_KICKOUT){ ?>
				&nbsp;<button name="kick" onClick="conf_action('kick','<?PHP echo $confno; ?>', '<?PHP echo $recordset[5]; ?>', '<?PHP echo "$recordset[0] ($recordset[1])"; ?>')"><b><SPAN title="Evict this participant from the conference">Kick</SPAN></b></button>
						<?php } } ?>
				 </TD></TR>
				<?php } }else{ ?>
				<TD align="center"><h1>No participants are currently in this conference.</h1></td>
				<?php }?>
	      </TR>
              </TBODY>
            </TABLE>
          <div class="listheader bbl bbr footer">&nbsp;</div>
