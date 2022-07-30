<?php
$locale = 'en_US';

if (!array_key_exists ('HTTP_ACCEPT_LANGUAGE', $_SERVER))
  return;

$languages = ($_SERVER['HTTP_ACCEPT_LANGUAGE']);
$languages = str_replace (' ', '', $languages);
$languages = str_replace ('-', '_', $languages);
$languages = explode (',', $languages);

foreach ($languages as $temp)
{
  $temp = substr ($temp, 0, 5);
  $trans = "locale/$temp/LC_MESSAGES/messages.mo";

  if ($temp == 'en_us' || file_exists ($trans))
    {
      $locale = $temp;
      break;
    }
}

setlocale (LC_ALL, $locale);
bindtextdomain ('messages', './locale');
textdomain ('messages');
?>
