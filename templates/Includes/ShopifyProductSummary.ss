<div class="image">
    <% if $Image %>
        <a href="$Link"><img src="$Image.PreviewSrc" alt="$Image.Title.XML" class="img-fluid"></a>
    <% end_if %>
</div>

<h3>$Title</h3>

<div class="button-holder">
    <% include BuyOverlay %>
    <a href="$Link" class="btn btn-outline-dark" title="Go to the $MenuTitle.XML page">Learn more</a>
</div>
