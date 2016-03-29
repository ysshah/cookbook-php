$(document).ready(function(){

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

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
    $(".items").find("tr")[1].click();

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
