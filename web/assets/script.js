$(document).ready(function(){

    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    /* Apply tablesorter function to all tables. */
    $("table").tablesorter({
        emptyTo: "bottom",
        sortList: [[1,0]]
    });

    /* Clicking on a recipe moves the recipe view to that recipe. */
    $("tr.item").on("click", function() {
        var scroll = $("#recipes").find("#"+this.id)[0]
            .offsetTop - $(".column .title").height();
        $("#recipes").scrollTop(scroll);
        $(".items tbody").children().removeClass("selected");
        $(this).addClass("selected");
    });

    /* Select the topmost recipe on page load. */
//    $(".items").find("tr")[1].click();

    /* Advance to next or previous recipe on arrow key press.
     * <- = 37, ^ = 38, -> = 39, v = 40 */
//    $("html").keydown(function(e) {
//        if (37 <= e.which && e.which <= 40) {
//            e.preventDefault();
//            if (e.which <= 38) {
//                $($(".items").find(".selected")[0]).prev().click();
//            } else {
//                $($(".items").find(".selected")[0]).next().click();
//            };
//        }
//    });

//    $("button#add-recipe").click(function() {
//        alert("hello");
//    });

    $("ul,ol").on("keypress", "input.last", function(e) {
        if (this.value) {
            $(this).removeClass("last");
            var next = $(this).clone();
            next.addClass("last");
            next.val("");
            $(this).parent().parent().append($("<li></li>").append(next));
        }
    });

    $("button#recipe-save").click(function() {
        var ingredients = [], instructions = [];
        var ingrInputs = $("input.recipe-ingredient");
        var instInputs = $("input.recipe-instruction");
        for (var i = 0; i < ingrInputs.length; i++) {
            if (ingrInputs[i].value) {
                ingredients.push(ingrInputs[i].value);
            }
        }
        for (var i = 0; i < instInputs.length; i++) {
            if (instInputs[i].value) {
                instructions.push(instInputs[i].value);
            }
        }

        $.ajax({
            type: "POST",
            url: "ajax/functions.php",
            data: {
                action : 'add_recipe',
                name : $("input#recipe-name").val(),
                ingrArray : ingredients,
                instArray : instructions
            },
            success: function(msg) {
                console.log(msg);
                if (msg == 0) {
                    alert('added');
                } else {
                    alert('recipe name taken');
                }
            }
        });
    });

    $("form.login").submit(function(e) {
        e.preventDefault();

        var un = $("input.username.login").val();
        var pw = $("input.password.login").val();

        $.ajax({
            type: "POST",
            url: "ajax/functions.php",
            data: {
                action : 'login',
                username : un,
                password : pw
            },
            success: function(msg) {
                if (msg == 0) {
                    window.location.href = "index.php";
                } else {
                    alert("Incorrect username and/or password.");
                }
            }
        });
    });

//    $("form.create").validate();

    $("form.create").submit(function(e) {
        e.preventDefault();

        var un = $("input.username.create").val();
        var pw = $("input.password.create").val();

        $.ajax({
            type: "POST",
            url: "ajax/functions.php",
            data: {
                action : 'create_account',
                username : un,
                password : pw
            },
            success: function(msg) {
                alert(msg);
            }
        });
    });

});
