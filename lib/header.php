<?php
include 'header_vars.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<title><?php echo $gui_title ?> Conference Scheduler</title>
<meta http-equiv="X-UA-Compatible" content="IE=7">
<meta http-equiv="Content-Type" content="text/html">
<link rel="stylesheet" href="/css/jquery-ui-1.10.3.custom.min.css" />
<link rel="stylesheet" href="/css/jquery-ui-timepicker-addon.css" />
<link rel="stylesheet" href="/css/main.css" type="text/css" />
<link rel="stylesheet" href="css/meetme.css" type="text/css" />
<!--[if IE 6]>
<style>
#main { width: 80% }
</style>
<![endif]-->
<script src="/js/jquery-1.9.1.min.js"></script>
<script src="/js/jquery-ui-1.10.3.custom.min.js"></script>
<script src="/js/jquery-ui-timepicker-addon.js"></script>
<script src="/js/jquery.cookie.js"></script>
<script src="/js/jquery.browser.js"></script>
<script src="/js/jquery.tablescroll.js"></script>
<script src="/js/functions.js"></script>
<script src="/js/jstz.js"></script>
<script>
$(function () {
$.cookie ('tz', jstz.determine ().name ());
setupUI ($('body'));
});
</script>
</head>
<body>
  <div id="header">
      <img src="images/<?php echo $gui_icon ?>" alt="<?php echo $gui_title ?>">
      <span>Conference Scheduler</span>
  </div>
