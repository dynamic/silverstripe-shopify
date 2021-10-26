<div class="row">
    <% if $Title && $ShowTitle %><h2>$Title</h2><% end_if %>
    <% if $Content %><div class="col-md-12">$Content</div><% end_if %>
</div>
<div class="row">
    <% if $ProductsList %>
        <% loop $ProductsList %>
        <div class="col-md-3">
            <% include ShopifyProductSummary %>
        </div>
        <% end_loop %>
    <% end_if %>
</div>
