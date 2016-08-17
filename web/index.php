<?php include_once("includes/db.php"); ?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Yash's Cookbook</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="assets/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="assets/style.css">
        <script src="assets/jquery-1.12.4.min.js"></script>
        <script src="assets/jquery.validate.min.js"></script>
        <script src="assets/jquery.tablesorter.js"></script>
        <script src="assets/bootstrap.min.js"></script>
        <script src="assets/script.js"></script>
    </head>
    <body>

<?php
if (isset($_POST["action"]) && $_POST["action"] == "Log Out") {
    unset($_SESSION["username"]);
    unset($_POST["action"]);
    echo "Successfully logged out.";
}
if (!isset($_SESSION["username"])) { ?>

    <div class="container">
        <form class="login" action="javascript:void(0);">
            <h4>Log In</h4>
            <div class="form-group">
                <input class="form-control username login" type="text" name="username" placeholder="Username or Email" required>
            </div>
            <div class="form-group">
                <input class="form-control password login" type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-default login">Log In</button>
        </form>
    </div>

    <div class="container">
        <form class="create" action="javascript:void(0);">
            <h4>Create Account</h4>
            <div class="form-group">
                <input class="form-control username create" type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input class="form-control password create" type="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input class="form-control email create" type="email" name="email" placeholder="Email" required>
            </div>
            <button type="submit" class="btn btn-default create">Create Account</button>
        </form>
    </div>

<?php } else {

    $user_id = $_SESSION["user_id"];

    $today = time();
    $secondsInDay = 86400;

    /* Lifespans */
    $lifespans = array();
    $result = pg_query("SELECT * FROM lifespans");
    while ($lifespan = pg_fetch_assoc($result)) {
        $lifespans[$lifespan["food"]] = array(
            "room" => $lifespan["roomtemp"],
            "pantry" => $lifespan["roomtemp"],
            "spice cabinet" => $lifespan["roomtemp"],
            "big fridge" => $lifespan["fridge"],
            "mini fridge" => $lifespan["fridge"],
            "big freezer" => $lifespan["freezer"],
            "mini freezer" => $lifespan["freezer"]);
    }

    /* Ingredients */
    $ingredients = array();
    $result = pg_query("SELECT * FROM ingredients WHERE user_id = '$user_id'");
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
        $ingredients[$name] = array(
            "remain" => $remain,
            "expiration" => $ingredient["expiration"],
            "location" => $ingredient["location"],
            "purchase" => $ingredient["purchase"],
            "id" => $ingredient["id"]
        );
    }

    function generateRecipeHTMLs($recipe, $num) {
        global $ingredients, $user_id;

        $name = $recipe["name"];
        $id = $recipe["id"];
        $remain = $ingrHTML = $instHTML = "";
        $able = "yes";

        $result = pg_query("SELECT * FROM recipe_ingredients WHERE recipe_id = $id AND user_id = $user_id");
        if (pg_num_rows($result)) {
            $ingrHTML .= "<table class='ingredients'><thead><tr><th>Ingredients</th>"
                ."<th class='remaining'>Days remaining</th></tr></thead><tbody>";
            while ($ingredientArray = pg_fetch_assoc($result)) {
                $days = "";
                $ingredient = $ingredientArray["name"];
                if (array_key_exists($ingredient, $ingredients)) {
                    $instock = "yes";
                    $days = $ingredients[$ingredient]["remain"];
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
                $remain = $ingredients[$lower]["remain"];
            } else {
                $able = "no";
            }
        }

        if ($able == "no") {
            $remain = "";
        }

        $result = pg_query("SELECT instruction, number FROM recipe_instructions WHERE recipe_id = $id AND user_id = $user_id ORDER BY number");
        if (pg_num_rows($result)) {
            $instHTML = "<div class='instr-title'>Instructions</div>"
                ."<ol class='instructions'>";
            while ($instruction = pg_fetch_assoc($result)) {
                $instHTML .= "<li class='instruction'>".$instruction["instruction"]."</li>";
            }
            $instHTML .= "</ol>";
        }

        $recipeHTML = "<div id='$num' class='recipe'><div class='name'>$name</div>$ingrHTML$instHTML<button class='btn btn-default edit-recipe' id='$id'>Edit</button></div>";
        $itemHTML = "<tr class='item $able' id='$num'><td>$name</td><td class='remaining'>$remain</td></tr>";
        return array($recipe["mealtype"], $itemHTML, $recipeHTML);
    }

    /* Recipes */
    $num = 0;
    $recipes = array();
    $result = pg_query("SELECT * FROM recipes WHERE user_id = '$user_id'");
    while ($recipe = pg_fetch_assoc($result)) {
        $recipes[$recipe["name"]] = generateRecipeHTMLs($recipe, $num);
        $num += 1;
    }
    ?>

    <div id="loggedin">Logged in: <?php echo $_SESSION["username"]; ?></div>

    <form id="logout" action="index.php" method="post">
        <input type="submit" name="action" value="Log Out">
    </form>

    <div class="modal fade" id="new-recipe" tabindex="-1" role="dialog" aria-labelledby="new-recipe-label">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="new-recipe-label">Add New Recipe</h4>
                </div>
                <div class="modal-body">
                    <form class="new-recipe">
                        <input type="submit" id="submit-new-recipe" class="hidden">
                        <div class="form-group">
                            <label class="control-label" for="recipe-name">Recipe Name</label>
                            <input class="form-control" type="text" id="recipe-name" placeholder="Required" required>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Meal Type</label>
                            <select class="form-control">
                                <?php
                                $mealTypes = explode(",", substr(pg_fetch_assoc(pg_query("SELECT enum_range(NULL::meal)"))["enum_range"], 1, -1));
                                foreach ($mealTypes as $mealType) {
                                    echo "<option>$mealType</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ingredients</label>
                            <ul class="new-recipe">
                                <li><input class="form-control recipe-ingredient last" type="text"></li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label>Instructions</label>
                            <ol class="new-recipe">
                                <li><input class="form-control recipe-instruction last" type="text"></li>
                            </ol>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <label for="submit-new-recipe" class="btn btn-primary submit">Add</label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-recipe" tabindex="-1" role="dialog" aria-labelledby="edit-recipe-label">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="edit-recipe-label">Edit Recipe</h4>
                </div>
                <div class="modal-body">
                    <form class="edit-recipe">
                        <input type="submit" id="submit-edit-recipe" class="hidden">
                        <div class="form-group">
                            <label class="control-label" for="recipe-name">Recipe Name</label>
                            <input class="form-control name" type="text" placeholder="Required" required>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Meal Type</label>
                            <select class="form-control">
                                <?php
                                $mealTypes = explode(",", substr(pg_fetch_assoc(pg_query("SELECT enum_range(NULL::meal)"))["enum_range"], 1, -1));
                                foreach ($mealTypes as $mealType) {
                                    echo "<option>$mealType</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ingredients</label>
                            <ul class="edit-recipe"></ul>
                        </div>
                        <div class="form-group">
                            <label>Instructions</label>
                            <ol class="edit-recipe"></ol>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger pull-left delete recipe">Delete</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <label for="submit-edit-recipe" class="btn btn-primary submit">Save</label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-lg" id="new-ingredients" tabindex="-1" role="dialog" aria-labelledby="new-ingredients-label">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="new-ingredients-label">Add Ingredient(s)</h4>
                </div>
                <div class="modal-body">
                    <form class="new-ingredients">
                        <input type="submit" id="submit-new-ingredients" class="hidden">
                        <ul class="new-ingredients">
                            <li>
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control new-ingredient last" required>
                                </div>
                                <div class="form-group">
                                    <label>Purchase Date</label>
                                    <input type="date" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Expiration Date</label>
                                    <input type="date" class="form-control">
                                </div>
                            </li>
                        </ul>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <label for="submit-new-ingredients" class="btn btn-primary">Add</label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-lg" id="edit-ingredient" tabindex="-1" role="dialog" aria-labelledby="edit-ingredient-label">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="edit-ingredient-label">Edit Ingredient</h4>
                </div>
                <div class="modal-body">
                    <form class="edit-ingredient">
                        <input type="submit" id="submit-edit-ingredient" class="hidden">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control name" required>
                        </div>
                        <div class="form-group">
                            <label>Purchase Date</label>
                            <input type="date" class="form-control purchase">
                        </div>
                        <div class="form-group">
                            <label>Expiration Date</label>
                            <input type="date" class="form-control expire">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger pull-left delete ingredient">Delete</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <label for="submit-edit-ingredient" class="btn btn-primary">Save</label>
                </div>
            </div>
        </div>
    </div>

    <div class="column">
        <div class="title">Current Ingredients</div>
        <button id="newIngredientsModal" type="button" class="btn btn-primary" data-toggle="modal" data-target="#new-ingredients">Add</button>
        <table id="ingredients">
            <thead>
                <tr>
                    <th>Ingredient</th>
                    <th class="remaining">Days remaining</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($ingredients as $ingredient => $info) {
                    $days = $info["remain"];
                    $location = $info["location"];

                    $id = "id='".$info["id"]."'";
                    $dataName = "data-name='$ingredient'";
                    $dataPurchase = "";
                    $dataExpire = "";
                    if ($info["purchase"]) {
                        $dataPurchase = "data-purchase='".$info["purchase"]."'";
                    }
                    if ($info["expiration"]) {
                        $dataExpire = "data-expire='".$info["expiration"]."'";
                    }
                    $data = "$id $dataName $dataPurchase $dataExpire";
                    ?>
                    <tr class="ingredient" <?php echo $data; ?>>
                        <td>
                            <div data-toggle='tooltip' data-placement='right' title='Located in <?php echo $location; ?>'><?php echo $ingredient; ?></div>
                        </td>
                        <td class='remaining'><?php echo $days; ?></td>
                    </tr>
                <?php }
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
        <button id="newRecipeModal" type="button" class="btn btn-primary" data-toggle="modal" data-target="#new-recipe">Add New Recipe</button>
        <div id="recipes">
        <?php
        foreach ($recipes as $name => $info) {
            echo $info[2];
        }
        ?>
        </div>
    </div>

<?php } ?>

    </body>
</html>
