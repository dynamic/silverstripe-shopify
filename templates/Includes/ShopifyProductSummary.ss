<% if $Image %>
<div class="image">
    <a href="$Link" title="Go to the $MenuTitle.XML page"><img src="$Image.PreviewSrc" alt="$Image.Title.XML" class="img-fluid"></a>
</div>
<% end_if %>

<h2>$Title</h2>

<div class="button-holder">
    <% include BuyOverlay %>
    <a href="$Link" class="btn btn-outline-dark" title="Go to the $MenuTitle.XML page">Learn more</a>
</div>
