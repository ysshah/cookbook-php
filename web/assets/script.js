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
            data: $('form.new-recipe :input').filter(function(i,e){return e.value != ''}).serialize(),
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

                console.log(data);

                var editModal = $('#edit-recipe');

                editModal.find('input.name').val(data.name);                editModal.find('input.id').val(data.id);
                editModal.find('select').val(data.mealtype);
                editModal.find('button.delete').attr('id', data.id);
                editModal.find('ul.edit-recipe').empty();
                editModal.find('ol.edit-recipe').empty();

                var ingredient = $('<input name="ingrArray[]" class="form-control recipe-ingredient">');
                var instruction = $('<textarea name="instArray[]" class="form-control recipe-instruction"></textarea>');

                $.each(data.ingredients, function(key, value) {
                    var ingr = ingredient.clone().val(value);
                    editModal.find('ul.edit-recipe').append($('<li></li>').html(ingr));
                });
                $.each(data.instructions, function(key, value) {
                    var inst = instruction.clone().val(value);
                    editModal.find('ol.edit-recipe').append($('<li></li>').html(inst));
                });

                editModal.find('ul.edit-recipe').append($('<li></li>').html(ingredient.clone().addClass('last')));
                editModal.find('ol.edit-recipe').append($('<li></li>').html(instruction.clone().addClass('last')));

                $('#edit-recipe').modal('show');
            }
        });
    });


    /* Submit the edit ingredient form. */
    $('form.edit-recipe').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: 'ajax/functions.php',
            data: $('form.edit-recipe :input').filter(function(i,e){return e.value != ''}).serialize(),
            success: function(msg) {
                if (msg == 0) {
                    location.reload();
                } else {
                    alert(msg);
                }
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


    /* Append additional ingredient and instruction inputs when typing. */
    $('ul.new-recipe,ol.new-recipe,ul.edit-recipe,ol.edit-recipe').on('input', 'input.last,textarea.last', function() {
        if (this.value) {
            $(this).removeClass('last');
            var next = $(this).clone();
            next.addClass('last');
            next.val('');
            $(this).parent().parent().append($('<li></li>').append(next));
        }
    });


    /* Apply tablesorter function to all tables. */
    $('table').tablesorter({
        emptyTo: 'bottom',
        sortList: [[1,0]]
    });


    /* Clicking on a recipe moves the recipe view to that recipe. */
    $('tr.item').on('click', function() {
        $('div.recipe#r'+this.id).show().siblings().hide();

        $('.items tbody').children().removeClass('selected');
        $(this).addClass('selected');
    });


    /* Advance to next or previous recipe on arrow key press.
     * <- = 37, ^ = 38, -> = 39, v = 40 */
    $('div.column').keydown(function(e) {
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
