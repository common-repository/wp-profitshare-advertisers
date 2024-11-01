<!-- categories tree in smarty
<div class="level{$level} ps-category {(is_array($category['child']) && $level > 0) ? "expandable" : ""}" categoryLevel="{$level}" style="{($level > 3) ? 'display:none' : ''}; margin-left:{$levelMargin * 10}px; width: calc(100% - {$levelMargin * 10}px);" parentId="{$category['id_parent']}" categoryId="{$category['id_category']}">
<div class="expand {((is_array($category['child']) && $level > 0) ? "plus" : (($level == 0) ? "minus" : "no-child"))}">
<span>{((is_array($category['child']) && $level > 0) ? "+" : (($level == 0) ? "-" : ""))}</span>
</div>
<div class="name wordpressCategoryId">
    <i class="fa fa-exclamation-triangle fa-danger" data-toggle="tooltip" title="" style="display: none;"></i>
    {$category['name']}
</div>
<div class="commission">
    <i class="fa fa-exclamation-triangle fa-danger" data-toggle="tooltip" title="" style="display: none;"></i>
    <input type="text" class="profitshare-commission" value="{($category['id_category']|array_key_exists:$profitsharewordpressCategories && $profitsharewordpressCategories[$category['id_category']]['category_commission'] > 0) ? $profitsharewordpressCategories[$category['id_category']]['category_commission'] : 25}" placeholder="{l s="Category commission"}">
</div>
<div class="profitshare-category">
    <div class="ps-select profitshareCategoryId {($category['id_category']|array_key_exists:$wordpressInactiveCategories) ? 'has-error' : ' '}">
        <i class="fa fa-exclamation-triangle fa-danger" data-toggle="tooltip" title="" style="display: none;"></i>
        <select class="profitshare-category-select">
            {foreach $profitshareCategories as $psCategory}
            <option value="{$psCategory['id_category']}"  {($category['id_category']|array_key_exists:$profitsharewordpressCategories && $profitsharewordpressCategories[$category['id_category']]['profitshare_category_id'] == $psCategory['id_category']) ? 'selected' : ''}>{$psCategory['name']}</option>
            {/foreach}
        </select>
    </div>
</div>
<div class="actions">
    <button class="btn btn-success btn-save">Save</button> <i class="fa fa-check-circle fa-success" style="display: none;"></i>
</div>
</div>
-->
<button id="backButton" class="btn btn-primary">Inapoi</button>
<button id="getProfitshareCategories" class="btn btn-success">Reincarca categoriile profitshare</button>

<div class="ps-container">
    <h2>Store categories</h2>

    <?php if(!empty($wordpressInactiveCategories)):?>
    <div class="row" style="margin-top: 15px;">
        <div class="col-md-6 ">
            <div class="alert alert-danger">Exista categorii asociate cu categorii profitshare inactive!</div>
        </div>
    </div>
    <?php endif;?>

    <div class="row">
        <div class="col-md-8">
            <div class="ps-categories">
                load tree
            </div>
        </div>
    </div>
</div>

<?php if(!empty($wordpressInactiveCategories)):?>
<script>
    $(".ps-category").each(function(){
        $(this).show();
        var expandElement = $(this).find(".expand");

        if(expandElement.hasClass("plus")) {
            expandElement.removeClass("plus").addClass("minus").find("span").html("-");
        }
    });
</script>
<?php endif;?>

<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>

<link href="{$assets}css/categories.css" rel="stylesheet" />
<script src="{$assets}js/categories.js"></script>