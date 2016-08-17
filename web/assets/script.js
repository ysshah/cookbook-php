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


    $('button.delete').on('click', function() {

        var deleteType, thisModal;
        if ($(this).hasClass('ingredient')) {
            deleteType = 'ingredients';
            thisModal = $('#edit-ingredient');
        } else {
            deleteType = 'recipes';
            thisModal = $('#edit-recipe');
        }

        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: {
                action: 'delete',
                type: deleteType,
                id: this.id
            },
            success: function(msg) {
                if (msg == 1) {
                    location.reload();
                } else {
                    alert(msg);
                }
            }
        });
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

                var editModal = $('#edit-recipe');

                editModal.find('input.name').val(data.name);
                editModal.find('button.delete').attr('id', data.id);
                editModal.find('ul.edit-recipe').empty();
                editModal.find('ol.edit-recipe').empty();

                for (var i = 0; i < data.ingredients.length; i++) {
                    var ingredient = $('<input class="form-control recipe-ingredient">').val(data.ingredients[i]);
                    editModal.find('ul.edit-recipe').append($('<li></li>').html(ingredient));
                }
                for (var i = 0; i < data.instructions.length; i++) {
                    var instruction = $('<input class="form-control recipe-instruction">').val(data.instructions[i]);
                    editModal.find('ol.edit-recipe').append($('<li></li>').html(instruction));
                }

                $('#edit-recipe').modal('show');
            }
        });
    });


    $('form.edit-ingredient').submit(function(e) {
        e.preventDefault();
        var ingrName = $(this).find('input.name').val();
        var purchaseDate = $(this).find('input.purchase').val();
        var expirationDate = $(this).find('input.expire').val();

        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: {
                action : 'edit-ingredient',
                id : this.id,
                name : ingrName,
                purchase : purchaseDate,
                expiration : expirationDate
            },
            success: function(msg) {
                if (msg == 1) {
                    location.reload();
                } else {
                    alert(msg);
                }
            }
        });
    });


    $('tr.ingredient').on('click', function() {
        var editModal = $('#edit-ingredient');
        editModal.find('input.name').val($(this).attr('data-name'));
        editModal.find('input.purchase').val($(this).attr('data-purchase'));
        editModal.find('input.expire').val($(this).attr('data-expire'));
        editModal.find('button.delete').attr('id', $(this).attr('id'));
        editModal.find('form').attr('id', $(this).attr('id'));
        editModal.modal('show');
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
        $($('div.recipe')[this.id]).show().siblings().hide();

        $('.items tbody').children().removeClass('selected');
        $(this).addClass('selected');
    });

    /* Advance to next or previous recipe on arrow key press.
     * <- = 37, ^ = 38, -> = 39, v = 40 */
    $('html').keydown(function(e) {
        if (37 <= e.which && e.which <= 40) {
            e.preventDefault();
            var selected = $('.selected');
            if (e.which <= 38) {
                if (selected.prev().length) {
                    selected.prev().click();
                } else {
                    selected.parent().parent().prev().find('tr').last().click();
                }
            } else {
                if (selected.next().length) {
                    selected.next().click();
                } else {
                    selected.parent().parent().next().find('tr').eq(1).click();
                }
            };
        }
    });

});
