<?php
if (isset($_POST["action"])) {
    include_once(dirname(__DIR__)."/includes/db.php");
    if ($_POST["action"] == "add_recipe" && isset($_SESSION["username"])) {
        $name = $_POST["name"];
        $user_id = $_SESSION["user_id"];
        $exists = pg_num_rows(pg_query(
            "SELECT 1 FROM recipes WHERE name = '$name' AND user_id = '$user_id' LIMIT 1"));
        if ($exists) {
            echo 1; //Error: Recipe exists
        } else {
            pg_query("INSERT INTO recipes (name, user_id) VALUES ('$name', '$user_id')");
            $id = pg_fetch_assoc(pg_query(
                "SELECT id FROM recipes WHERE name = '$name' and user_id = '$user_id'"))["id"];
            if ($_POST["ingrArray"]) {
                $pairs = [];
                foreach ($_POST["ingrArray"] as $ingr) {
                    array_push($pairs, "('$ingr', '$id', '$user_id')");
                }
                $pairs = implode(", ", $pairs);
                echo $pairs."\n";
                pg_query("INSERT INTO recipe_ingredients (name, recipe_id, user_id) VALUES $pairs");
            }
            if ($_POST["instArray"]) {
                $pairs = [];
                foreach ($_POST["instArray"] as $inst) {
                    array_push($pairs, "('$inst', '', '$id', '$user_id')");
                }
                $pairs = implode(", ", $pairs);
                echo $pairs."\n";
                pg_query("INSERT INTO recipe_instructions (instruction, number, recipe_id, user_id) VALUES $pairs");
            }
            echo 0; //Successfully added as id# $id
        }
    } else if ($_POST["action"] == "create_account") {
        $username = $_POST["username"];
        $exists = pg_num_rows(pg_query("SELECT 1 FROM users WHERE username = '$username'"));
        if ($exists) {
            echo 1; //Error: User already exists
        } else {
            $password = $_POST["password"];
            pg_query("INSERT INTO users (username, password) VALUES ('$username', '$password')");
            $_SESSION["username"] = $username;
            echo 0; //Successfully created account
        }
    }  else if ($_POST["action"] == "login") {
        $username = $_POST["username"];
        $password = $_POST["password"];
        $check = pg_query("SELECT id FROM users WHERE username = '$username' AND password = '$password'");
        if (pg_num_rows($check)) {
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = pg_fetch_assoc($check)["id"];
            echo 0; //Successfully logged in
        } else {
            echo 1; //Incorrect username and/or password
        }
    }
} else {
    echo var_dump($_POST);
}



//echo $_POST["name"];
//echo implode($_POST["ingrArray"]);
//echo implode($_POST["instArray"]);

?>
