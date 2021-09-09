<% if $Files %>
    <div id="product-images-{$ShopifyID}" class="carousel" data-ride="carousel">
        <ul class="carousel-indicators">
            <% loop $Files.Sort('SortOrder') %>
            <li data-target="#product-images-{$ShopifyID}" data-slide-to="$Pos(0)"<% if $First %> class="active"<% end_if %>></li>
            <% end_loop %>
        </ul>
        <div class="carousel-inner">
            <% loop $Files.Sort('SortOrder') %>
                <div class="carousel-item <% if $First %>active<% end_if %>">
                    <img class="d-block w-100" src="$URL" alt="$Title">
                </div>
            <% end_loop %>
        </div>
        <a class="carousel-control-prev" href="#product-images-{$ShopifyID}" data-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </a>
        <a class="carousel-control-next" href="#product-images-{$ShopifyID}" data-slide="next">
            <span class="carousel-control-next-icon"></span>
        </a>
    </div>
<% end_if %>
