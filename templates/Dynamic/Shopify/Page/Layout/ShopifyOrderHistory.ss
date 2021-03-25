<% loop $Orders %>
    <div class="order container">
        <% loop $LineItems %>
        <div class="line-item">
            <div class="media">
                <img class="mr-3" src="$ImageSrc" width="64" height="64"/>
                <div class="media-body row">
                    <div class="col">$Name</div>
                    <div class="col"><% if $TotalDiscount %><s><% end_if %>$OriginalPriceSingle.Nice<% if $TotalDiscount %></s> $DiscountedPriceSingle.Nice<% end_if %> X $Quantity</div>
                    <div class="col"><% if $TotalDiscount %><s><% end_if %>$OriginalPriceTotal.Nice<% if $TotalDiscount %></s> $DiscountedPrice.Nice<% end_if %></div>
                </div>
            </div>
        </div>
        <% end_loop %>
        <div class="row">
            <div class="col">Subotal</div>
            <div class="col-auto">$SubTotal.Nice</div>
        </div>
        <div class="row">
            <div class="col">Shipping</div>
            <div class="col">$Shipping.Title</div>
            <div class="col-auto">$Shipping.Amount.Nice</div>
        </div>
        <div class="row">
            <div class="col">Taxes</div>
            <div class="col-auto">
                <% loop $Taxes %>
                <div class="row">
                    <div class="col">$Title ($Rate%)</div>
                    <div class="col-auto">$Amount.Nice</div>
                </div>
                <% end_loop %>
            </div>
        </div>
        <div class="row">
            <div class="col">Total</div>
            <div class="col-auto">$Total.Nice</div>
        </div>
    </div>
    <% if not $Last %><hr/><% end_if %>
<% end_loop %>
