<?php
include("./lib/defines.php");
include("./lib/functions.php");
//include ("locale.php");

getpost_ifset(array('confno', 'book', 'user', 'privilege'));

$_SESSION['userid'] = $user;
$_SESSION['privilege'] = $privilege;

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
    <title><?php echo GUI_TITLE; ?> <?php echo _("control"); ?></title>
    <!--<meta http-equiv="Content-Type" content="text/html">//-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <link rel="stylesheet" type="text/css">
    <style type="text/css" media="screen">
        @import url("css/content.css");
        @import url("css/docbook.css");
    </style>

    <script language="JavaScript" type="text/JavaScript">
        function MM_openBrWindow(theURL,winName,features) {
          window.open(theURL,winName,features);
        }
    </script>

    <script language="JavaScript" type="text/JavaScript">
        function getXmlHttp(){
        var xmlhttp;
        try {
            xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            try {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (E) {
            xmlhttp = false;
            }
        }
        
        if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
            xmlhttp = new XMLHttpRequest();
        }

        return xmlhttp;
        }
    </script>

    <script language="JavaScript" type="text/JavaScript">
        // voting javascript code from example
        function call_add(nM,nU,rX,tX,cN,bI) {
            // (1) create an object to request to the server
            var req = getXmlHttp()
            
            // (2)
            // span next to the button
            // it will display the progress
            var statusElem = document.getElementById(nU)

            req.onreadystatechange = function() {
                // onreadystatechange is activated when a server response is received

                if (req.readyState == 4) { 
                    // if the request has finished executing

                    statusElem.innerHTML = req.statusText // show status (Not Found, OK..)

                    if(req.status == 200) { 
                        // if the status is 200 (OK) - issue a response to the user
                        // Removed to not open a lot of windows
                        // alert("Server response: "+req.responseText);
                    }
                    // here you can add else with request error handling
                }
            }

            // (3) set connection address
            req.open('GET', 'call_operator_add.php?name='+nM+'&invite_num='+nU+'&action=quickcall&data='+cN+'&bookid='+bI+'&rx='+rX+'&tx='+tX, true);

            // request object prepared: address specified and onreadystatechange function created
            // to process the server response

            // (4)
            req.send(null);  // send a request

            // (5)
            statusElem.innerHTML = 'Expectation...' 
        }
    </script>

</head>

<body bgcolor="#acbdee">

    <?php
    include("./lib/database.php");
    include("./lib/addressbook.php");

    echo "<form method = \"post\"><input type = \"submit\" name = \"button1\" value = \"" . _("Invite all") . "\"><form>\n";

    $FG_CLAUSE = "WHERE listOwner='$user'";

    if ($listnum) {
        $LIST = "AND list_$listnum=1";
    } else {
        $LIST = "";
    }

    if ($_POST['button1']) {
        $query1 = "SELECT SQL_CALC_FOUND_ROWS `id`,`name`,`surname`,`number`,`rx`,`tx` FROM `addressbook` $FG_CLAUSE $LIST ORDER BY `name` ASC ";
        $result1 = $db->query($query1);
        while ($row1 = $result1->fetchRow()) {
            $row1[1] = iconv("cp1251", "UTF-8", preg_replace("/[^\x30-\x39\x41-\x5A\x61-\x7A\x20\x5F\xC0-\xFF\xA8\xB8]/", "", iconv("UTF-8", "cp1251", $row1[1])));
            $name = "$row1[1] $row1[2]";
            echo "<script>call_add(\x27$name\x27,\x27$row1[3]\x27,$row1[4],$row1[5],$confno,$book);</script>";
        }
    }
    ?>
</body>

</html>