<% if $Images.First %>
	<% with $Images.First %>
		<div>
			<a href="$Top.getProductURL" title="View Enlarged Photo">$getImage</a>
		</div>
	<% end_with %>
<% else %>
	<div>
		No Image
	</div>
<% end_if %>

<br /><br/>

<a href="$getProductURL">$Title</a>