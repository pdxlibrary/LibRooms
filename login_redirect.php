<?php

$redirect = $_GET['redirect'];
$parsed = parse_url($redirect);
parse_str($parsed['query'],$query);
unset($query['redirect']);
$location = $parsed['path']."?".http_build_query($query);
//print("location: $location<br>\n");
//header("location: $location");
print("<META HTTP-EQUIV=Refresh CONTENT='0; URL=$location'>\n");

?>