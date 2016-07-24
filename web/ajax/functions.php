<?php
if (isset($_POST["action"])) {
    include_once(dirname(__DIR__)."/includes/db.php");

    if (isset($_SESSION["username"])) {
        $user_id = $_SESSION["user_id"];

        if ($_POST["action"] == "new-recipe") {
            if (!isset($_POST["name"]) or $_POST["name"] === "") {
                echo -1; //Error: Recipe name not set
                return;
            }
            $name = pg_escape_string($_POST["name"]);
            $exists = pg_num_rows(pg_query(
                "SELECT 1 FROM recipes WHERE name = '$name' AND user_id = '$user_id' LIMIT 1"));
            if ($exists) {
                echo 1; //Error: Recipe exists
            } else {
                pg_query("INSERT INTO recipes (name, user_id) VALUES ('$name', '$user_id')");
                $id = pg_fetch_assoc(pg_query(
                    "SELECT id FROM recipes WHERE name = '$name' and user_id = '$user_id'"))["id"];
                if (isset($_POST["ingrArray"])) {
                    $pairs = [];
                    foreach ($_POST["ingrArray"] as $ingr) {
                        $ingr = pg_escape_string($ingr);
                        array_push($pairs, "('$ingr', '$id', '$user_id')");
                    }
                    $pairs = implode(", ", $pairs);
                    pg_query("INSERT INTO recipe_ingredients (name, recipe_id, user_id) VALUES $pairs");
                }
                if (isset($_POST["instArray"])) {
                    $pairs = [];
                    foreach ($_POST["instArray"] as $key => $inst) {
                        $num = $key + 1;
                        $inst = pg_escape_string($inst);
                        array_push($pairs, "('$inst', '$num', '$id', '$user_id')");
                    }
                    $pairs = implode(", ", $pairs);
                    pg_query("INSERT INTO recipe_instructions (instruction, number, recipe_id, user_id) VALUES $pairs");
                }
                echo 0; //Successfully added as id# $id
            }

        } elseif ($_POST["action"] == "new-ingredients") {
            # code...
        }

    } elseif ($_POST["action"] == "create-account") {
        if (!isset($_POST["username"]) or $_POST["username"] === "") {
            echo -1; // Error: username not set
            return;
        }
        $username = pg_escape_string($_POST["username"]);
        $exists = pg_num_rows(pg_query("SELECT 1 FROM users WHERE username = '$username'"));
        if ($exists) {
            echo 1; //Error: User already exists
        } else {
            if (!isset($_POST["password"]) or $_POST["password"] === "") {
                echo -1; // Error: password not set
                return;
            }
            $password = pg_escape_string($_POST["password"]);
            pg_query("INSERT INTO users (username, password) VALUES ('$username', '$password')");
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = pg_fetch_assoc(pg_query("SELECT id FROM users WHERE username = '$username' AND password = '$password'"))["id"];
            echo 0; //Successfully created account
        }

    }  elseif ($_POST["action"] == "login") {
        $username = pg_escape_string($_POST["username"]);
        $password = pg_escape_string($_POST["password"]);
        $check = pg_query("SELECT id FROM users WHERE username = '$username' AND password = '$password'");
        if (pg_num_rows($check)) {
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = pg_fetch_assoc($check)["id"];
            echo 0; //Successfully logged in
        } else {
            echo 1; //Incorrect username and/or password
        }
    }

} elseif (isset($_GET["action"])) {
    include_once(dirname(__DIR__)."/includes/db.php");
    if (isset($_SESSION["username"])) {
        $user_id = $_SESSION["user_id"];
        if ($_GET["action"] == "edit-recipe") {
            $id = $_GET["id"];
            $result = pg_fetch_assoc(pg_query("SELECT name, mealtype FROM recipes WHERE user_id = $user_id AND id = $id"));
            $name = $result["name"];
            $mealtype = $result["mealtype"];

            $ingredients = array();
            $result = pg_query("SELECT * FROM recipe_ingredients WHERE user_id = $user_id AND recipe_id = $id");
            while ($row = pg_fetch_assoc($result)) {
                array_push($ingredients, $row["name"]);
            }

            $instructions = array();
            $result = pg_query("SELECT * FROM recipe_ingredients WHERE user_id = $user_id AND recipe_id = $id");
            while ($row = pg_fetch_assoc($result)) {
                array_push($instructions, $row["instruction"]);
            }

            $data = array(
                "name" => $name,
                "mealtype" => $mealtype,
                'ingredients' => $ingredients,
                'instructions' => $instructions
            );
            echo json_encode($data);
        }
    }
} else {
    echo var_dump($_POST);
}

?>
