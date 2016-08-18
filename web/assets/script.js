$(document).ready(function(){

    /* Submit the sign-up form. */
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


    /* Submit the login form. */
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


    /* Submit the new recipe form. */
    $('form.new-recipe').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: $('form.new-recipe :input').filter(function(i,e){return e.value != ""}).serialize(),
            success: function(msg) {
                console.log(msg);
                if (msg == 0) {
                    location.reload();
                } else {
                    alert('Error: Recipe name taken.');
                }
            }
        });
    });


    /* Submit the new ingredient form. */
    $('form.new-ingredient').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: $(this).serialize(),
            success: function(msg) {
                if (msg == 0) {
                    location.reload();
                } else {
                    alert('Error: Ingredient name taken.');
                }
            }
        });
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


    $('ul.new-recipe,ol.new-recipe').on('keypress', 'input.last', function(e) {
        if (this.value) {
            $(this).removeClass('last');
            var next = $(this).clone();
            next.addClass('last');
            next.val('');
            $(this).parent().parent().append($('<li></li>').append(next));
        }
    });


    /* Populate the edit-recipe modal. */
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
                editModal.find('select').val(data.mealtype);
                editModal.find('button.delete').attr('id', data.id);
                editModal.find('ul.edit-recipe').empty();
                editModal.find('ol.edit-recipe').empty();

                for (var i = 0; i < data.ingredients.length; i++) {
                    var ingredient = $('<input name="ingrArray[]" class="form-control recipe-ingredient">').val(data.ingredients[i]);
                    editModal.find('ul.edit-recipe').append($('<li></li>').html(ingredient));
                }
                for (var i = 0; i < data.instructions.length; i++) {
                    var instruction = $('<input name="instArray[]" class="form-control recipe-instruction">').val(data.instructions[i]);
                    editModal.find('ol.edit-recipe').append($('<li></li>').html(instruction));
                }

                $('#edit-recipe').modal('show');
            }
        });
    });



    /* Populate the edit-ingredient modal. */
    $('tr.ingredient').on('click', function() {
        var editModal = $('#edit-ingredient');
        editModal.find('input.name').val($(this).attr('data-name'));
        editModal.find('input.id').val($(this).attr('id'));
        editModal.find('select.location').val($(this).attr('data-location'));
        editModal.find('input.purchase').val($(this).attr('data-purchase'));
        editModal.find('input.expiration').val($(this).attr('data-expiration'));
        editModal.find('button.delete').attr('id', $(this).attr('id'));
        editModal.modal('show');
    });


    /* Save the edited ingredient. */
    $('form.edit-ingredient').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: $(this).serialize(),
            success: function(msg) {
                if (msg == 1) {
                    location.reload();
                } else {
                    alert(msg);
                }
            }
        });
    });


    /* Handle deletion of recipe or ingredient. */
    $('button.delete').on('click', function() {
        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: {
                action: 'delete',
                type: $(this).attr("data-delete"),
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
