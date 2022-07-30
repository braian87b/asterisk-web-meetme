<?php
include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';
include 'locale.php';

session_start ();
if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

getpost_ifset (array ('DeletebyId', 'sens', 'order', 'current_page',
		      'confno'));

$BOOK_TABLE_NAME = DB_TABLESCHED;
$CDR_TABLE_NAME = DB_TABLECDR;

$TABLE_COL = array ();

$TABLE_COL[] = array ('Conference #', 'confno', 'center', 'SORT', '10');
$TABLE_COL[] = array ('Conference Name', 'confDesc', 'center', 'SORT', '10');
$TABLE_COL[] = array ('Start Time', 'starttime', 'center', 'SORT',
		      '30');
$TABLE_COL[] = array ('End Time', 'endtime', 'center', '', '30');
if ($_SESSION['privilege'] != 'User')
  $TABLE_COL[] = array ('Owner', 'confOwner', 'center', 'Sort', '4');

// This Variable store the argument for the SQL query
$FG_QUERY = 'confno, confDesc, starttime, endtime, confOwner, bookId';

$LINES_PER_PAGE = 1000;

// Number of column in the html table
$NB_TABLE_COL = count ($TABLE_COL);

$FG_TOTAL_TABLE_COL = $NB_TABLE_COL;

//This variable define the Title of the HTML table
$FG_HTML_TABLE_TITLE = _('All Conferences');

if (!isset ($order) || !isset ($sens))
  {
    $order = 'starttime';
    $sens  = 'DESC';
}

if (isset ($DeletebyId))
  foreach ($DeletebyId as $i)
    delete_conf ($db, $i);

$FG_CLAUSE = '';
$USER_CLAUSE = '';
if (isset ($confno) && $confno)
  $FG_CLAUSE = "(confno LIKE '%$confno%' OR confDesc LIKE '%$confno%')";

// Look only for conferences user is owner of.
if (defined('AUTH_TYPE') && $_SESSION['privilege'] == "User")
  {
    if (AUTH_TYPE == 'adLDAP')
      $USER_CLAUSE = "confOwner='$_SESSION[userid]'";
    else
      $USER_CLAUSE = "clientId='$_SESSION[clientid]'";
  }

$FG_CLAUSE = merge_where_clauses ($FG_CLAUSE, $USER_CLAUSE);

if (!isset ($current_page))
  $current_page = 0;

$nb_record = $db->getOne ("SELECT COUNT(*) FROM $BOOK_TABLE_NAME $FG_CLAUSE");

$record_start = intval ($current_page * $LINES_PER_PAGE);

$query = "SELECT $FG_QUERY FROM $BOOK_TABLE_NAME $FG_CLAUSE ORDER BY $order $sens LIMIT $LINES_PER_PAGE OFFSET $record_start";
$result = $db->query ($query);

$i = 0;
while ($row = $result->fetchRow ())
  $list[$i++] = $row;

$FG_HTML_TABLE_TITLE .= ' (' . count ($list) . ')';
$num_pages = intval (($nb_record - 1) / $LINES_PER_PAGE) + 1;
$gotnow = false;
?>
<script language="javascript">
$(function () { setupUI ($('#deleteResult')); } );
</script>
<!-- ** ** ** ** ** Part to display the conference  ** ** ** ** ** -->
<?php if (!is_array ($list) || !count ($list)) {
    echo '<center><h1>No conferences found.</h1></center>';
    exit;
  } ?>

<FORM METHOD="POST" id="deleteConf" onSubmit="return confirmDelete ('<?php echo $_SERVER['PHP_SELF']?>?order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo $current_page ?><?php if (isset ($confno)) echo "&confno=$confno";?>')">
<div class="bar-status">
    <div class="listheader btl btr"><?php echo $FG_HTML_TABLE_TITLE; ?></div>
      <TABLE class="data">
	<THEAD>
          <TR class="color1"> 
    	    <?php for ($i = 0; $i < $NB_TABLE_COL; $i++){ ?>
	      <TH align="<?php echo $TABLE_COL[$i][2]; ?>"> 
		<center><strong> 
		<?php if (strtoupper ($TABLE_COL[$i][3]) == 'SORT'){?>
                  <SPAN title="Sort by <?php echo $TABLE_COL[$i][0]; 
		    if ($order == $TABLE_COL[$i][1] && $sens == 'ASC')
		      echo ' in descending order';
		    else if ($order == $TABLE_COL[$i][1] && $sens == 'DESC')
		      echo ' in ascending order';?>">
		  <a href="javascript:void(0)" onClick="dynamicLoad ('#deleteResult', '<?php echo $_SERVER['PHP_SELF']."?current_page=$current_page&order=".$TABLE_COL[$i][1]."&sens="; if ($order == $TABLE_COL[$i][1] && $sens == 'ASC') echo 'DESC'; else echo 'ASC'; if (isset ($confno)) echo "&confno=$confno"; ?>')">
                  <span class="liens">
		  <?php }
                    echo $TABLE_COL[$i][0]; 
                    if ($order == $TABLE_COL[$i][1] && $sens == 'ASC') {?>
                      &nbsp;<img src="images/icon_up_12x12.gif"> 
                      <?php } elseif ($order == $TABLE_COL[$i][1] && $sens == 'DESC'){?>
                      &nbsp;<img src="images/icon_down_12x12.gif"> 
                  <?php }?>
                  <?php  if (strtoupper ($TABLE_COL[$i][3]) == 'SORT'){?></span></span></a><?php }?>
                  </strong></center></TH>
		  <?php } ?>
		</TR></THEAD><TBODY>
		<?php
		  $line_number = -1;
		  foreach ($list as $recordset)
		    { 
		      $trid = '';
		      if ($order == 'starttime' && !$gotnow)
			{
			  if (($sens == 'DESC'
			       && strtotime ($recordset[2]) < time ())
			      || ($sens == 'ASC'
				  && strtotime ($recordset[2]) > time ()))
			    {
			      $trid = " id='now'";
			      $gotnow = true;
			    }
			}

		      $line_number++;
		      if ($recordset[4] == '0')
			$recordset[4] = 'UNL';
		      if ($recordset[0] == '2') { ?>
		        <TR class="coloradmin">
		      <?php } else { ?>
		        <TR class="color<?php echo $line_number%2;?>"<?php echo $trid; ?>>
		      <?php }
		      for ($i = 0; $i < $NB_TABLE_COL; $i++)
			{
			  $record_display = $recordset[$i];
			  if ($i == 2 || $i ==3)
			    $record_display = display_date ($record_display);
			  if ($i == 0) { ?> 
			    <TD align="<?php echo $TABLE_COL[$i][2]; ?>">
			    <SPAN title="Delete conference <?php echo $record_display; ?>">
			    <INPUT TYPE=CHECKBOX NAME="DeletebyId[]" ID="delete<?php echo $line_number?>" VALUE="<?php print $recordset[5] ?>" onclick="toggleDelete (this)"><label for="delete<?php echo $line_number?>"><?php echo stripslashes($record_display); ?></label></SPAN></TD>
			     <?php } else { ?>
                 	    <TD align="<?php echo $TABLE_COL[$i][2]; ?>" class="del_<?php echo $TABLE_COL[$i][1]?>"><?php echo stripslashes($record_display); ?></TD>
			<?php } } ?>	
		      </TR>
		<?php
			} ?>
              </TBODY>
            </TABLE>
          <div class="listheader bbl bbr footer"> 
                    &nbsp;<?php if ($current_page > 0){?>
		    <span title="First page">
		    <a class="NP" href="javascript:void(0)" onClick="dynamicLoad ('#deleteResult', '<?php echo $_SERVER['PHP_SELF']."?order=$order&sens=$sens&current_page=0"; ?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&lt;&lt;&nbsp;</a></span>
		    <span title="Previous page">
		    <a class="NP" href="javascript:void(0)" onClick="dynamicLoad ('#deleteResult', '<?php echo $_SERVER['PHP_SELF']."?order=$order&sens=$sens&current_page=" . ($current_page-1); ?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&lt;&nbsp;</a></span>
                    <?php }?>
		    <?php if ($num_pages > 1) { ?>
                    Page <?php echo ($current_page+1) . " of " . $num_pages; } ?> 
                    <?php if ($current_page<$num_pages-1){?>
		    <span title="Next page">
                    <a class="NP" href="javascript:void(0)" onClick="dynamicLoad ('#deleteResult', '<?php echo $_SERVER['PHP_SELF']?>?order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo ($current_page+1)?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&nbsp;&gt;&nbsp;</a></span>
		    <span title="Last page">
                    <a class="NP" href="javascript:void(0)" onClick="dynamicLoad ('#deleteResult', '<?php echo $_SERVER['PHP_SELF']?>?order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo $num_pages-1?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&gt;&gt;</a></span>
                    <?php }?>
		    </div>
      <br/><span title="Delete selected conferences."><center>
      <INPUT TYPE="Submit" NAME="DeleteNow" VALUE="Delete Selected"></center></span>
</FORM>
