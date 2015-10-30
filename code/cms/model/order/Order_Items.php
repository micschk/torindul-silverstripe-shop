<?php
/**
 * Model to store the items of an order
 *
 * CMS DESCRIPTION
 * Admins of the Order_Items form will select a product to add to an order with a dropdown and click Create. 
 * The onBeforeWrite() method will then populate all the other fields by referencing the selected product 
 * from the Product DataObject. We do this to ensure that customers will always be able to view their order
 * history, at the price they paid for it, should the product later be removed from the Product DataObject.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Order_Items extends DataObject {
	
	private static $singular_name = "Order Item";
	private static $plural_name = "Order Items";
	
	/**
	 * get_settings
	 * Get the StoreSettings.
	 *
	 * @return DataObject
	 */
	public static function get_settings() {
		return StoreSettings::get_settings();
	}

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"OriginalProductID" => "Int", //Will be set to the original product ID.
		"SKU" => "Varchar",
		"Price" => "Decimal(10,2)",
		"Discounted" => "Boolean", //Set to 1 if the price of the product was reduced
		"Quantity" => "Int",
		"TaxClass" => "Int",
		"TaxClassName" => "Varchar",
		"TaxClassRate" => "Decimal(10,2)",
		"TaxCalculation" => "Varchar",
		"Width" => "Decimal(10,2)",
		"Height" => "Decimal(10,2)",
		"Length" => "Decimal(10,2)",
		"Weight" => "Decimal(10,2)",
		"TempBasketID" => "Text",
	);
	
	/**
	 * Specify has_one relationship 
	 */
	public static $has_one = array(
		"Order" => "Order"
	);
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array();
	
		
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		'getPhoto' => "Photo",
	    'Title' => 'Product',
	    'Price' => 'Item Price',
	    'Quantity' => 'Qty.',
	    'productPrice' => 'Total Price',
		'SKU' => 'SKU',
		'getfriendlyTaxCalculation' => 'Tax Inc/Exc'
	);
	
    /**
     * getCMSFields
	 * Construct the FieldList used in the CMS. To provide a
	 * smarter UI we don't use the scaffolding provided by
	 * parent::getCMSFields.
     * 
     * @return FieldList
     */
	public function getCMSFields() {
		
	    Requirements::css('torindul-silverstripe-shop/css/LeftAndMain.css');
		
		//Product Item not yet created
		$select_product_item = Tab::create(
			"Item",

			HeaderField::create("Add Order Item"),
			CompositeField::create(
				
				DropdownField::create(
					"OriginalProductID",
					"Select Product",
					Product::get()->sort("Title ASC")->map()
				)
				->setEmptyString("(Select a product)")
			)
			
		);
		
		//Product Item has been created
		$edit_product_item = Tab::create(
			"Item",

			HeaderField::create("Order Item"),
			CompositeField::create(
				
				ReadonlyField::create(
					"Title",
					"Product"
				)
				->setRightTitle("You will need to remove this item and add another should you wish to change the product."),
				
				ReadonlyField::create(
					"SKU",
					"SKU"
				),
				
				FieldGroup::create(
				
					NumericField::create(
						"Price",
						"Cost per item"
					),
					
					DropdownField::create(
						"Discounted",
						"Is this the sale price?",
						array(
							"0" => "No",
							"1" => "Yes"
						)
					),
					
					NumericField::create(
						"Quantity",
						"Quantity ordered"
					)
				
				)
				->setTitle( "Pricing (" . Product::getDefaultCurrency() . ") "),
				
				ReadonlyField::create(
					"SubTotal",
					"Subtotal (" . Product::getDefaultCurrency() . ")",
					StoreCurrency::convertToCurrency( ($this->Price * $this->Quantity) )
				),
				
				ReadonlyField::create(
					"TAX",
					$this->TaxClassName . " (" . Product::getDefaultCurrency() . ")",
					$this->calculateItemTax()
				)
				->setRightTitle( ($this->TaxCalculation==1) ? "Subtotal is inclusive of this tax." : "Subtotal is exclusive of this tax."),
				
				ReadonlyField::create(
					"Total",
					"Total (" . Product::getDefaultCurrency() . ")",
					($this->TaxCalculation==1) 
					? StoreCurrency::convertToCurrency( ($this->Price * $this->Quantity) )
					: StoreCurrency::convertToCurrency( ( ($this->Price * $this->Quantity) + $this->calculateItemTax() ) )
				)
				
			)
			
		);
		
		//Create the FieldList and push the either of the above Tabs to the Root TabSet based on if Product Item exists yet or not.
		$fields = FieldList::create( $root = TabSet::create('Root', ($this->exists()) ? $edit_product_item : $select_product_item ) );
		
		return $fields;
		
	}
	
	/**
	 * Specifiy which form fields are required 
	 */
	public static function getCMSValidator() {
		
		return RequiredFields::create(
			array(
				'OriginalProductID'
			)
		);	
		
	}
	
	/**
	 * getPhoto
	 * If a photo has been uploaded, create a thumbnail
	 * for the GridField. Otherwise, display No Image. 
	 */
	public function getPhoto() {
		
		if(DataObject::get_by_id("Product", $this->OriginalProductID)->Images()->First()->ID) { 
			return DataObject::get_by_id("Product", $this->OriginalProductID)->Images()->First()->setWidth('75');
		} else {
			return "No Image";
		}
		
	}
	
    /**
     * getfriendlyTaxCalculation
	 * Translates a numerical boolean to Inclusive/Exclusive.
     * 
     * @return String
     */
	public function getfriendlyTaxCalculation() {
		if($this->TaxCalculation==1) {
			return "Inclusive";
		} else {
			return "Exclusive";
		}
	}
	
	/**
	 * calculateItemTax
	 * Calculate Item Tax 
	 *
	 * @return float The tax total.
	 */
	public function calculateItemTax() {
		
		/* Multipler for Tax Calculation */
		$inc_multiplier = ( ($this->TaxClassRate / 100) + 1 );
		$exc_multiplier = ( $this->TaxClassRate / 100 );
		
		/* Price of the Product * Quantity */
		$price = ($this->Price * $this->Quantity);
		
		/* Calculate Tax (TaxCalculation 1 means Inclusive, 2 means Exclusive) */
		if( $this->TaxCalculation==1 ) { 
			$tax = ( $price - ($price / $inc_multiplier) );
		} elseif( $this->TaxCalculation==2 ) {
			$tax = ( $price * $exc_multiplier );
		} else { $tax = 0; }
		
		return StoreCurrency::convertToCurrency($tax);
		
	}
	
	/**
	 * productPrice
	 * Price to display in the GridField 
	 *
	 * @return float The product price.
	 */
	public function productPrice() {
		
		/* Discounted CSS Class */
		$discounted = ($this->Discounted!=0) ? "GridFieldSaleNewPrice" : "";
		
		/* Price of the Product * Quantity */		
		$price = StoreCurrency::convertToCurrency( ($this->Price * $this->Quantity) );
		
		/* Field to Create */
		$text = LiteralField::create($title="Price","");
		$text->setValue("<span class='$discounted'>" . Product::getDefaultCurrency() . $price . "</span>");
		return $text;
		
	}
	
	/* 
	 * productTaxInfo 
	 * Get the products tax information
	 *
	 * @param int $order_id the order id from the has_one relationship
	 * @param int $class_id the tax class id for this product
	 * @param string $data the type of tax information to return. 'Rate' for tax rate or 'Title' for tax class name.
	 
	 * @return Tax Class Name / Tax Rate 
	 */
	public static function productTaxInfo( $order_id, $class_id, $data) {
		
		/* 
	     * How is tax to be calculated, based on Shipping/Billing/Store Address 
	     *
	     * VALUES:
	     * 1 - Billing Address
	     * 2 - Shipping Address
	     * 3 - Store Address
	     */
		$calculate = StoreSettings::get_settings()->TaxSettings_CalculateUsing;
		switch($calculate) {
			
			case 1:
			
				$tax_address_country = DataObject::get_by_id( 
					"Customer_AddressBook", 
					DataObject::get_by_id( "Order", $order_id )->BillingAddressID
				)->Country;

			break;
			
			case 2:
			
				$tax_address_country = DataObject::get_by_id( 
					"Customer_AddressBook", 
					DataObject::get_by_id( "Order", $order_id )->ShippingAddressID
				)->Country;		
				
			break;
			
			case 3:
			
				$tax_address_country = StoreSettings::get_settings()->StoreSettings_StoreCountry;
				
			break;
			
		}
		
		/* Get the ID of the Tax Zone for the tax country if it exists. Otherwise, use the All zone. */
		$count = new SQLQuery("COUNT(*)");
		$count->setFrom("`TaxZones`")->addWhere("`Title`='$tax_address_country'");
		if( $count->execute()->value() > 0 ) {	
			$tax_zone_id = new SQLQuery("id");
			$tax_zone_id->setFrom("`TaxZones`")->addWhere("Title`='$tax_address_country'");		
			$tax_zone_id = $tax_zone_id->execute()->value();
		} else {
			$tax_zone_id = 1;//This is the ID of the 'All' zone.
		}
		
		/* If a tax rate does not exist for the tax class in the shipping country tax zone default to the 'All' zone */
		$count = new SQLQuery("COUNT(*)");
		$count->setFrom("`TaxRates`")->addWhere("`TaxZoneID`='$tax_zone_id' AND `TaxClass`='$class_id'");
		if( $count->execute()->value() > 0 ) {
			$query = new SQLQuery($data);
			$query->setFrom("`TaxRates`")->addWhere("`TaxZoneID`='$tax_zone_id' AND `TaxClass`='$class_id'");
			return $query->execute()->value();
		} else {
			$query = new SQLQuery($data);
			$query->setFrom("`TaxRates`")->addWhere("`TaxZoneID`='1' AND `TaxClass`='$class_id'");
			return $query->execute()->value();
		}

	}
	
	/**
	 * onBeforeWrite
	 * Take the selected product and populate the rest of
	 * the fields in the instance with its details from
	 * the Product DataObject.
	 */
	public function onBeforeWrite() {
		
		parent::onBeforeWrite();
		
		/*
		 * Check if the product title exists so that we only populate the fields
		 * with the default from the product itself on first create 
		 */
		if(!$this->getTitle()) {
		
			//Get the product selected.
			$product = DataObject::get_by_id("Product", $this->OriginalProductID);
			
			//Populate the rest of the fields.
			$this->Title = $product->Title;
			$this->SKU = $product->SKU;
			$this->Price = ($product->SalePrice>"0.00") ? $product->SalePrice : $product->RegularPrice;
			$this->Discounted = ($product->SalePrice>"0.00") ? 1 : 0;
			$this->TaxClass = $product->TaxClass;
			$this->TaxCalculation = $this->get_settings()->TaxSettings_InclusiveExclusive;
			$this->Width = $product->Width;
			$this->Height = $product->Height;
			$this->Length = $product->Length;
			$this->Weight = $product->Weight;
				
			if(!$this->TaxClassName) { 
				$this->productTaxInfo( $this->OrderID, $product->TaxClass, "Title" ); 
			}
				
			if(!$this->TaxClassRate) { 
				$this->productTaxInfo( $this->OrderID, $product->TaxClass, "Rate" ); 
			}
				
			if(!$this->Quantity) { 
				$this->Quantity = 1;
			}
		
		}
		
	}
	
	public function canView( $member = null )  { return true; }
	public function canEdit( $member = null )  { return true; }
	public function canCreate( $member = null )  { return true; }
	public function canDelete( $member = null )  { return true; }

}