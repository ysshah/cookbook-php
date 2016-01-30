$(document).ready(function(){

    /* Map of ingredients to shelf life in days. */
    var lifespans = {};

    /* Array of ingredients; filled by loadIngredients(). */
    var ingredientsArray = [];

    /* Map of ingredients to days remaining. */
    var ingredientsObj = {};

    /* Total number of recipes; incremented when recipes are parsed. */
    var recipeNum = 0;

    /* Today's date. */
    var today = new Date();

    /* Number of milliseconds in a day. */
    var msInDay = 3600 * 1000 * 24;

    /* After loading ingredients, parse all recipes. */
    loadLifespans(function() {
        loadIngredients(function() {
            parseAllRecipes();
        });
    });

    /* Load the lifespans of the ingredients in days. */
    function loadLifespans(callback) {
        $.ajax({
            url: "lifespans.txt",
            cache: false,
            success: function(data) {
                var items = data.split("\n");
                items.forEach(function(ingr) {
                    if (ingr) {
                        var ingrInfo = ingr.split(", ");
                        lifespans[ingrInfo[0]] = parseInt(ingrInfo[1]);
                    }
                });
            },
            complete: callback
        });
    }

    /* Place ingredients from ingredients.txt into ingredientsArray and sort
     * them alphabetically. */
    function loadIngredients(callback) {
        $.ajax({
            url: 'ingredients.txt',
            cache: false,
            success: function (data) {
                var items = data.split("\n");
                items.forEach(function(ingredientLine) {
                    if (ingredientLine) {
                        var ingrInfo = ingredientLine.split(", ");
                        var ingr = ingrInfo[0];
                        if (ingrInfo.length == 3) {
                            ingredientsObj[ingr] = Math.ceil(
                                ((new Date(ingrInfo[2])) - today) / msInDay);
                        } else if (ingrInfo.length == 2) {
                            var daysRemaining = lifespans[ingr] - Math.floor(
                                (today - (new Date(ingrInfo[1]))) / msInDay);
                            ingredientsObj[ingr] = daysRemaining;
                        }
                        ingredientsArray.push(ingr);
                    }
                });
                ingredientsArray.sort();
                ingredientsArray.forEach(function(ingr) {
                    if (ingredientsObj.hasOwnProperty(ingr)) {
                        var color = getRGBheat(ingredientsObj[ingr]);
                        expirationHTML = "<td class='remaining' style='color:"+color+"'>"
                            +ingredientsObj[ingr]+"</td>";
                    } else {
                        expirationHTML = "<td></td>";
                    }
                    $("#ingredients tbody").append("<tr><td>"+ingr+"</td>"
                        +expirationHTML+"</tr>");
                });
            },
            complete: callback
        });
    }

    /* Parse breakfast, lunch/dinner, and snack recipes. */
    function parseAllRecipes() {
        parseRecipes("breakfast.txt", "#breakfast-items", function() {
            parseRecipes("lunch_dinner.txt", "#dinner-items", function() {
                parseRecipes("snacks.txt", "#snack-items", function() {
                    // $(".items")[0].firstChild.click();
                    $(".items").find("tr")[1].click()
                    $("table").tablesorter({
                        emptyTo: "bottom"
                    });
                })
            });
        });
    }

    /* Parse recipes from FILE for MEAL. */
    function parseRecipes(file, meal, callback) {
        $.ajax({
            url: file,
            cache: false,
            success: function (data){
                if (data.slice(-1) == "\n") {
                    data = data.slice(0, -1);
                }
                var items = data.split("\n===\n");
                items.forEach(function(item) {
                    var recipe = item.split("\n");
                    var name = recipe[0];

                    var HTMLarray = generateHTML(name, recipe);
                    ingrHTML = HTMLarray[0];
                    instrHTML = HTMLarray[1];
                    numNoIngr = HTMLarray[2];
                    recipeExpire = HTMLarray[3];

                    var tr = document.createElement("tr");
                    tr.id = recipeNum;
                    tr.className = (numNoIngr == 0) ? "item yes" : "item no";
                    tr.innerHTML = "<td>"+name+"</td>"+recipeExpire;
                    tr.onclick = function() {
                        var scroll = $("#recipes").find("#"+this.id)[0]
                                     .offsetTop - $(".column .title").height();
                        $("#recipes").scrollTop(scroll);
                        $(".items tbody").children().removeClass("selected");
                        $(this).addClass("selected");
                    }
                    $(meal+" tbody").append(tr);

                    $("#recipes").append("<div id='"+recipeNum
                        +"' class='recipe'><div class='name'>"+name
                        +"</div>"+ingrHTML+instrHTML+"</div>");
                    recipeNum += 1;
                });
            },
            complete: callback
        });
    }

    /* Generates HTML for items and recipes. */
    function generateHTML(name, recipe) {
        var numNoIngr = 0;
        var ingrHTML = "";
        var instrHTML = "";
        var recipeDeadline = 999;
        if (recipe.length > 1) {
            ingrHTML += "<table class='ingredients'><thead><tr>";            ingrHTML += "<th>Ingredients</th>";
            ingrHTML += "<th class='remaining'>Days remaining</th>";
            ingrHTML += "</tr></thead>";
            var ingredients = recipe[1].split(", ").sort();
            ingredients.forEach(function(ingr) {
                var expireHTML = "<td></td>";
                if (ingredientsArray.indexOf(ingr) == -1) {
                    var instock = "no";
                    numNoIngr += 1;
                } else {
                    var instock = "yes";
                    if (ingredientsObj.hasOwnProperty(ingr)) {
                        var daysRemaining = ingredientsObj[ingr];
                        if (daysRemaining < recipeDeadline) {
                            recipeDeadline = daysRemaining;
                        }
                        var color = getRGBheat(daysRemaining);
                        expireHTML = "<td class='remaining' style='color:"+color+"'>"
                            +daysRemaining+"</td>";
                    }
                }
                ingrHTML += "<tr><td class='ingredient "+instock+"'>"+ingr
                    +"</td>"+expireHTML+"</tr>";
            });
            ingrHTML += "</table>";

            if (recipe.length > 2) {
                instrHTML += "<div class='instr-title'>Instructions</div>";
                instrHTML += "<ol class='instructions'>";
                var instructions = recipe[2].split("; ");
                instructions.forEach(function(instr) {
                    instrHTML += "<li class='instruction'>"+instr+"</li>";
                });
                instrHTML += "</ol>";
            }
        } else {
            name = name.toLowerCase();
            if (ingredientsArray.indexOf(name) == -1) {
                numNoIngr = 1;
            } else {
                if (ingredientsObj.hasOwnProperty(name)) {
                    recipeDeadline = ingredientsObj[name];
                }
            }
        }
        var color = getRGBheat(recipeDeadline);
        var recipeExpire = (recipeDeadline == 999 || numNoIngr > 0) ? "<td></td>"
            : "<td class='remaining' style='color:"+color+"'>"+recipeDeadline+"</td>";
        return [ingrHTML, instrHTML, numNoIngr, recipeExpire];
    }

    /* Get RGB color going from green -> red as DAYSREMAINING reaches zero. */
    function getRGBheat(daysRemaining) {
        var colors = ["#FF0000", "#FF3300", "#ff6600", "#ff9900", "#FFCC00",
                      "#FFFF00", "#ccff00", "#99ff00", "#66ff00", "#33ff00",
                      "#00FF00"];
        var r = 255;
        var g = 255;
        var max = colors.length;
//        if (daysRemaining >= max) {
//            return colors[10];
//        } else {
//            return colors[daysRemaining];
//        }
        return "#000000";
    }

    /* Advance to next or previous recipe on arrow key press.
     * <- = 37, ^ = 38, -> = 39, v = 40 */
    $("html").keydown(function(e) {
        if (37 <= e.which && e.which <= 40) {
            e.preventDefault();

            if (e.which <= 38) {
                $($(".items").find(".selected")[0]).prev().click();
            } else {
                $($(".items").find(".selected")[0]).next().click();
            };
        }
    });

});
