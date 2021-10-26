<% if $Files %>
    <div id="product-{$ID}-images" class="carousel" data-ride="carousel">
        <ul class="carousel-indicators">
            <% loop $Files.Sort('SortOrder') %>
            <li data-target="#product-{$Up.Up.ID}-images" data-slide-to="$Pos(0)"<% if $First %> class="active"<% end_if %>></li>
            <% end_loop %>
        </ul>
        <div class="carousel-inner">
            <% loop $Files.Sort('SortOrder') %>
                <div class="carousel-item <% if $First %>active<% end_if %>">
                    <img class="d-block w-100" src="$URL" alt="$Title">
                </div>
            <% end_loop %>
        </div>
        <a class="carousel-control-prev" href="#product-{$ID}-images" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#product-{$ID}-images" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
<% end_if %>
