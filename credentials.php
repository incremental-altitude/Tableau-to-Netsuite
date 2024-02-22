<?php

// TABLEAU CREDS
$username ="tableau-login-email@example.com";
$password = "****";
$site_url ="tableau-site-url-example";


$parameters= "?filter.status=All&";
//$host = 'https://company-name.rz-ops.com/api/3.4/auth/signin';
$host = 'https://tableau.rz-ops.com/api/3.4/auth/signin';
$tableau_site_id = '********';
$tableau_view_id ='*********';
/*echo $host.'<br>'; */


// NETSUITE CREDS
$config = array(
   // required -------------------------------------
   "endpoint" => "2019_1",
   "host"     => "https://webservices.netsuite.com",
   "role"     => "3",
   "account"  => "company account id -numeric ",
   "app_id"   => "netsuite app id",
   "consumerKey"    => "*****",
   "consumerSecret" => "*****",
   "token"          => "*****",
   "tokenSecret"    => "*****",
   // optional -------------------------------------
);
?>
