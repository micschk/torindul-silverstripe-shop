<% with $Product %>

<h1>$Title</h1>

<div>

	<!-- Product Photos -->
	<div class="ProductPhotos">
		
		<!-- Main Photo -->
		<% if $Images.First %>
			<% with $Images.First %>
				<div>
					<a href="$getImageEnlarged.URL" title="View Enlarged Photo">$getImage</a>
				</div>
			<% end_with %>
		<% end_if %>		
		<!--//END Main Photo -->
		
		<!-- Other Photos -->
		<% if $Images.Count>1 %>
			<ul class="other_photos">
				<% loop $Images %>
					<% if not First %>
						<li>
							<a href="$getImageEnlarged.URL" title="View Enlarged Photo">$getImageThumbnail</a>
						</li>
					<% end_if %>
				<% end_loop %>
			</ul>
		<% end_if %>
		<!--//END Other Photos -->
		
	</div>
	

	
	<!-- Product Information -->
	<div class="ProductInformation">
		
		<h3>Product Details</h3>
		
		<% if $getProductBrandLogo %>
			<div>
				<img src="$getProductBrandLogo.URL" />
			</div>
		<% end_if %>
		
		<% if $conf(DisplaySettings_ShowPrice)==1 %>
			<div>
				<strong>Price:</strong>
				$getProductPrice
			</div>
		<% end_if %>
		
		<% if $getProductRetailPrice %>
			<div>
				<strong>Retail Price:</strong>
				$getProductRetailPrice
			</div>
		<% end_if %>
		
		<% if $conf(Stock_StockManagement)==1 && $stockLevelViewable %>
			<div>
				<strong>Stock Level:</strong> 
				$StockLevel
			</div>
		<% end_if %>
		
		<% if $conf(DisplaySettings_ShowSKU)==1 %>
			<div>
				<strong>SKU:</strong> 
				$SKU
			</div>
		<% end_if %>
		
		<% if $conf(DisplaySettings_ShowDimensions)==1 %>
			<div>
				<strong>Dimensions:</strong> 
				W: $Width x H: $Height x L: $Length ($conf(StoreSettings_ProductDimensions))
			</div>
		<% end_if %>
		
		<% if $conf(DisplaySettings_ShowWeight)==1 %>
			<div>
				<strong>Weight:</strong> 
				$Weight ($conf(StoreSettings_ProductWeight))
			</div>
		<% end_if %>
		
		<% if $Brand %>
			<div>
				<strong>Brand:</strong> 
				<a href="$getBrandURL" title="View $getBrandName Brand">$getBrandName</a>
			</div>
		<% end_if %>
		
		<% if $Categories %>
			<div>
				<strong>Categories:</strong> 
				<% loop $getCategoryList.Sort(Title, ASC) %>
					<a href="$URL" title="View $Title Category">$Title</a><% if not Last %>,<% end_if %>
				<% end_loop %>
			</div>
		<% end_if %>
		
		<% if $ProductCustomFields %>
		<% loop $ProductCustomFields %>
			<div>
				<strong>$Title:</strong> 
				$Value
			</div>
		<% end_loop %>
		<% end_if %>
		
		<div>$addToBasketForm($ID)</div>
		
	</div>
	
</div>

<br />

<h2>Description</h2>

$Description

<% end_with %>
