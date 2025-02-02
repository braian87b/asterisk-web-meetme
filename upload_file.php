<?php
require './Classes/PHPExcel.php';
require_once './Classes/PHPExcel/IOFactory.php';
include("./lib/functions.php");
include("./lib/defines.php");
include("locale.php");

$debug = 0;

getpost_ifset(array('s', 'confno', 'book'));

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
        function getXmlHttp() {
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

        function call_add(nM,nU,cN,bI) {
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
                    statusElem.innerHTML = req.statusText // показать статус (Not Found, ОК..)
                    if(req.status == 200) {
                    // if the status is 200 (OK) - issue a response to the user
                        alert("Ответ сервера: "+req.responseText);
                    }
                // here you can add else with request error handling
                }
            }
            // (3) set connection address
            req.open('GET', 'call_operator_add.php?name='+nM+'&invite_num='+nU+'&action=quickcall&data='+cN+'&bookid='+bI, true);
            // request object prepared: address specified and onreadystatechange function created
            // to process the server response
            // (4)
            req.send(null);  // send a request
            // (5)
            statusElem.innerHTML = 'Expectation...'
        }
    </script>

    <?php

    if ($s == 1) {

        echo "<p>" . _("Please select xls file from your PC") . "</p>\n";
        echo '<form action="upload_file.php?s=2&confno=' . $confno . '&book=' . $book . '" method="post" enctype="multipart/form-data">' . "\n\t";
        echo '<label for="file">' . _("Filename") . ':</label>' . "\n\t";
        echo '<input type="file" name="file" id="file"><br>' . "\n\t";
        echo '<input type="submit" name="submit" value="' . _("Upload") . '">' . "\n</form>\n";
    } elseif ($s == 2) {
        $allowedExts = array("xls", "xlsx", "txt");
        $temp = explode(".", $_FILES["file"]["name"]);
        $extension = end($temp);
        if ((($_FILES["file"]["type"] == "application/vnd.ms-excel") || ($_FILES["file"]["type"] == "text/plain")) && ($_FILES["file"]["size"] < 1000000) && in_array($extension, $allowedExts)) {
            if ($_FILES["file"]["error"] > 0) {
                echo "Error: " . $_FILES["file"]["error"] . "<br>";
            } else {
                // http://adsanti.wordpress.com/2011/12/01/php-excel-import-to-mysql-using-phpexcel/
                $path = $_FILES["file"]["tmp_name"];
                $objPHPExcel = PHPExcel_IOFactory::load($path);
                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                    $worksheetTitle = $worksheet->getTitle();
                    $highestRow = $worksheet->getHighestRow();
                    $highestColumn = $worksheet->getHighestColumn();
                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                }
                $nrColumns = ord($highestColumn) - 64;

                if ($debug === 1) {
                    echo "File name: " . $_FILES["file"]["name"] . "<br>";
                    echo "File type: " . $_FILES["file"]["type"] . "<br>";
                    echo "The size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
                    echo "Saved in: " . $_FILES["file"]["tmp_name"] . "<br>";
                    echo "$worksheetTitle contains $nrColumns speakers and $highestRow lines";
                }

                echo '<table width="100%" cellpadding="3" cellspacing="0"><tr>';
                for ($row = 1; $row <= $highestRow; ++$row) {
                    echo '<tr>';
                    $val = array();
                    for ($col = 0; $col < $highestColumnIndex; ++$col) {
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        $val[] = $cell->getValue();
                        if (!is_numeric(str_replace(array('(', ')', '-', ' '), '', $val[$col]))) {
                            $translit = translit($val[$col]);
                        }
                        if (is_numeric(str_replace(array('(', ')', '-', ' '), '', $val[$col]))) {
                            $num = str_replace(array('(', ')', '-', ' '), '', $val[$col]);
                            echo "<td><a href=\"#\" onclick=\"call_add('$translit','$num','$confno','$book')\" type=\"button\" />" . $val[$col] . "</a><div align=\"center\" id=\"$num\"></td>";
                        } else {
                            echo '<td>' . $val[$col] . '</td>';
                        }
                    }
                    echo '</tr>';
                }
                echo '</table><br />' . "\n";
                echo '<input type="submit" value="' . _("Close window") . '" onClick="window.close()" />';
            }
        } else {
            echo _("Invalid file") . '<br /><input type="Submit" onClick="javascript:history.back()" value="' . _("Retry") . '">';
        }
    }
    ?>
    </body>

</html>