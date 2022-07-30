<?php

define ('FSROOT', '/var/www/html/web-meetme/');
define ('LIBDIR', FSROOT . 'lib/');

//GUI title
define ('GUI_TITLE', 'XXXX');
define ('GUI_ICON', 'adacore_logo.gif');

$dialin_numbers = array ('US direct' => 'xxx'
			 'France direct' => 'xxx',
			 'UK direct' => 'xxx',
			 'Japan direct' => 'xxx',
			 'US toll-free' => 'xxx',
			 'Japan toll-free' => 'xxx',
			 'German toll-free' => 'xxx',
			 'extension' => '200');
define ('PBX_ICON', 'adacore_logo.gif');
define ('TIMEZONE', 'America/New_York');
define ('HOST_FOR_URL', 'asterisk.gnat.com');

// Interface to Google Calendar
define ('GCAL_RESOURCE', 'xxx');
define ('GCAL_ID', 'xxx');
define ('GCAL_SECRET', 'xxx');
define ('GCAL_REFRESH_TOKEN', 'xxx');

// Comment out the following lines to disable authentication
define ('AUTH_TYPE', 'sqldb'); // adLDAP or sqldb 
define ('ADMIN_GROUP', 'Domain Admins');
define ('AUTH_TIMEOUT', '3');	//Hours
include (LIBDIR.AUTH_TYPE . '.php');

define ('AUTO_CREATE_DOMAIN', 'adacore.com');
define ('AUTO_CREATE_DOMAIN_FUNCTION', 'ldap_get_email');
define ('AUTO_CREATE_VALID_GROUP', 'ada');
define ('AUTO_CREATE_MANAGER_GROUP', 'confmgr');
define ('AUTO_CREATE_ADMIN_GROUP', 'admin');

//Database tables
define ('DB_TABLECDR', 'cdr');
define ('DB_TABLESCHED', 'booking');
define ('DB_TABLEUSERS', 'user');

//Outcall defaults
define ('CHAN_TYPE', 'Local'); //Use Local to let dialplan decide which chan
define ('OUT_CONTEXT', 'Call_Conferences'); // Context to place the call from
define ('OUT_PEER', ''); // Use this if not using CHAN_TYPE Local
define ('OUT_CALL_CID', 'AdaCore <xxx>'); // Caller ID for Invites
define ('OUT_CALL_CID_EXT', 'Conferencing <200>'); // Caller ID for Invites

//Standard flags for Users and Admins
define ('SAFLAGS', 'aAosT');
define ('SUFLAGS', 'osT');

$Conf_Options = array (
array ('Moderated', 'd', 'All participants are initially muted'),
array ('Announce', 'I', 'Prompt caller for name and play it when they enter'),
array ('Count', 'c', 'Announce count of users when entering conference'),
array ('Quiet', 'q', 'Don\'t play enter and exit sounds'),
array ('Record', 'r', 'Make recording of conference'));

$User_Options = array (
array ('Initally Muted', 'm', 'Participants start out muted'),
array ('Listen Only', 'l', 'Participants can never speak, only listen'),
array ('Wait for Leader', 'w',
       'Participants cannot enter conference before leader'));

$Email_Options = array (
array ('Send', 's', 'Send email to participants', 'toggleEmail'),
array ('iCal&nbsp;&nbsp;&nbsp;&nbsp;', 'c',
       'Attach file to enter in iCal-compatible calendar'),
array ('Extra Text', 't', 'Include additional text', 'toggleMessage'),
array ('List Participants', 'l', 'Include list of participants in email'));

$Remind_Options = array (
array ('1 day&nbsp;', 'd', 86400),
array ('1 hour', 'h', 3600),
array ('10 minutes', 'm', 600));

//Change conference End Time on a 'End Now' click
define ('FORCE_END', 'YES');

$months = array ('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
		 'Sep', 'Oct', 'Nov', 'Dec');

$days = array ('31', '29', '31', '30', '31', '30', '31', '31', '30', '31',
	       '30', '31');

$recurLabel = array (_('Daily'), _('Weekly'), _('Bi-Weekly'));
$recurInterval = array ('86400', '604800', '1209600');

if (isset ($_COOKIE['tz']))
  date_default_timezone_set ($_COOKIE['tz']);
else
  date_default_timezone_set (TIMEZONE);
?>
