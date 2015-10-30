<a href="$LogoutLink">Logout</a>

<h1>Tell us your billing details</h1>

	<% if $CustomerHasAddressBook %>
	
		<h2>Select an existing address</h2>
		
		$CustomerExistingAddressForm(billing)
		
		<br /><br />
		
		<h2>Or, enter a new one</h2>
		
		$CustomerNewAddressForm(billing)
	
	<% else %>
	
		<h2>Enter a Billing Address</h2>
		
		$CustomerNewAddressForm(billing)
		
	<% end_if %>