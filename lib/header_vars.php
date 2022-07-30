<?php
$gui_title = GUI_TITLE;
$gui_icon = GUI_ICON;
if (isset ($_SESSION) && $_SESSION['auth'])
  $array = array (_("Home"), "" => array ());
else
  $array = array (_("Login"));

if (!defined ('AUTH_TYPE') || (isset ($_SESSION) && $_SESSION['auth']))
  {
    $conf_sel = count ($array);
    $conf_section = "section$conf_sel";
    $sched = _('Conferences');
    $array[$sched] = array ('New', 'Current', 'Future', 'Past', 'Delete',
			    'Clone');
    $conf_add_sel = 0;
    $conf_current_sel = 1;
    $conf_future_sel = 2;
    $conf_past_sel = 3;
    $conf_delete_sel = 4;
    $conf_clone_sel = 5;
    $conf_add_section = "section$conf_sel$conf_add_sel";
    $conf_delete_section = "section$conf_sel$conf_delete_sel";
    $conf_past_section = "section$conf_sel$conf_past_sel";
    $conf_current_section = "section$conf_sel$conf_current_sel";
    $conf_future_section = "section$conf_sel$conf_future_sel";
    $conf_clone_section = "section$conf_sel$conf_clone_sel";

    $user_sel = count ($array);
    $user_section = "section$user_sel";

    if (AUTH_TYPE == 'sqldb')
      {
	if ($_SESSION['privilege'] == 'Admin'
	    && !defined ('AUTO_CREATE_DOMAIN'))
	  {
	    $array[_('Users')] =  array (_('Update Users'), _('Add User'));
	    $user_update_sel = "0";
	    $user_add_sel = "1";
	    $user_add_section = "section$user_sel$user_add_sel";
	    $user_update_section = "section$user_sel$user_update_sel";
	  }
	else
	  {
	    array_push ($array, _('Users'));
	    $user_update_section = "section$user_sel";
	  }
      }

    $report_sel = count ($array);
    $report_section = "section$report_sel";
    $array[_('Reports')] = array (_('Yearly'), _('Monthly'), _('Daily'));
    $report_yearly_sel = 0;
    $report_monthly_sel = 1;
    $report_daily_sel = 2;
    $report_yearly_section = "section$report_sel$report_yearly_sel";
    $report_monthly_section = "section$report_sel$report_monthly_sel";
    $report_daily_section = "section$report_sel$report_daily_sel";

    if ($_SESSION['auth'] && !defined ('AUTO_CREATE_DOMAIN'))
      {
	$logoff_sel = count ($array);
	$logoff_section = "section$logoff_sel";
	array_push ($array, _('Log-off'));
      }
  }

if (!isset ($s) || !strlen ($s))
{
  $section = 'section0';
  $s = 0;
 }

elseif (!isset ($section) || $section != 'section99')
  $section = "section$s$t";

$hints = array ('Home' => 'Return to main page',
		'Conferences' => 'Create, modify, or view conferences',
		'New' => 'Create a new conference or series of conferences',
		'Delete' => 'Delete one or more conferences',
		'Past' => 'View participants or listen to recordings of previous conferences',
		'Current' => 'View and monitor current conferences',
		'Future' => 'View and modify upcoming conferences',
		'Clone' => 'Make an identical copy of a conference',
		'Users' => 'Update or delete a user',
		'Update Users' => 'Update or delete a user',
		'Add User' => 'Add a new user',
		'Reports' => 'View conference system utilization',
		'Yearly' => 'View utilization report for a year by month',
		'Monthly' => 'View utilization report for a month by day',
		'Daily' => 'View utilization report for a day by hour');

$racine = 'index.php';
?>
