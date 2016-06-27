<?php
include_once(dirname(__DIR__)."/includes/db.php");

$name = $_POST["name"];
echo pg_query("SELECT EXISTS(SELECT 1 FROM recipes WHERE name = '$name')");

//echo $_POST["name"];
//echo implode($_POST["ingrArray"]);
//echo implode($_POST["instArray"]);

?>
