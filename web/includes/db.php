<?php
extract(parse_url($_ENV["DATABASE_URL"]));
pg_connect("user=$user password=$pass host=$host dbname=".substr($path, 1));

session_set_cookie_params(1209600);
session_start();
?>
