<?php
include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include 'lib/phones.php';
include 'locale.php';

session_start ();
getpost_ifset (array ('add', 'fname', 'lname', 'userPass', 'userEmail',
		      'userAdmin', 's', 't', 'order', 'sens', 'current_page'));

if (defined ('AUTH_TYPE') && !isset ($_SESSION['auth']))
  exit;

// this variable specifie the debug type (0 => nothing, 1 => sql result,
// 2 => boucle checking, 3 other value checking)
$FG_DEBUG = 0;

// The variable Var_col would define the col that we want show in your table
// First Name of the column in the html page, second name of the field
$FG_TABLE_COL = array ();
$FG_TABLE_COL[] = array ('Email', 'email', 'center', 'SORT', '10');
$FG_TABLE_COL[] = array ('First Name', 'first_name', 'center', 'SORT', '10');
$FG_TABLE_COL[] = array ('Last Name', 'last_name', 'center', 'SORT', '10');
$FG_TABLE_COL[] = array ('Type', 'admin', 'center', 'SORT', '30');
$FG_TABLE_COL[] = array ('Phone', 'telephone', 'center', '', '30');

$FG_TABLE_DEFAULT_ORDER = 'email';
$FG_TABLE_DEFAULT_SENS = 'ASC';

// This Variable store the argument for the SQL query
$FG_QUERY = 'email,first_name,last_name,admin,id,id';
$FG_TABLE_NAME = DB_TABLEUSERS;

// The variable LIMITE_DISPLAY define the limit of record to display by page
$FG_LIMITE_DISPLAY = 1000;

// Number of column in the html table
$FG_NB_TABLE_COL = count ($FG_TABLE_COL);

//This variable will store the total number of column
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;

//This variable define the Title of the HTML table
$FG_HTML_TABLE_TITLE = 'Users and Participants';

if ($FG_DEBUG == 3)
  echo "<br>Table : $FG_TABLE_NAME - Col_query : $FG_COL_QUERY<br />";

if (!isset ($order) || $order == '')
  $order = $FG_TABLE_DEFAULT_ORDER;
if (!isset ($sens) || $sens == '')
  $sens  = $FG_TABLE_DEFAULT_SENS;
if (!isset ($current_page) || $current_page == '')
  $current_page = 0;

//get only a persons info

$FG_CLAUSE = '';
if (defined ('AUTH_TYPE') && ($_SESSION['privilege'] != 'Admin'))
  $FG_CLAUSE = "WHERE email='$_SESSION[userid]'";

if ($FG_DEBUG >= 1)
  var_dump ($FG_CLAUSE);

$nb_record = $db->getOne ("SELECT COUNT(*) FROM $FG_TABLE_NAME");
$record_start = intval ($current_page * $FG_LIMITE_DISPLAY);

$query = "SELECT $FG_QUERY FROM $FG_TABLE_NAME $FG_CLAUSE ORDER BY $order $sens LIMIT $FG_LIMITE_DISPLAY OFFSET $record_start";
$result = $db->query ($query);
$i = 0;
while ($row = $result->fetchRow ())
  {
    $phones = enumerate_phones ($db, $row[0], $row[4]);
    if (isset ($phones[0]))
      $row[4] = expand_phone ($phones[0][1]);
    else
      $row[4] = '';

    $list[$i++] = $row;
  }

if ($nb_record <= $FG_LIMITE_DISPLAY)
  $nb_record_max = 1;
else
  $nb_record_max = intval (($nb_record - 1) / $FG_LIMITE_DISPLAY) + 1;

if ($FG_DEBUG == 3)
  echo "<br>Nb_record : $nb_record";
if ($FG_DEBUG == 3)
  echo "<br>Nb_record_max : $nb_record_max";
?>
<!-- ** ** ** ** ** Part to display the users  ** ** ** ** ** -->
<script language="javascript">
$(function () { setupUI ($('#userEditResult')); } );
</script>
<?php  if (isset ($list) && is_array ($list)){ ?>
      <div class="bar-status">
          <div class="listheader btl btr"><?php echo $FG_HTML_TABLE_TITLE?></div>
		  	<TABLE class="data">
			<thead>
                <TR class="color1"> 
                  <?php 
			if (is_array ($list) && count ($list) > 0)
			  {
			    for ($i = 0; $i < $FG_NB_TABLE_COL; $i++)
			      { 
				?>				
                  <TH align=center>
                    <center><strong> 
                    <?php if (strtoupper($FG_TABLE_COL[$i][3])=="SORT"){?>
                    <SPAN title="Sort by <?php echo $FG_TABLE_COL[$i][0]; 
		    if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC")
			echo " in descending order";
		    else if ($order==$FG_TABLE_COL[$i][1] && $sens=="DESC")
			echo " in ascending order";?>">
		    <a href="javascript:void(0)" onClick="dynamicLoad ('#userEditResult', '<?php echo $_SERVER['PHP_SELF']."?s=$s&t=$t&current_page=$current_page&order=".$FG_TABLE_COL[$i][1]."&sens="; if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC"){echo"DESC";}else{echo"ASC";} if (isset ($confno)) echo "&confno=$confno"; ?>')"> 
                    <span class="liens"><?php } ?>
                    <?php echo $FG_TABLE_COL[$i][0]; ?> 
                    <?php if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC"){?>
                    &nbsp;<img src="images/icon_up_12x12.gif"> 
                    <?php }elseif ($order==$FG_TABLE_COL[$i][1] && $sens=="DESC"){?>
                    &nbsp;<img src="images/icon_down_12x12.gif"> 
                    <?php }?>
                    <?php if (strtoupper ($FG_TABLE_COL[$i][3]) == 'SORT'){?>
                    </span></span></a> 
                    <?php }?>
                    </strong></center></TH>
				   <?php } ?>
			    </tr></thead><tbody>
				<?php
				  	 $ligne_number = -1;
				  	 foreach ($list as $recordset)
			    		    { 
						 $ligne_number++;
				?>
					<?php if ($recordset[0] == '2'){ ?>
						<TR class="coloradmin">
					<?php }else{ ?>
               		 	<TR class="color<?php echo $ligne_number%2 ?>">
					<?php } ?>
					<?php for ($i = 0; $i < $FG_NB_TABLE_COL; $i++){ ?>
					<?php  $record_display = $recordset[$i];
				 		 ?>
					<?php if ($i == 0) { ?>
                 		 <TD align="<?php echo $FG_TABLE_COL[$i][2]?>">
		<SPAN title="Update <?php echo $recordset[0]?>"><a href="update_user.php?s=<?php echo $s?>&t=<?php echo $t?>&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo $current_page?>&uuid=<?php echo $recordset[5]?>">
		<?php echo stripslashes($record_display)?></span></a></TD>
				<?php } else { ?>
                                 <TD  align="<?php echo $FG_TABLE_COL[$i][2]?>"><?php echo stripslashes($record_display)?></TD>
				<?php } ?>		 
				   	<?php } ?>	
				   		 </TD>  
					</TR>
				<?php } } else{ echo 'No data found.'; } ?>
              </TBODY>
            </TABLE>
          <div class="listheader bbl bbr footer"> 
                    <?php if ($current_page>0){?>
		    <span title="First page">
		    <a class="NP" href="javascript:void(0)" onClick="dynamicLoad ('#userEditResult', '<?php echo $_SERVER['PHP_SELF']."?s=$s&t=$t&order=$order&sens=$sens&current_page=0"; ?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&lt;&lt;&nbsp;</a></span>
		    <span title="Previous page">
		    <a class="NP" href="javascript:void(0)" onClick="dynamicLoad ('#userEditResult', '<?php echo $_SERVER['PHP_SELF']."?s=$s&t=$t&order=$order&sens=$sens&current_page=" . ($current_page-1); ?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&lt;&nbsp;</a></span>
                    <?php }?>&nbsp;
		    <?php if ($nb_record_max > 1) { ?>
                    Page <?php echo ($current_page+1) . " of " . $nb_record_max; } ?> 
                    <?php if ($current_page<$nb_record_max-1){?>
		    <span title="Next page">
                    <a class="NP" href="javascript:void(0)" onClick="dynamicLoad ('#userEditResult', '<?php echo $_SERVER['PHP_SELF']."?s=$s&t=$t&order=$order&sens=$sens&current_page=" . ($current_page+1)?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&nbsp;&gt;&nbsp;</a></span>
		    <span title="Last page">
                    <a class="NP" href="javascript:void(0)" onClick="dynamicLoad ('#userEditResult', '<?php echo $_SERVER['PHP_SELF']."?s=$s&t=$t&order=$order&sens=$sens&current_page=" . ($nb_record_max-1)?><?php if (isset ($confno)) echo "&confno=$confno";?>')">&gt;&gt;</a></span>
                    <?php }?>
</div>
</div>
<?php }else{ ?>
<?php echo 'No users found.'; ?>
<?php } ?>
