$(document).ready(function(){


    $('form.create').submit(function(e) {
        e.preventDefault();

        var un = $('input.username.create').val();
        var pw = $('input.password.create').val();

        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: {
                action : 'create-account',
                username : un,
                password : pw
            },
            success: function(msg) {
                alert(msg);
            }
        });
    });


    $('form.login').submit(function(e) {
        e.preventDefault();

        var un = $('input.username.login').val();
        var pw = $('input.password.login').val();

        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: {
                action : 'login',
                username : un,
                password : pw
            },
            success: function(msg) {
                if (msg == 0) {
                    window.location.href = 'index.php';
                } else {
                    alert('Incorrect username and/or password.');
                }
            }
        });
    });


    $('div.modal-body').on('submit', 'form.new-recipe', function(e) {
        e.preventDefault();

        var recipeName = $('input#recipe-name').val();
        if (recipeName) {
            var ingredients = [], instructions = [];
            var ingrInputs = $('input.recipe-ingredient');
            var instInputs = $('input.recipe-instruction');
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
                type: 'POST',
                url: 'ajax/functions.php',
                data: {
                    action : 'new-recipe',
                    name : recipeName,
                    ingrArray : ingredients,
                    instArray : instructions
                },
                success: function(msg) {
                    console.log(msg);
                    if (msg == 0) {
                        alert('Success! New recipe added.');
                        $('#new-recipe').modal('hide');
                    } else {
                        alert('Error: Recipe name taken.');
                    }
                }
            });
        } else {
            alert('Error: No recipe name given.');
        }
    });


    $('div.modal-body').on('submit', 'form.edit-recipe', function(e) {
        e.preventDefault();

        // $.ajax({
        //     type: 'POST',
        //     url: 'ajax/functions.php',
        //     data: {
        //         action : 'edit-recipe',
        //         name : recipeName,
        //         ingrArray : ingredients,
        //         instArray : instructions
        //     },
        //     success: function(msg) {
        //         console.log(msg);
        //         if (msg == 0) {
        //             alert('Success! New recipe added.');
        //             $('#new-recipe').modal('hide');
        //         } else {
        //             alert('Error: Recipe name taken.');
        //         }
        //     }
        // });
    });


    $('ul.new-ingredients').on('keypress', 'li div input.new-ingredient.last', function(e) {
        if (this.value) {
            $(this).removeClass('last');
            var next = $(this).parent().parent().clone();
            next.find('input.new-ingredient').addClass('last').val('');
            $(this).parent().parent().parent().append(next);
        }
    });


    $('ul.new-recipe,ol.new-recipe').on('keypress', 'input.last', function(e) {
        if (this.value) {
            $(this).removeClass('last');
            var next = $(this).clone();
            next.addClass('last');
            next.val('');
            $(this).parent().parent().append($('<li></li>').append(next));
        }
    });


    $('button.edit-recipe').on('click', function() {
        $.ajax({
            type: 'GET',
            url: 'ajax/functions.php',
            data: {
                action : 'get-recipe',
                id : this.id
            },
            success: function(data) {
                data = JSON.parse(data);
                $('#new-recipe').find('form').removeClass('new-recipe').addClass('edit-recipe');
                $('#new-recipe').find('.modal-title').html('Edit Recipe');
                $('#new-recipe').find('label.submit').html('Save');
                $('#new-recipe').find('input#recipe-name').val(data.name);

                $('#new-recipe').find('ul.new-recipe').empty();
                $('#new-recipe').find('ol.new-recipe').empty();

                for (var i = 0; i < data.ingredients.length; i++) {
                    var ingredient = $('<input class="form-control recipe-ingredient">').val(data.ingredients[i]);
                    $('#new-recipe').find('ul.new-recipe').append($('<li></li>').html(ingredient));
                }
                for (var i = 0; i < data.instructions.length; i++) {
                    var instruction = $('<input class="form-control recipe-instruction">').val(data.instructions[i]);
                    $('#new-recipe').find('ol.new-recipe').append($('<li></li>').html(instruction));
                }

                $('#new-recipe').modal('show');
            }
        });
    });


    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    /* Apply tablesorter function to all tables. */
    $('table').tablesorter({
        emptyTo: 'bottom',
        sortList: [[1,0]]
    });

    /* Clicking on a recipe moves the recipe view to that recipe. */
    $('tr.item').on('click', function() {
        var scroll = $('#recipes').find('#'+this.id)[0]
            .offsetTop - $('.column .title').height();
        $('#recipes').scrollTop(scroll);
        $('.items tbody').children().removeClass('selected');
        $(this).addClass('selected');
    });

    /* Select the topmost recipe on page load. */
//    $('.items').find('tr')[1].click();

    /* Advance to next or previous recipe on arrow key press.
     * <- = 37, ^ = 38, -> = 39, v = 40 */
//    $('html').keydown(function(e) {
//        if (37 <= e.which && e.which <= 40) {
//            e.preventDefault();
//            if (e.which <= 38) {
//                $($('.items').find('.selected')[0]).prev().click();
//            } else {
//                $($('.items').find('.selected')[0]).next().click();
//            };
//        }
//    });

//    $('button#add-recipe').click(function() {
//        alert('hello');
//    });

});
