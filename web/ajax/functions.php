<?php
include_once(dirname(__DIR__)."/includes/db.php");

$name = $_POST["name"];
$exists = pg_num_rows(pg_query(
    "SELECT 1 FROM recipes WHERE name = '$name' LIMIT 1"));

if ($exists) {
    echo "Error: Recipe exists.";
} else {
    pg_query("INSERT INTO recipes (name) VALUES ('$name')");
    $id = pg_fetch_assoc(pg_query(
        "SELECT id FROM recipes WHERE name = '$name'"))["id"];
    if ($_POST["ingrArray"]) {
        $pairs = [];
        foreach ($_POST["ingrArray"] as $ingr) {
            array_push($pairs, "('$ingr', '$id')");
        }
        $pairs = implode(", ", $pairs);
        echo $pairs."\n";
        pg_query("INSERT INTO recipe_ingredients (name, recipe_id) VALUES $pairs");
    }
    if ($_POST["instArray"]) {
        $pairs = [];
        foreach ($_POST["instArray"] as $inst) {
            array_push($pairs, "('$inst', '', '$id')");
        }
        $pairs = implode(", ", $pairs);
        echo $pairs."\n";
        pg_query("INSERT INTO recipe_instructions (instruction, number, recipe_id) VALUES $pairs");
    }
    echo "Successfully added as id# ".$id;
}

//echo $_POST["name"];
//echo implode($_POST["ingrArray"]);
//echo implode($_POST["instArray"]);

?>
