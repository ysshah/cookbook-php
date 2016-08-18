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
            $mealType = pg_escape_string($_POST["mealType"]);
            $exists = pg_num_rows(pg_query(
                "SELECT 1 FROM recipes WHERE name='$name' AND user_id=$user_id LIMIT 1"));
            if ($exists) {
                echo 1; //Error: Recipe exists
            } else {
                pg_query("INSERT INTO recipes (name, mealtype, user_id) VALUES ('$name', '$mealType', '$user_id')");
                $id = pg_fetch_assoc(pg_query(
                    "SELECT id FROM recipes WHERE name = '$name' and user_id = '$user_id'"))["id"];
                if (isset($_POST["ingrArray"])) {
                    $pairs = [];
                    foreach ($_POST["ingrArray"] as $key => $ingr) {
                        $num = $key + 1;
                        $ingr = pg_escape_string($ingr);
                        array_push($pairs, "('$ingr', '$num', '$id', '$user_id')");
                    }
                    $pairs = implode(", ", $pairs);
                    pg_query("INSERT INTO recipe_ingredients (name, number, recipe_id, user_id) VALUES $pairs");
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

        } elseif ($_POST["action"] == "new-ingredient") {
            if (!isset($_POST["name"]) or $_POST["name"] === "") {
                echo -1; //Error: Recipe name not set
                return;
            }
            $name = pg_escape_string($_POST["name"]);
            $exists = pg_num_rows(pg_query(
                "SELECT 1 FROM ingredients WHERE ingredient='$name' AND user_id='$user_id' LIMIT 1"));
            if ($exists) {
                echo 1; //Error: Ingredient exists
            } else {
                $location = $_POST["location"];
                $purchase = ($_POST["purchase"] === "") ? "NULL" : "'".$_POST["purchase"]."'";
                $expiration = ($_POST["expiration"] === "") ? "NULL" : "'".$_POST["expiration"]."'";
                pg_query("INSERT INTO ingredients (ingredient, location, purchase, expiration, user_id) VALUES ('$name', '$location', $purchase, $expiration, '$user_id')");
                echo 0;
            }

        } elseif ($_POST["action"] == "edit-recipe") {
            $name = pg_escape_string($_POST["name"]);
            $mealtype = pg_escape_string($_POST["mealType"]);
            $recipe_id = $_POST["id"];
            pg_query("UPDATE recipes SET name='$name', mealtype='$mealtype' WHERE id=$recipe_id AND user_id=$user_id");

            if (isset($_POST["ingrArray"])) {
                $numIngr = pg_fetch_row(pg_query("SELECT COUNT(*) FROM recipe_ingredients WHERE recipe_id=$recipe_id AND user_id=$user_id"))[0];
                $pairs = [];
                foreach ($_POST["ingrArray"] as $key => $ingr) {
                    $num = $key + 1;
                    $ingr = pg_escape_string($ingr);
                    if ($num <= $numIngr) {
                        pg_query("UPDATE recipe_ingredients SET name='$ingr' WHERE number=$num AND recipe_id=$recipe_id AND user_id=$user_id");
                    } else {
                        array_push($pairs, "('$ingr', '$num', '$recipe_id', '$user_id')");
                    }
                }
                $newNumInst = count($_POST["ingrArray"]);
                if ($newNumInst > $numIngr) {
                    $pairs = implode(", ", $pairs);
                    pg_query("INSERT INTO recipe_ingredients (name, number, recipe_id, user_id) VALUES $pairs");
                } else if ($newNumInst < $numIngr) {
                    pg_query("DELETE FROM recipe_ingredients WHERE number>$newNumInst AND recipe_id=$recipe_id AND user_id=$user_id");
                }
            } else {
                pg_query("DELETE FROM recipe_instructions WHERE recipe_id = $recipe_id AND user_id = $user_id");
            }

            if (isset($_POST["instArray"])) {
                $numInst = pg_fetch_row(pg_query("SELECT COUNT(*) FROM recipe_instructions WHERE recipe_id=$recipe_id AND user_id=$user_id"))[0];
                $pairs = [];
                foreach ($_POST["instArray"] as $key => $inst) {
                    $num = $key + 1;
                    $inst = pg_escape_string($inst);
                    if ($num <= $numInst) {
                        pg_query("UPDATE recipe_instructions SET instruction='$inst' WHERE number=$num AND recipe_id=$recipe_id AND user_id=$user_id");
                    } else {
                        array_push($pairs, "('$inst', '$num', '$recipe_id', '$user_id')");
                    }
                }
                $newNumInst = count($_POST["instArray"]);
                if ($newNumInst > $numInst) {
                    $pairs = implode(", ", $pairs);
                    pg_query("INSERT INTO recipe_instructions (instruction, number, recipe_id, user_id) VALUES $pairs");
                } else if ($newNumInst < $numInst) {
                    pg_query("DELETE FROM recipe_instructions WHERE number>$newNumInst AND recipe_id=$recipe_id AND user_id=$user_id");
                }
            } else {
                pg_query("DELETE FROM recipe_instructions WHERE recipe_id = $recipe_id AND user_id = $user_id");
            }

        } elseif ($_POST["action"] == "edit-ingredient") {
            $id = $_POST["id"];
            $updates = "ingredient='".$_POST["name"]."'";
            if ($_POST["purchase"] === "") {
                $updates .= ",purchase=NULL";
            } else {
                $updates .= ",purchase='".$_POST["purchase"]."'";
            }
            if ($_POST["expiration"] === "") {
                $updates .= ",expiration=NULL";
            } else {
                $updates .= ",expiration='".$_POST["expiration"]."'";
            }
            $result = pg_query("UPDATE ingredients SET $updates WHERE id = $id AND user_id = $user_id");
            echo pg_affected_rows($result);

        } elseif ($_POST["action"] == "delete") {
            $id = $_POST["id"];
            $table = $_POST["type"];
            if ($table == "recipes") {
                pg_query("DELETE FROM recipe_ingredients WHERE recipe_id=$id AND user_id=$user_id");
                pg_query("DELETE FROM recipe_instructions WHERE recipe_id=$id AND user_id=$user_id");
            }
            $result = pg_query("DELETE FROM $table WHERE id=$id AND user_id=$user_id");
            echo pg_affected_rows($result);
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
        if ($_GET["action"] == "get-recipe") {
            $id = $_GET["id"];
            $result = pg_fetch_assoc(pg_query("SELECT name, mealtype FROM recipes WHERE user_id=$user_id AND id=$id"));
            $name = $result["name"];
            $mealtype = $result["mealtype"];

            $ingredients = array();
            $result = pg_query("SELECT * FROM recipe_ingredients WHERE user_id=$user_id AND recipe_id=$id ORDER BY number");
            while ($row = pg_fetch_assoc($result)) {
                $ingredients[$row["id"]] = $row["name"];
            }

            $instructions = array();
            $result = pg_query("SELECT * FROM recipe_instructions WHERE user_id=$user_id AND recipe_id=$id ORDER BY number");
            while ($row = pg_fetch_assoc($result)) {
                $instructions[$row["number"]] = $row["instruction"];
            }

            $data = array(
                "id" => $id,
                "name" => $name,
                "mealtype" => $mealtype,
                "ingredients" => $ingredients,
                "instructions" => $instructions
            );
            echo json_encode($data);
        } elseif ($_GET["action"] == "edit-ingredient") {
            $result = pg_fetch_assoc(pg_query("SELECT * FROM ingredients WHERE user_id = $user_id AND id = $id"));
        }
    }
} else {
    echo var_dump($_POST);
}

?>
