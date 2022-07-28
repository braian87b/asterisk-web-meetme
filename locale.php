<?php

// putenv("LANG=ru_RU");
// $locale = 'ru_RU';

putenv("LANG=en_US");
$locale = 'en_US';

setlocale(LC_ALL, $locale);

// Specify location of translation tables
bindtextdomain("messages", "./locale");

// Choose domain
textdomain("messages");

bind_textdomain_codeset("messages", 'UTF-8');
