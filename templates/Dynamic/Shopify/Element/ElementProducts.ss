<div class="container">
    <div class="row">
        <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    </div>
    <div class="row">
        <% if $Content %><div>$Content</div><% end_if %>
    </div>
    <div class="row">
        <% if $ProductsList %>
            <% loop $ProductsList %>
            <div class="col-md-3 product inner">
                <% include ShopifyProductSummary %>
            </div>
            <% end_loop %>
        <% end_if %>
    </div>
</div>
