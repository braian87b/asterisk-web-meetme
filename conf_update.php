<?php
include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include '../../php/confdata.php';
include 'locale.php';

session_start ();

include ('lib/header_vars.php');

getpost_ifset (array ('confno', 'current_page', 'view', 's', 't',
		      'order', 'sens'));

// this variable specifie the debug type (0 => nothing, 1 => sql
// result, 2 => boucle checking, 3 other value checking)
$FG_DEBUG = 0;

// The variable FG_TABLE_NAME define the table name to use
$FG_TABLE_NAME = DB_TABLESCHED;
$CDR_TABLE_NAME = DB_TABLECDR;

// The variable Var_col would define the col that we want show in your table
// First Name of the column in the html page, second name of the field
$FG_TABLE_COL = array ();

$FG_TABLE_COL[] = array ('Conf. #', 'confno', 'center', 'SORT', '7');
$FG_TABLE_COL[] = array ('Conference Name', 'confDesc','center', 'SORT', '10');
$FG_TABLE_COL[] = array ('Start Time', 'starttime', 'center', 'SORT', '30');
$FG_TABLE_COL[] = array ('End Time', 'endtime', 'center', '', '30');

if ($view == 'Past' || (strlen ($view) > 5 && substr ($view, 0, 5) == 'Hour:'))
  $FG_TABLE_COL[] = array ('Callers', 'maxUser', 'center', '', '4');

if ($_SESSION['privilege'] != 'User')
  $FG_TABLE_COL[] = array ('Owner', 'confOwner', 'center', 'SORT', '10');

$FG_TABLE_DEFAULT_ORDER = 'starttime';
$FG_TABLE_DEFAULT_SENS = 'DESC';

// This Variable stores the argument for the SQL query
$FG_QUERY = 'confno, confDesc, starttime, endtime, maxUser, bookId, pin, confOwner, adminpin, adminopts, opts, recordingfilename, recordingformat';
$CDR_QUERY = 'bookId';

// The variable LIMITE_DISPLAY define the limit of record to display by page
$FG_LIMITE_DISPLAY = 1000;

// Number of column in the html table
$FG_NB_TABLE_COL = count ($FG_TABLE_COL);

//This variable will store the total number of column
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;

if ($FG_DEBUG == 3)
  echo "<br>Table : $FG_TABLE_NAME  	- 	Col_query : $FG_QUERY";

if ( !isset ($order) || !isset ($sens))
  {
    $order = $FG_TABLE_DEFAULT_ORDER;
    $sens  = $FG_TABLE_DEFAULT_SENS;
  }

if (!isset ($current_page))
  $current_page = 0;

$now = getConfDate ();

$orig_view = $view;
if ($view == 'Clone')
  {
    $FG_HTML_TABLE_TITLE = _('All Conferences');
    $FG_CLAUSE = '';
    if (isset ($confno) && $confno)
      $FG_CLAUSE = "(confno LIKE '%$confno%' OR confDesc LIKE '%$confno%')";
  }

elseif ($view == 'Past')
  {
    $FG_HTML_TABLE_TITLE = _('Previous Conferences');
    $FG_CLAUSE = "endtime<='$now'";
    if (isset ($confno) && $confno)
      $FG_CLAUSE
	.= " AND (confno LIKE '%$confno%' OR confDesc LIKE '%$confno%')";
  }

elseif ($view == 'Current')
  {
    $FG_HTML_TABLE_TITLE = _('Current Conferences');
    $fuzzy_start = getConfDate (time () + 15*60);
    $fuzzy_end = getConfDate (time () - (45*60));
    $FG_CLAUSE = "starttime<='$fuzzy_start' AND endtime>='$fuzzy_end'";
    if (isset ($confno) && $confno)
      $FG_CLAUSE .= " AND confno='$confno'";
  }
elseif (strlen ($view) > 5 && substr ($view, 0, 5) == 'Hour:')
  {
    $hour = substr ($view, 5);
    $arr = explode (' ' , $hour);
    $time = strtotime ($arr[0]) + intval ($arr[1]) * 3600;
    $FG_HTML_TABLE_TITLE = 'Conferences on ' . date ('F j, Y \a\t ga', $time);
    $FG_CLAUSE = "starttime LIKE '$hour%'";
    if (isset ($confno) && $confno)
      $FG_CLAUSE .= " AND confno='$confno'";
    $view = 'Past';
  }
else
   {
     $FG_HTML_TABLE_TITLE = _('Scheduled Conferences');
     $FG_CLAUSE = "starttime>='$now'";
     if (isset ($confno) && $confno)
       $FG_CLAUSE
	 .= " AND (confno LIKE '%$confno%' OR confDesc LIKE '%$confno%')";
     $sens = 'ASC';
   }

//get only conferences user is owner of
if (defined ('AUTH_TYPE')) 
{
  $FG_USER = $_SESSION['userid'];
  if (!$FG_USER)
    exit;

  if($_SESSION['privilege'] == 'User')
    $client_clause = "confOwner='$FG_USER'";

  if ($FG_CLAUSE && isset ($client_clause))
    $FG_CLAUSE .= " AND $client_clause";
  elseif (!$FG_CLAUSE && isset ($client_clause))
    $FG_CLAUSE = $client_clause;

  if ($FG_CLAUSE)
    $FG_CLAUSE = "WHERE $FG_CLAUSE";
 }

$nb_record = $db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME $FG_CLAUSE");
$record_start = intval ($current_page * $FG_LIMITE_DISPLAY);

$query = "SELECT $FG_QUERY FROM $FG_TABLE_NAME $FG_CLAUSE ORDER BY $order $sens LIMIT $FG_LIMITE_DISPLAY OFFSET $record_start";
$result = $db->query ($query);

$list = array ();
$i = 0;
while ($row = $result->fetchRow ())
  $list[$i++] = $row;

$confs = get_conf_list ();
foreach ($confs as $room)
  if ($room['room'] >= 201 && $room['room'] <= 209)
    $list[$i++] = array ($room['room'], "Conference Room $room[room]",
			 '------', '------', 0, 0, '', '------', '', '', '',
			 '', '');

$FG_HTML_TABLE_TITLE .= ' (' . count ($list) . ')';
if ($FG_DEBUG >= 1)
  var_dump ($list);

if ($nb_record <= $FG_LIMITE_DISPLAY)
  $nb_record_max = 1;
else
  $nb_record_max = (intval (($nb_record - 1) / $FG_LIMITE_DISPLAY) + 1);

if ($FG_DEBUG == 3)
  {
    echo "<br>Nb_record : $nb_record";
    echo "<br>Nb_record_max : $nb_record_max";
    echo "<br>current_page : $current_page";
    echo "<br>Search clause : $FG_CLAUSE";
    echo "<br>Order clause : $order";
    echo "<br>Sense clause : $sens";
  }
?>

<!-- ** ** ** ** ** Part to display the conference  ** ** ** ** ** -->
<script language="javascript">
$(function () { setupUI ($('#updateResult')); } );
</script>
<?php if (isset ($list) && is_array ($list) && count ($list) > 0) { ?>
<div class="bar-status">
  <div class="listheader btl btr"><?php echo $FG_HTML_TABLE_TITLE; ?></DIV>
    <TABLE class="data">
       <THEAD>
                <TR class="color1">
                  <?php 
			if (is_array ($list) && count ($list) > 0)
			  {
			    for ($i = 0; $i < $FG_NB_TABLE_COL; $i++)
			      { ?>				
                  <TH align="<?php echo $FG_TABLE_COL[$i][2]; ?>"> 
                    <strong> 
                    <?php if (strtoupper ($FG_TABLE_COL[$i][3])=="SORT"){?>
                    <SPAN title="Sort by <?php echo $FG_TABLE_COL[$i][0]; 
		    if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC")
			echo " in descending order";
		    elseif ($order==$FG_TABLE_COL[$i][1] && $sens=="DESC")
			echo " in ascending order";?>">
		    <a href="javascript:void(0)" onclick="dynamicLoad ('#updateResult', '<?php echo $_SERVER['PHP_SELF']."?s=$s&t=$t&view=$orig_view&current_page=$current_page&order=".$FG_TABLE_COL[$i][1]."&sens="; if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC"){echo"DESC";}else{echo"ASC";} if (isset ($confno)) echo "&confno=$confno";
					echo "";?>')"> 
                    <span class="liens"><?php } ?>
                    <?php echo $FG_TABLE_COL[$i][0]; ?> 
                    <?php if ($order == $FG_TABLE_COL[$i][1] && $sens == "ASC"){?>
                    &nbsp;<img src="images/icon_up_12x12.gif"> 
                    <?php }elseif ($order == $FG_TABLE_COL[$i][1] && $sens == "DESC"){?>
                    &nbsp;<img src="images/icon_down_12x12.gif"> 
		<?php }
		if (strtoupper ($FG_TABLE_COL[$i][3]) == "SORT")
		  echo "</span></span></a>"; 
		?>
                    </strong></TH>
		<?php 
		}
		echo "</TR></THEAD><TBODY>"; 
		$ligne_number = -1;
		foreach ($list as $recordset)
		  { 
		    $adminopts = $recordset[9];
		    $ligne_number++;
		    if ($recordset[0] == "2")
		      { 
		?>
		<TR class="coloradmin">
			<?php 
			}
			else
			{ 
			?>
			<TR class="color<?php echo $ligne_number%2; ?>"> 
			<?php 
			}
			for ($i = 0; $i < $FG_NB_TABLE_COL; $i++)
			  {
			    $record_display = $recordset[$i];
			    if ($FG_TABLE_COL[$i][1] == 'confOwner')
			      $record_display = $recordset[7];
			    elseif ($FG_TABLE_COL[$i][1] == 'maxUser')
			      {
				$record_display = $recordset[4];
				if ($record_display == "0")
				  $record_display = "UNL";
			      }
			    elseif ($i == 2 || $i == 3)
			      $record_display = display_date ($record_display);
			    if ($i == 0)
			      { 
			?>
                 		 <TD align="<?php echo $FG_TABLE_COL[$i][2]; ?>">
		<?php 
			if ($view == "Current")
			{ 
		?>
                <span title="Control conference <?php echo $recordset[0]; ?>"><a href="index.php?s=1&t=0&confno=<?php echo $recordset[0]; ?>">
                <?php echo stripslashes ($record_display); ?></a></span></TD>
                                        <?php } elseif ($view == "Past"){ 
				    if (strchr ($adminopts, 'r') && strlen ($recordset[11]) && (file_exists ($recordset[11] . "." . $recordset[12])))
			{
				echo "<a href=\"javascript:void(0)\" onClick=\"window.open('play.php?confno=$recordset[0]&bookId=$recordset[5]', 'newWin', 'toolbar=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=350,height=100')\" class=\"image-link\">&nbsp;<img src=\"images/speaker.gif\" alt=\"Listen to this conference's recording\" border=0 style=\"{text-decoration: none;}\"></a>";
				echo "<span title=\"See participants in conference $recordset[0]\"><a href=\"javascript:void(0)\" onClick=\"dynamicLoad ('#cdrResult', 'conf_cdr.php?bookId=$recordset[5]&confno=$recordset[0]')\">";
			}
			else
			{
				echo "<span title=\"See participants in conference $recordset[0]\"><a href=\"javascript:void(0)\" onClick=\"dynamicLoad ('#cdrResult', 'conf_cdr.php?bookId=$recordset[5]&confno=$recordset[0]')\">";
			}
                 echo stripslashes ($record_display); 
		?></a></span></TD><?php } else { ?>
		<span title="<?php if ($view == 'Clone') echo "Clone"; else echo "Update"; ?> conference <?php echo $recordset[0]; ?>"><a href="index.php?s=<?php echo $conf_sel;?>&t=<?php echo $conf_add_sel; if ($view == 'Clone') echo '&clone=1'?>&bookId=
		<?php 
		echo "$recordset[5]\">" . stripslashes($record_display); ?></span></a>
		</TD>
								 
				<?php } } elseif (($FG_TABLE_COL[$i][1] == 'maxUser') && ($view == "Past")) { 
					$CDR_TABLE_CLAUSE = "bookId='$recordset[5]'";
                                        $cdr_count = $db->getOne("SELECT COUNT(*) FROM $CDR_TABLE_NAME WHERE $CDR_TABLE_CLAUSE");?>

                                 <TD align="<?php echo $FG_TABLE_COL[$i][2]; ?>"><?php echo $cdr_count ?></TD>
                                 <?php } else { ?>
                                        <TD align="<?php echo $FG_TABLE_COL[$i][2]; ?>"><?php echo stripslashes($record_display); ?></TD>
				<?php } ?><?php } ?></TD></TR>
				<?php } }else { echo "No data found!!!"; } ?>
              </TBODY></TABLE>
          <div class="listheader bbl bbr footer">
                    <?php if ($current_page>0){?>
		    <span title="First page">
		    <a class="NP" href="javascript:void(0)" onclick="dynamicLoad ('#updateResult', '<?php echo $_SERVER['PHP_SELF']."?s=1&t=0&view=$orig_view&order=$order&sens=$sens&current_page=0"; ?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&lt;&lt;&nbsp;</a></span>
		    <span title="Previous page">
		    <a class="NP" href="javascript:void(0)" onclick="dynamicLoad ('#updateResult', '<?php echo $_SERVER['PHP_SELF']."?s=1&t=0&view=$orig_view&order=$order&sens=$sens&current_page=" . ($current_page-1); ?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&lt;&nbsp;</a></span>
                    <?php }?>
		    <?php if ($nb_record_max > 1) { ?>
                    Page <?php echo ($current_page+1) . " of " . $nb_record_max; } ?> 
                    <?php if ($current_page<$nb_record_max-1){?>
		    <span title="Next page">
                    <a class="NP" href="javascript:void(0)" onclick="dynamicLoad ('#updateResult', '<?php echo $_SERVER['PHP_SELF']?>?s=1&t=0&view=<?php echo $orig_view?>&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo ($current_page+1)?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&nbsp;&gt;&nbsp;</a></span>
		    <span title="Last page">
                    <a class="NP" href="javascript:void(0)" onclick="dynamicLoad ('#updateResult', '<?php echo $_SERVER['PHP_SELF']?>?s=1&t=0&view=<?php echo $orig_view?>&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo $nb_record_max-1?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&gt;&gt;</a></span>
                    <?php } ?>
                  &nbsp;
</div>
</div>
<?php 
} else { ?>
<center><h1>No conferences <?php echo $view == 'Current' ? 'currently active' : 'found'?>.</h1></center>
<?php } ?>
</body>
</html>
