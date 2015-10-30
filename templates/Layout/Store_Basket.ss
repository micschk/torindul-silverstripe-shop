<h1>Shopping Basket</h1>

<% if not $BasketForm %>
	
	You do not currently have any items in your basket. 
	
	<br /><br />
	
	<a href="$getStoreURL" title="Continue Shopping">Continue Shopping</a>
	
<% else %>

	$BasketForm 
	
<% end_if %>