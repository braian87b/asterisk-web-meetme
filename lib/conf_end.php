<?php
ob_implicit_flush (true);
set_time_limit (0);

include 'lib/defines.php';
include 'lib/functions.php';
include 'lib/database.php';
include '../../phpagi/phpagi-asmanager.php';
include '../../php/confdata.php';

define ('GRACE_PERIOD', 45);

date_default_timezone_set (TIMEZONE);
$now = getConfDate ();

/* Here we handle any conferences that are nearing their end or need
   to be ended.  We want to end conferences in case there are connections
   "stuck" in them, but we don't need to enforce a conference end time,
   so give a grace period of 45 minutes.  */

foreach (get_conf_list () as $conf)
  if ($conf['room'] > 300)
    {
      $trigger = getConfDate (time () + 3600);
      $almost = getConfDate (time () - 3600);
      $query = "SELECT adminpin, endtime FROM booking ";
      $query .= "WHERE confno=$conf[room] AND ";
      $query .= "endtime<='$trigger' AND endtime>'$almost' AND ";
      $query .= "starttime<='$now'";
      $result = $db->query ($query);
      while ($row = $result->fetchRow (DB_FETCHMODE_ASSOC))
	{
	  extract ($row);
	  $minutes
	    = intval ((strtotime ($endtime) - strtotime ($now) + 59) / 60);
	  $minutes += GRACE_PERIOD;
	  if ($minutes >= 10)
	    continue;
	    
	  $as = new AGI_AsteriskManager ();
	  $as->connect ();

	  $vars = $minutes > 0 ? "WTIME=$minutes" : 'WTIME=0';
	  if (strlen ($adminpin))
	    $vars .= ",PIN=$adminpin";

	  $as->Originate ("Local/$conf[room]@Conferences", 's',
			  'Conf_Warn', '1', NULL, NULL, NULL,
			  'END WARNING <200>', $vars, NULL, NULL);

	  if ($minutes <= 0)
	    {
	      sleep (9);
	      $callers = $conf['caller'];
	      if (!isset ($callers[0]))
		$callers = array (0 => $callers);

	      foreach ($callers as $caller)
		$as->send_request ('ConfbridgeKick',
				   array ('Channel' => $caller['channel'],
					  'Conference' => $conf['room']));
	    }

	  $as->disconnect ();
	}
    }
