<?php
include_once("includes/db.php");

$today = time();
$secondsInDay = 86400;

/* Lifespans */
$lifespans = array();
$result = pg_query("SELECT * FROM lifespans");
while ($lifespan = pg_fetch_assoc($result)) {
    $lifespans[$lifespan["food"]] = array(
        "roomtemp" => $lifespan["roomtemp"],
        "fridge" => $lifespan["fridge"],
        "freezer" => $lifespan["freezer"]);
}

/* Ingredients */
$ingredients = array();
$result = pg_query("SELECT * FROM ingredients");
while ($ingredient = pg_fetch_assoc($result)) {
    $name = $ingredient["ingredient"];
    $remain = "";
    if ($ingredient["expiration"]) {
        $expire = strtotime($ingredient["expiration"]);
        $remain = ceil(($expire - $today) / $secondsInDay);
    } else if ($ingredient["purchase"]) {
        if (array_key_exists($name, $lifespans)) {
            $life = intval($lifespans[$name][$ingredient["location"]]);
            $purchaseDate = strtotime($ingredient["purchase"]);
            $elapsed = ($today - $purchaseDate) / $secondsInDay;
            $remain = ceil($life - $elapsed);
        }
    }
    $ingredients[$name] = $remain;
}

function generateRecipeHTMLs($recipe, $num) {
    global $ingredients;

    $name = $recipe["name"];
    $remain = $ingrHTML = $instHTML = "";
    $able = "yes";

    if ($recipe["ingredients"]) {
        $ingrHTML .= "<table class='ingredients'><thead><tr><th>Ingredients</th>"
            ."<th class='remaining'>Days remaining</th></tr></thead><tbody>";
        $theseIngredients = explode(", ", $recipe["ingredients"]);
        foreach ($theseIngredients as $ingredient) {
            $days = "";
            if (array_key_exists($ingredient, $ingredients)) {
                $instock = "yes";
                $days = $ingredients[$ingredient];
                if ($days !== "" and ($remain === "" or $days < $remain)) {
                    $remain = $days;
                }
            } else {
                $instock = "no";
                $able = "no";
            }
            $ingrHTML .= "<tr><td class='ingredient $instock'>$ingredient</td>"
                ."<td class='remaining'>$days</td></tr>";
        }
        $ingrHTML .= "</tbody></table>";
    } else {
        $lower = strtolower($name);
        if (array_key_exists($lower, $ingredients)) {
            $remain = $ingredients[$lower];
        } else {
            $able = "no";
        }
    }

    if ($able == "no") {
        $remain = "";
    }

    if ($recipe["instructions"]) {
        $instHTML = "<div class='instr-title'>Instructions</div>"
            ."<ol class='instructions'>";
        $instructions = explode("; ", $recipe["instructions"]);
        foreach ($instructions as $instruction) {
            $instHTML .= "<li class='instruction'>$instruction</li>";
        }
        $instHTML .= "</ol>";
    }

    $recipeHTMl = "<div id='$num' class='recipe'><div class='name'>$name</div>$ingrHTML$instHTML</div>";
    $itemHTML = "<tr class='item $able' id='$num'><td>$name</td><td class='remaining'>$remain</td></tr>";
    return array($recipe["mealtype"], $itemHTML, $recipeHTMl);
}

/* Recipes */
$num = 0;
$recipes = array();
$result = pg_query("SELECT * FROM recipes");
while ($recipe = pg_fetch_assoc($result)) {
    $recipes[$recipe["name"]] = generateRecipeHTMLs($recipe, $num);
    $num += 1;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Yash's Cookbook</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="assets/style.css">
        <script src="assets/jquery-1.11.3.min.js"></script>
        <script src="assets/jquery.tablesorter.js"></script>
        <script src="assets/script.js"></script>
    </head>
    <body>
        <div class="column">
            <div class="title">Current Ingredients</div>
            <table id="ingredients">
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th class="remaining">Days remaining</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($ingredients as $ingredient => $days) {
                        echo "<tr><td>$ingredient</td>"
                            ."<td class='remaining'>$days</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="border"></div>
        <div class="column">
            <div class="title">Items</div>
            <div id="items">
                <table id="breakfast-items" class="items">
                    <thead>
                        <tr>
                            <th>Breakfast</th>
                            <th class="remaining">Days Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($recipes as $name => $info) {
                        if ($info[0] == "breakfast") {
                            echo $info[1];
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <br>
                <table id="dinner-items" class="items">
                    <thead>
                        <tr>
                            <th>Lunch / Dinner</th>
                            <th class="remaining">Days Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($recipes as $name => $info) {
                        if ($info[0] == "lunchdinner") {
                            echo $info[1];
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <br>
                <table id="snack-items" class="items">
                    <thead>
                        <tr>
                            <th>Snacks</th>
                            <th class="remaining">Days Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($recipes as $name => $info) {
                        if ($info[0] == "snack") {
                            echo $info[1];
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="border"></div>
        <div class="column">
            <div class="title">Recipe</div>
            <div id="recipes">
            <?php
            foreach ($recipes as $name => $info) {
                echo $info[2];
            }
            ?>
            </div>
        </div>
    </body>
</html>
