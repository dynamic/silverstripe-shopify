<div class="col-md-12">
    <h1>$Title</h1>

    <div class="row">
        <% if $ProductList %>
            <% loop $ProductList %>
            <div class="col-md-3 product inner">
                <% if $ClassName.ShortName == VirtualPage %>
                    <% with $ContentSource %>
                        <% include ShopifyProductSummary Link=$Up.Link %>
                    <% end_with %>
                <% else %>
                    <% include ShopifyProductSummary %>
                <% end_if %>
            </div>
            <% end_loop %>
        <% else %>
            <p>Sorry, there are currently no products. Check back soon!</p>
        <% end_if %>
    </div>

</div>