<?php

$calling_codes = array ();

function add ($name, $num)
{
  global $calling_codes;

  if (preg_match ('/[^\x20-\x7f]/', $name))
    return;

  $calling_codes[strtolower ($name)] = $num;
}

$codes = json_decode (file_get_contents ('countries.json'), true);
foreach ($codes as $code)
{
  if (count ($code['callingCode']) != 1)
    continue;

  $num = $code['callingCode'][0];
  add ($code['name']['common'], $num);
  add ($code['name']['official'], $num);
  foreach ($code['name']['native'] as $name)
    {
      add ($name['official'], $num);
      add ($name['common'], $num);
    }

  foreach (array ('cca2', 'cca3', 'ccn3', 'cioc') as $type)
    if (!is_numeric ($code[$type]))
      add ($code[$type], $num);

  foreach ($code['altSpellings'] as $spell)
    add ($spell, $num);
}

$calling_codes['england'] = $calling_codes['uk'];
ksort ($calling_codes);

$file = fopen ('countries.php', 'w');
fwrite ($file, "<?php\n\$country_codes = ");
fwrite ($file, var_export ($calling_codes, true));
fwrite ($file, ";\n?>\n");
fclose ($file);

include 'countries.php';
print_r ($country_codes);
?>
