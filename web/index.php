<?php include_once("includes/db.php"); ?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Yash's Cookbook</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width">
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
            "Room" => $lifespan["roomtemp"],
            "Pantry" => $lifespan["roomtemp"],
            "Spice cabinet" => $lifespan["roomtemp"],
            "Big fridge" => $lifespan["fridge"],
            "Mini fridge" => $lifespan["fridge"],
            "Big freezer" => $lifespan["freezer"],
            "Mini freezer" => $lifespan["freezer"]
        );
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

    /* Recipes */
    $recipes = array();
    $result = pg_query("SELECT * FROM recipes WHERE user_id='$user_id'");
    while ($recipe = pg_fetch_assoc($result)) {
        $recipe_id = $recipe["id"];
        $recipeIngredients = $instructions = array();
        $minDaysRemaining = 999;
        $able = "yes";

        $res = pg_query("SELECT name,number FROM recipe_ingredients WHERE recipe_id=$recipe_id AND user_id=$user_id");
        if (pg_num_rows($res)) {
            while ($row = pg_fetch_assoc($res)) {
                $ingredientInfo = array("ingredient" => $row["name"]);
                if (array_key_exists($row["name"], $ingredients)) {
                    $daysRemaining = $ingredients[$row["name"]]["remain"];
                    if ($daysRemaining !== "" and $daysRemaining < $minDaysRemaining) {
                        $minDaysRemaining = $daysRemaining;
                    }
                    $ingredientInfo["daysRemaining"] = $daysRemaining;
                    $ingredientInfo["instock"] = "yes";
                } else {
                    $ingredientInfo["daysRemaining"] = "";
                    $ingredientInfo["instock"] = "no";
                    $able = "no";
                }
                $recipeIngredients[$row["number"]] = $ingredientInfo;
            }
        } else {
            $lower = strtolower($recipe["name"]);
            if (array_key_exists($lower, $ingredients)) {
                $minDaysRemaining = $ingredients[$lower]["remain"];
            } else {
                $able = "no";
            }
        }

        $res = pg_query("SELECT instruction,number FROM recipe_instructions WHERE recipe_id=$recipe_id AND user_id=$user_id");
        while ($row = pg_fetch_assoc($res)) {
            $instructions[$row["number"]] = $row["instruction"];
        }

        if ($able === "no" or $minDaysRemaining == 999) {
            $minDaysRemaining = "";
        }
        $recipes[$recipe_id] = array(
            "name" => $recipe["name"],
            "mealtype" => $recipe["mealtype"],
            "ingredients" => $recipeIngredients,
            "instructions" => $instructions,
            "able" => $able,
            "daysRemaining" => $minDaysRemaining
        );
    }


    function output_items($recipes, $mealtype) {
        $str = "";
        foreach ($recipes as $id => $info) {
            if ($info["mealtype"] == $mealtype) {
                $name = $info["name"];
                $able = $info["able"];
                $remain = $info["daysRemaining"];
                $str .= "
                <tr class='item $able' id='$id'>
                    <td>$name</td>
                    <td class='remaining'>$remain</td>
                </tr>
                ";
            }
        }
        return $str;
    }


    function output_table($recipes, $mealtype) {
        $rows = output_items($recipes, $mealtype);
        echo "
        <table class='items'>
            <thead>
                <tr>
                    <th>$mealtype</th>
                    <th class='remaining'>Days Remaining</th>
                </tr>
            </thead>
            <tbody>
                $rows
            </tbody>
        </table>
        ";
    }


    ?>

    <div id="loggedin">Logged in: <?php echo $_SESSION["username"]; ?></div>

    <form id="logout" action="index.php" method="post">
        <input type="submit" name="action" value="Log Out">
    </form>

    <div class="modal fade" id="new-recipe" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Add New Recipe</h4>
                </div>
                <div class="modal-body">
                    <form class="new-recipe">
                        <input type="submit" id="submit-new-recipe" class="hidden">
                        <input name="action" value="new-recipe" class="hidden">
                        <div class="form-group">
                            <label class="control-label" for="recipe-name">Recipe Name</label>
                            <input name="name" class="form-control" type="text" id="recipe-name" placeholder="Required" required>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Meal Type</label>
                            <select name="mealType" class="form-control">
                                <option>Breakfast</option>
                                <option>Lunch / Dinner</option>
                                <option>Snacks</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ingredients</label>
                            <ul class="new-recipe">
                                <li><input name="ingrArray[]" class="form-control recipe-ingredient last" type="text"></li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label>Instructions</label>
                            <ol class="new-recipe">
                                <li><textarea name="instArray[]" class="form-control recipe-instruction last" type="text"></textarea></li>
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

    <div class="modal fade" id="edit-recipe" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Edit Recipe</h4>
                </div>
                <div class="modal-body">
                    <form class="edit-recipe">
                        <input type="submit" id="submit-edit-recipe" class="hidden">
                        <input name="action" value="edit-recipe" class="hidden">
                        <input name="id" class="hidden id">
                        <div class="form-group">
                            <label class="control-label" for="recipe-name">Recipe Name</label>
                            <input name="name" class="form-control name" type="text" placeholder="Required" required>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Meal Type</label>
                            <select name="mealType" class="form-control">
                                <option>Breakfast</option>
                                <option>Lunch / Dinner</option>
                                <option>Snacks</option>
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
                    <button class="btn btn-danger pull-left delete" data-delete="recipes">Delete</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <label for="submit-edit-recipe" class="btn btn-primary submit">Save</label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade bs-example-modal-lg" id="new-ingredients" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Add Ingredient</h4>
                </div>
                <div class="modal-body">
                    <form class="new-ingredient">
                        <input type="submit" id="submit-new-ingredients" class="hidden">
                        <input name="action" value="new-ingredient" class="hidden">
                        <div class="form-group">
                            <label>Name</label>
                            <input name="name" type="text" class="form-control new-ingredient last" required>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <select name="location" class="form-control location">
                                <option>Pantry</option>
                                <option>Room</option>
                                <option>Big fridge</option>
                                <option>Mini fridge</option>
                                <option>Big freezer</option>
                                <option>Mini freezer</option>
                                <option>Spice cabinet</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Purchase Date</label>
                            <input name="purchase" type="date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Expiration Date</label>
                            <input name="expiration" type="date" class="form-control">
                        </div>
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
                        <input name="action" value="edit-ingredient" class="hidden">
                        <input name="id" class="hidden id">
                        <div class="form-group">
                            <label>Name</label>
                            <input name="name" type="text" class="form-control name" required>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <select name="location" class="form-control location">
                                <option>Pantry</option>
                                <option>Room</option>
                                <option>Big fridge</option>
                                <option>Mini fridge</option>
                                <option>Big freezer</option>
                                <option>Mini freezer</option>
                                <option>Spice cabinet</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Purchase Date</label>
                            <input name="purchase" type="date" class="form-control purchase">
                        </div>
                        <div class="form-group">
                            <label>Expiration Date</label>
                            <input name="expiration" type="date" class="form-control expiration">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger pull-left delete" data-delete="ingredients">Delete</button>
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
                    $dataLocation = "data-location='$location'";
                    $dataPurchase = "";
                    $dataExpiration = "";
                    if ($info["purchase"]) {
                        $dataPurchase = "data-purchase='".$info["purchase"]."'";
                    }
                    if ($info["expiration"]) {
                        $dataExpiration = "data-expiration='".$info["expiration"]."'";
                    }
                    $data = "$id $dataName $dataLocation $dataPurchase $dataExpiration";
                    ?>
                    <tr class="ingredient" <?php echo $data; ?>>
                        <td>
                            <div><?php echo $ingredient; ?></div>
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
            <?php
            output_table($recipes, "Breakfast");
            output_table($recipes, "Lunch / Dinner");
            output_table($recipes, "Snacks");
            ?>
        </div>
    </div>
    <div class="border"></div>
    <div class="column">
        <div class="title">Recipe</div>
        <button id="newRecipeModal" type="button" class="btn btn-primary" data-toggle="modal" data-target="#new-recipe">Add New Recipe</button>
        <div id="recipes">
        <?php foreach ($recipes as $recipe_id => $recipe) { ?>
            <div id="r<?php echo $recipe_id; ?>" class="recipe">
                <div class="name"><?php echo $recipe["name"]; ?></div>

                <?php if (count($recipe["ingredients"])) { ?>
                <table class="ingredients">
                <thead>
                    <tr>
                        <th>Ingredients</th>
                        <th class="remaining">Days remaining</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recipe["ingredients"] as $num => $info) { ?>
                    <tr>
                        <td class='ingredient <?php echo $info["instock"]; ?>'><?php echo $info["ingredient"]; ?></td>
                        <td class='remaining'><?php echo $info["daysRemaining"]; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
                </table>
                <?php } ?>
                
                <?php if (count($recipe["instructions"])) { ?>
                <div class="instr-title">Instructions</div>
                <ol class="instructions">
                <?php foreach ($recipe["instructions"] as $num => $instruction) {
                    echo "<li class='instruction'>$instruction</li>";
                } ?>
                </ol>
                <?php } ?>
                <button class='btn btn-default edit-recipe' id='<?php echo $recipe_id; ?>'>Edit</button>
            </div>
        <?php } ?>
        </div>
    </div>

<?php } ?>

    </body>
</html>
