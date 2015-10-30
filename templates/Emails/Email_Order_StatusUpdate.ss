<table width="700" cellpadding="10" cellspacing="0" border="0" style="border: 1px solid; border-color: #ccc;">
	
	<tbody>
		
		<tr style="font-weight: bold; background-color: #000; color: #fff; text-transform: uppercase;">
			<td colspan="7">$StoreName | Order Confirmation</td>
		</tr>
		
		<tr>
			<td colspan="7">
				Hello $Customer.FirstName,
				
				<br /><br />
				
				Your recent order (#$Order.ID) has had its status changed to <strong>$OrderStatus.Title</strong>.
				
				<br /><br />
				
				<% if $OrderStatus.SystemTitle=='Completed' || $OrderStatus.SystemTitle=='Shipped' && $Order.TrackingNo!='' %>
				
					Your order has been sent using $OrderCourier with $Order.TrackingNo as a tracking reference.
				
					<br /><br/>
				
				<% else_if $OrderStatus.SystemTitle=='Completed' || $OrderStatus.SystemTitle=='Shipped' && $Order.TrackingNo='' %>

					Your order has been sent using $OrderCourier. No tracking number is available on this occasion.
				
					<br /><br/>
				
				<% end_if %>
				
				<% if $OrderStatus.SystemTitle=='Cancelled' %>
				
					<strong>What does Cancelled mean?</strong><br />
					
					An order would typically be set to cancelled for one of the following reasons:<br />
					* You have requested your selected payment provider reverses your payment.<br />
					* A payment authorised against your bank account could not be collected.<br />
					* You requested the order was cancelled before any payments were made against it.
					
					<br /><br />
					
					Please get in touch with us if you have any further enquiries regarding this update.
				
				<% end_if %>
				
				A summary of your original order contents is included below for reference. You can also view it online by  
				<a href="$OrderLink" title="View Order">clicking here</a>.
				
				<br /><br />
				
				Regards,
				
				<br /><br />
				
				$StoreName
			</td>
		</tr>
		
		<tr style="font-weight: bold; background-color: #000; color: #fff; text-transform: uppercase;">
			<td colspan="7">Addresses</td>
		</tr>
		
		<tr style="background-color: #e9e4e4; color: #000;">
			<td colspan="4">Bill To</td>
			<td colspan="3">Ship To</td>
		</tr>
		
		<tr>
			<td colspan="4">
				$BillingAddress.FirstName $BillingAddress.Surname<br />
				<% if $BillingAddress.Company %>$BillingAddress.Company<br /><% end_if %>
				$BillingAddress.AddressLine1<br />
				<% if $BillingAddress.AddressLine2 %>$BillingAddress.AddressLine2<br /><% end_if %>
				$BillingAddress.City<br />
				$BillingAddress.StateCounty<br />
				$BillingAddress.Postcode<br />
				$BillingAddress.Country
			</td>
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
			<td colspan="7">Your Basket</td>
		</tr>
		
		<tr style="background-color: #e9e4e4; color: #000;">
			<td>Photo</td>
			<td>Product</td>
			<td>Unit Price</td>
			<td>Qty</td>
			<td>Total Price</td>
			<td>Tax Inc/Exc</td>
			<td>Tax Rate</td>
		</tr>
		
		<% loop $OrderItems %>
		
			<tr>
				<td>$getPhoto</td>
				<td>$Title</td>
				<td>$Price</td>
				<td>$Quantity</td>
				<td>$productPrice</td>
				<td>$getfriendlyTaxCalculation</td>
				<td>$TaxClassRate%</td>
			</tr>
			
		<% end_loop %>
		
		<tr style="font-weight: bold; background-color: #000; color: #fff; text-transform: uppercase;">
			<td colspan="7">Totals</td>
		</tr>
		
		<tr>
			<td colspan="6" style="text-align: right;">
				Basket Total
			</td>
			<td colspan="1" style="text-align: right;">$CurrencySymbol$Order.calculateSubTotal()</td>
		</tr>
		
		<tr>
			<td colspan="6" style="text-align: right;">
				Basket Tax (Inclusive &amp; Exclusive)
			</td>
			<td colspan="1" style="text-align: right;">$CurrencySymbol$ProductTax</td>
		</tr>
		
		<tr>
			<td colspan="6" style="text-align: right;">
				Shipping ($OrderCourier)
			</td>
			<td colspan="1" style="text-align: right;">$CurrencySymbol$Order.calculateShippingTotal()</td>
		</tr>
		
		<tr>
			<td colspan="6" style="text-align: right;">
				Shipping Tax
			</td>
			<td colspan="1" style="text-align: right;">$CurrencySymbol$Order.calculateShippingTax( $Order.calculateShippingTotal() )</td>
		</tr>
		
		<tr>
			<td colspan="6" style="text-align: right; text-transform: uppercase; font-weight: bold;">
				Order Total
			</td>
			<td colspan="1" style="text-align: right;">$CurrencySymbol$Order.calculateOrderTotal()</td>
		</tr>
		
	</tbody>
	
</table>