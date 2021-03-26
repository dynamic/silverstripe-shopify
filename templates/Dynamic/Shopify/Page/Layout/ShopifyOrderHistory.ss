<% loop $Orders %>
    <div class="order container mb-5" id="$Cursor">
        <div class="row">
            <h4 class="col">$Name</h4>
            <h5 class="col-auto">$CreatedAt.Date $CreatedAt.Time12</h5>
        </div>
        <div class="row mb-3">
            <% loop $ShippingAddress %>
                <div class="col-12">$Line</div>
            <% end_loop %>
        </div>
        <% loop $LineItems %>
        <div class="line-item mb-2">
            <div class="media">
                <img class="mr-3" src="$ImageSrc" width="64" height="64"/>
                <div class="media-body row">
                    <div class="col">$Name</div>
                    <div class="col"><% if $TotalDiscount %><s><% end_if %>$OriginalPriceSingle.Nice<% if $TotalDiscount %></s> $DiscountedPriceSingle.Nice<% end_if %> X $Quantity</div>
                    <div class="col-auto"><% if $TotalDiscount %><s><% end_if %>$OriginalPriceTotal.Nice<% if $TotalDiscount %></s> $DiscountedPrice.Nice<% end_if %></div>
                </div>
            </div>
        </div>
        <% end_loop %>
        <% if $Note %>
        <div class="row">
            <div class="col">Note</div>
            <div class="col-auto">$Note</div>
        </div>
        <% end_if %>
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
