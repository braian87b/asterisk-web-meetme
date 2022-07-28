<?php
include("./lib/defines.php");
include("./lib/functions.php");
//include ("locale.php");

getpost_ifset(array('confno', 'book'));

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
</head>

<body bgcolor="#acbdee">
    <center>
        Enter the participant's name and phone number
        <br>to invite you to the conference: <FORM action="./call_operator.php" method=post name=WMOutCall>
            <INPUT type="hidden" name="action" value="quickcall">
            <INPUT type="hidden" name="data" value="<?php echo $confno; ?>">
            <INPUT type="hidden" name="bookid" value="<?php echo $book; ?>">
            <table border="0">
                <tr>
                    <td align="right">Name: </td>
                    <td><INPUT type="text" name="name" value=""></td>
                </tr>
                <tr>
                    <td>Telephone: </td>
                    <td><INPUT type="text" name="invite_num" value=""></td>
                </tr>
                <tr>
                    <td colspan="2" align="center"><input type="submit" value="Call" /></td>
                </tr>
            </table>
            <center>
        </FORM>
        <script language="javascript">
            document.WMOutCall.invite_num.focus()
        </script>