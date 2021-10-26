<div class="col-md-7">
    <% include ProductImages %>
</div>
<div class="col-md-5">
    <h1>$Title</h1>
    <% if $SKU %><div class="item-number">SKU: <span class="item-number-holder">{$SKU}</span></div><% end_if %>

    <% if $Content %>
        $Content
    <% end_if %>

    <% include BuyForm %>
</div>
