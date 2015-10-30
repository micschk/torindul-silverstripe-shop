<a href="$LogoutLink">Logout</a>

<h1>Tell us your delivery address</h1>

	<% if $CustomerHasAddressBook %>
	
		<h2>Select an existing address</h2>
		
		$CustomerExistingAddressForm(shipping)
		
		<br /><br />
		
		<h2>Or, enter a new one</h2>
		
		$CustomerNewAddressForm(shipping)
	
	<% else %>
	
		<h2>Enter a Delivery Address</h2>
		
		$CustomerNewAddressForm(shipping)
		
	<% end_if %>