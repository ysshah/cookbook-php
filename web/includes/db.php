<?php
extract(parse_url($_ENV["DATABASE_URL"]));
pg_connect("user=$user password=$pass host=$host dbname=".substr($path, 1));
?>
