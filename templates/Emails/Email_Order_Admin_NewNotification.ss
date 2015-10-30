<table width="700" cellpadding="10" cellspacing="0" border="0" style="border: 1px solid; border-color: #ccc;">
	
	<tbody>
		
		<tr style="font-weight: bold; background-color: #000; color: #fff; text-transform: uppercase;">
			<td colspan="4">New Order Received</td>
		</tr>
		
		<tr>
			<td colspan="4">
				A new order ($Order.ID) has been placed with $StoreName.
				
				<br /><br />
				
				You can find the summary below. To manage this order and view more detail, <a href="$OrderLink">click here</a>.
				
				<br /><br />
				
				Have a nice day!
				
			</td>
		</tr>
		
		<% if $Order.CustomerComments %>
		
			<tr style="font-weight: bold; background-color: #000; color: #fff; text-transform: uppercase;">
				<td colspan="4">Customer Comments</td>
			</tr>
			
			<tr>
				<td>$Order.CustomerComments</td>
			</tr>
			
		<% end_if %>
		
		<tr style="font-weight: bold; background-color: #000; color: #fff; text-transform: uppercase;">
			<td colspan="4">Ship To</td>
		</tr>
		
		<tr>
			<td colspan="3">
				$ShippingAddress.FirstName $ShippingAddress.Surname<br />
				<% if $ShippingAddresss.Company %>$ShippingAddress.Company<br /><% end_if %>
				$ShippingAddress.AddressLine1<br />
				<% if $ShippingAddress.AddressLine2 %>$ShippingAddress.AddressLine2<br /><% end_if %>
				$ShippingAddress.City<br />
				$ShippingAddress.StateCounty<br />
				$ShippingAddress.Postcode<br />
				$ShippingAddress.Country
			</td>
		</tr>
		
		<tr style="font-weight: bold; background-color: #000; color: #fff; text-transform: uppercase;">
			<td colspan="4">Order Items</td>
		</tr>
		
		<tr style="background-color: #e9e4e4; color: #000;">
			<td>Photo</td>
			<td>Product</td>
			<td>Qty</td>
			<td>SKU</td>
		</tr>
		
		<% loop $OrderItems %>
		
			<tr>
				<td>$getPhoto</td>
				<td>$Title</td>
				<td>$Quantity</td>
				<td>$SKU</td>
			</tr>
			
		<% end_loop %>
		
	</tbody>
	
</table>