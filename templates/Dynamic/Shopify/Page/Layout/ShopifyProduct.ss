<div class="col-md-12">
    <div class="row">
        <div class="col-md-7">
            <% include ProductImages %>
            <div class="clearfix"></div>
        </div>
        <div class="col-md-5">
            <h1>$Title</h1>
            <% if $SKU %><div class="item-number">SKU: <span class="item-number-holder">$SKU</span></div><% end_if %>

            <% if $Content %>
                <div class="short-description">
                    <h3>Product Description</h3>
                    $Content
                </div>
            <% end_if %>

            <% include BuyButton %>
        </div>
    </div>

    $CommentsForm
</div>
