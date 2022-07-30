<?php
$url = 'https://www.googleapis.com/oauth2/v3/token';
$content = array ('code' => 'xxx',
		  'client_id' => 'xxx',
		  'client_secret' => 'xxx',
		  'grant_type' => 'authorization_code',
		  'redirect_uri' => 'http://localhost');
$options = array ('http' =>
		  array ('method'  => 'POST',
			 'content' => json_encode ($content),
			 'header'
			 =>  ("Content-Type: application/json\r\n" )));

$context  = stream_context_create ($options);
$result = json_decode (file_get_contents ($url, false, $context), true);
print_r ($http_response_header);
print_r ($result);

?>
