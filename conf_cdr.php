<?php
include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';
include 'locale.php';

session_start ();
if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

getpost_ifset (array ('bookId', 'confno', 'order', 'sens'));

// The variable FG_TABLE_NAME define the table name to use
$FG_TABLE_NAME = DB_TABLECDR;

// The variable Var_col would define the col that we want show in your table
// First Name of the column in the html page, second name of the field
$FG_TABLE_COL = array ();

$FG_TABLE_COL[] = array ('Caller Name', 'CIDname', 'center', 'SORT', '30');
$FG_TABLE_COL[] = array ('Caller Number', 'CIDnum', 'center', 'SORT', '30');
$FG_TABLE_COL[] = array ('Duration', 'duration', 'center', 'SORT', '30');

$FG_TABLE_DEFAULT_ORDER = 'CIDname';
$FG_TABLE_DEFAULT_SENS = 'ASC';

// This Variable store the argument for the SQL query
$FG_COL_QUERY = 'CIDname, CIDnum, duration';

// Number of column in the html table
$FG_NB_TABLE_COL = count ($FG_TABLE_COL);

//This variable will store the total number of column
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;

//This variable define the Title of the HTML table
$FG_HTML_TABLE_TITLE = "Participants: $confno";

if (!isset ($order) || !isset ($sens))
  {
    $order = $FG_TABLE_DEFAULT_ORDER;
    $sens  = $FG_TABLE_DEFAULT_SENS;
  }

if (!isset ($current_page))
  $current_page = 0;

if (isset ($bookId))
  {
    $FG_TABLE_CLAUSE = "bookId='$bookId'";
    $query = "SELECT confDesc, startTime, endTime FROM booking WHERE $FG_TABLE_CLAUSE";
    $result = $db->query ($query);
    while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
      {
	extract ($row);
	$tmpTime = (strtotime ($startTime)) + get_tz_offset ();
	$startTime = date ('M j, Y: g:i a', $tmpTime);
	$tmpTime = (strtotime ($endTime)) + get_tz_offset ();
	$endTime = date ('g:i a', $tmpTime);
	$FG_HTML_TABLE_TITLE = "$confDesc ($startTime - $endTime) Participants";
      }
  }	

$i = 0;
$list = array ();
$query = "SELECT $FG_COL_QUERY FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE";
$result = $db->query ($query);
while ($row = $result->fetchRow ())
  $list[$i++] = $row;

$FG_HTML_TABLE_TITLE .= ' (' . count ($list) . ')';
for ($x = 0; $x < count ($list); $x++)
  {
    $phone = $list[$x][1];

    /* We get caller ID from our phone suppliers without any indication
     if it's domestic or international.  So we have to guess here.  */
    if (!ctype_alpha ($phone[0]) && strlen ($phone) == 10
	&& substr ($phone, 0, 2) != '33')
      $phone = "1$phone";

    $list[$x][1] = expand_phone ($phone);
    $dur = intval ($list[$x][2]);
    $hr = intval ($dur / 3600);
    $min = intval (($dur % 3600) / 60);
    $sec = intval ($dur % 60);
    $list[$x][2] = sprintf ('%d:%02d:%02d', $hr, $min, $sec);
  }

if ($order == 'CIDnum')
  $inum = 1;
elseif ($order == 'duration')
  $inum = 2;
 else
   $inum = 0;

$sorted_order = array ();
foreach ($list as $key => $val)
  $sorted_order[$key] = $val[$inum];

if ($sens == 'ASC')
  asort ($sorted_order);
 else
   arsort ($sorted_order);

$new_list = array ();
foreach ($sorted_order as $key => $val)
	array_push ($new_list, $list[$key]);

$list = $new_list;
?>
<div class="bar-status">
 <div class="listheader btl btr"><?php echo $FG_HTML_TABLE_TITLE; ?>
    <span class="closeButton">
        <BUTTON id="closeButton" onClick="$('#cdrHaveResult').hide (); setupUI ($('#updateResult'), 0)" NAME="Close" VALUE="Close"></span></div>
<?php if (is_array ($list) && count ($list)) { ?>
	     <TABLE>
	       <thead>
                <TR class="color1">
                  <?php for ($i = 0; $i < $FG_NB_TABLE_COL; $i++) { ?> 
                  <TH align=center>
                    <center><strong> 
                    <?php if (strtoupper($FG_TABLE_COL[$i][3])=="SORT"){?>
                    <SPAN title="Sort by <?php echo $FG_TABLE_COL[$i][0]?>"><a href="javascript:void(0)" onClick="dynamicLoad ('#cdrResult', '<?php echo $_SERVER['PHP_SELF']."?current_page=$current_page&confno=$confno&bookId=$bookId&order=".$FG_TABLE_COL[$i][1]."&sens="; 
 if ($sens=="ASC"){echo"DESC";}else{echo"ASC";} 
					echo "";?>')"> 
                    <span class="liens"><?php } ?>
                    <?php echo $FG_TABLE_COL[$i][0]; ?> 
                    <?php if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC"){?>
                    &nbsp;<img src="images/icon_up_12x12.gif"> 
                    <?php }elseif ($order==$FG_TABLE_COL[$i][1] && $sens=="DESC"){?>
                    &nbsp;<img src="images/icon_down_12x12.gif"> 
                    <?php }?>
                    <?php if (strtoupper($FG_TABLE_COL[$i][3])=="SORT"){?>
                    </span></span></a> 
                    <?php } ?>
                    </strong></center>
           </TH>
 		<?php } ?>
	   </tr></thead><tbody>
           <?php
			     $line_number = 1;
			     foreach ($list as $recordset)
			     { 
			       $line_number++; 
			       if ($recordset[0] || $recordset[1]){ ?>
               		 	<TR class="color<?php echo $line_number%2;?>">
			    <?php for ($i = 0; $i < $FG_NB_TABLE_COL; $i++){ ?>
			    <?php  $record_display = $recordset[$i]; ?>
		<TD align="<?php echo $FG_TABLE_COL[$i][2];?>">
		<?php echo htmlentities ($record_display); ?></TD>
 			<?php } } } ?>
		</TD>  
              </TR>
	      </tbody></table>
<?php }
else
  echo '<center>No callers found in conference.</center>'; ?>
<div class="listheader bbl bbr footer">&nbsp;</div>
<script>
$(function () { setupUI ($('#cdrHaveResult'));
    $('#cdrHaveResult').show ().find ('button'). button ({icons: {primary: 'ui-icon-close'}, text: false});
    setupUI ($('#updateResult'), $('#cdrHaveResult').height () + 25);
});
</script>
