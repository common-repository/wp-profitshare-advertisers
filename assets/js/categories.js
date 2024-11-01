$(document).ready(function() {
    $('.profitshare-category select').select2({ width: '100%' });

    // activate bootstrap tooltip
    $('[data-toggle="tooltip"]').tooltip()
});

// toggle categories tree functions
$(".ps-category.expandable .expand, .ps-category.expandable .name").on("click", function(){
    toggleCategory($(this).parent());
});

function toggleCategory(category) {
    var categoryId = category.attr('categoryId');
    var icon = category.find(".expand");

    if($(".ps-category[parentId='"+categoryId+"']").is(":visible")) {
        icon.removeClass('minus').addClass('plus').find("span").html("+");
        profitshareCloseChild(categoryId, category.attr("categoryLevel"));
    } else {
        icon.removeClass('plus').addClass('minus').find("span").html("-");
        $(".ps-category[parentId='"+categoryId+"']").slideToggle("fast");
    }
}

function profitshareCloseChild(categoryId, parentLevel) {
    $(".ps-category[parentId='"+categoryId+"']").each(function() {
        var childCategoryId = $(this).attr("categoryId");
        var icon = $(this).find(".expand");
        var categoryLevel = $(this).attr("categoryLevel");

        $(this).hide();

        if(!icon.hasClass('no-child')) {
            icon.removeClass('plus').removeClass('minus').addClass('plus').find("span").html("+");
        }
        profitshareCloseChild(childCategoryId, categoryLevel);
    });
}
// end of toggle categories functions

// save button click
$(".btn-save").on("click", function(e) {
    e.preventDefault();

    var parent = $(this).parent().parent();

    var wordpressCategoryId = parent.attr("categoryId");
    var profitshareCategoryId = parent.find(".profitshare-category-select").val();
    var profitshareCategoryCommission = parent.find(".profitshare-commission").val();

    var ajaxButton = $(this);

    ajaxButton.attr("disabled", true);
    parent.find(".fa-success").fadeOut();

    $.ajax({
        url: document.URL,
        type: "POST",
        data: {wordpressCategoryId : wordpressCategoryId, profitshareCategoryId : profitshareCategoryId, profitshareCategoryCommission : profitshareCategoryCommission },
        success: function(data){
            ajaxButton.attr("disabled", false);
            data = JSON.parse(data);

            if(data.success == 1) {
                parent.find(".has-error").removeClass("has-error");
                parent.find('[data-toggle="tooltip"]').tooltip('hide').fadeOut();
                parent.find(".fa-success").fadeIn();
                return;
            }

            if(data.message) {
                alert(data.message);
                return;
            }

            if(data.errors) {
                for(var key in data.errors) {
                    parent.find("." + key).addClass('has-error').find("i").attr("data-original-title", data.errors[key]).fadeIn().tooltip('show');
                }
            }
        }
    });
});

// submit request on input enter keypress
$(".profitshare-commission").on('keyup', function (e) {
    if (e.keyCode == 13) {
        $(this).parent().parent().find(".btn-save").click();
    }
});

// back button action
$("#backButton").on("click", function(){
    document.location = removeParam("action", document.location.toString());
});

// reimport profitshare categories button action
$("#getProfitshareCategories").on("click", function() {
    var ajaxButton = $(this);
    ajaxButton.attr("disabled", true);

    $.ajax({
        url: removeParam("action", document.location.toString()) + "&action=updateProfitshareCategories",
        type: "POST",
        success: function(data){
            ajaxButton.attr("disabled", false);
            location.reload();
        }
    });
});

// remove param function
function removeParam(key, sourceURL) {
    var rtn = sourceURL.split("?")[0],
        param,
        params_arr = [],
        queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
    if (queryString !== "") {
        params_arr = queryString.split("&");
        for (var i = params_arr.length - 1; i >= 0; i -= 1) {
            param = params_arr[i].split("=")[0];
            if (param === key) {
                params_arr.splice(i, 1);
            }
        }
        rtn = rtn + "?" + params_arr.join("&");
    }
    return rtn;
}