<?php

include(dirname(__FILE__) . "/../locale.php");

//Email text
function email_body($confDesc, $confOwner, $confno, $pin, $starttime, $endtime, $maxusers, $recurPrd, $encode)
{
    $starttime = strtotime($starttime);
    $endtime = strtotime($endtime);

    if (use24h()) {
        $starttime = date("l d.m.Y H:i:s", $starttime);
        $endtime = date("l d.m.Y H:i:s", $endtime);
    } else {
        $starttime = date("l M d, Y h:i:s A", $starttime);
        $endtime = date("l M d, Y h:i:s A", $endtime);
    }

    $local_phone = LOCAL_PHONE;
    $local_support = LOCAL_SUPPORT;
    $srv_phone = PHONENUM;

    if ($pin == "")
        $pin = "No password.";

    if ($encode) {
        echo rawurlencode(_("Conference Name") . ":          $confDesc \n");
        echo rawurlencode(_("Conference Owner") . ":         $confOwner \n");
        echo rawurlencode(_("Conference ID") . ":            $confno \n");
        echo rawurlencode(_("Conference Password") . ":      $pin \n");
        echo rawurlencode(_("Start Date and Time") . ":      $starttime \n");
        echo rawurlencode(_("End Date and Time") . ":        $endtime \n");
        echo rawurlencode(_("Participants") . ":             $maxusers \n");
        echo rawurlencode(_("Recurrence Information") . ":   $recurPrd \n");
        echo rawurlencode("-------------------------------------------------- \n");
        echo rawurlencode(_("Dial In Info") . " : \n");
        echo rawurlencode(_("The conference call can be accessed by calling") . " $srv_phone.  \n");
        echo rawurlencode(_("Please contact") . " $local_support " . _("at") . " $local_phone " . _("for assistance") . ". \n");
    } else {
        echo "\n";
        echo _("Conference Name") . ":          $confDesc \n";
        echo _("Conference Owner") . ":         $confOwner \n";
        echo _("Conference ID") . ":            $confno \n";
        echo _("Conference Password") . ":      $pin \n";
        echo _("Start Date and Time") . ":      $starttime \n";
        echo _("End Date and Time") . ":        $endtime \n";
        echo _("Participants") . ":             $maxusers \n";
        echo _("Recurrence Information") . ":   $recurPrd \n";
        echo "-------------------------------------------------- \n";
        echo _("Dial In Info") . " : \n";
        echo _("The conference call can be accessed by calling") . " $srv_phone.  \n";
        echo _("Please contact") . " $local_support " . _("at") . " $local_phone " . _("for assistance") . ". \n";
    }
}
