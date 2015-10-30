<?php
/**
 * Model to store customer orders
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Order extends DataObject {
	
	private static $singular_name = "Order";
	private static $plural_name = "Orders";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Status" => "Int",
		"CustomerComments" => "Text",
		"AdminComments" => "Text",
		"Courier" => "Int",
		"TrackingNo" => "Varchar",
		"TempBasketID" => "Text",
		"ConfirmationEmailSent" => "Int",
	);	
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"ID" => "Order Number",	
		"getOrderStatus" => "Status",
		"getCustomerName" => "Customer Name", 
		"getCustomerPhoneNumbers" => "Customer Phone Number",
		"Created" => "Date",

	);
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array(
		"ConfirmationEmailSent" => 0,
	);
	
	/**
	 * Has Many Relationship 
	 */
	public static $has_many = array(
		"Order_Items" => "Order_Items",
		"Order_Payment" => "Order_Payment",	
		"Order_Emails" => "Order_Emails",
	);
	
	/**
	 * Specific Has One Relationships 
	 */
	private static $has_one = array(
		"Customer" => "Customer",
		"BillingAddress" => "Customer_AddressBook",
		"ShippingAddress" => "Customer_AddressBook"
	);
	
	/**
	 * update_temp_order_addresses
	 * Given the parameters, update an order record 
	 * to use the correct addresses from the customers
	 * address book.
	 *
	 * @param String $TempBasketID The TempBasketID for the current basket
	 * @param String $AddressID The address to use
	 * @param String $AddressType The address to update. Either shipping|billing.
	 *
	 * @return Boolean
	 */
	public static function update_temp_order_addresses($TempBasketID, $AddressID, $AddressType) {
		
		/* The column to update */
		$Column = ($AddressType=='billing') ? "BillingAddressID" : "ShippingAddressID";
		
		/* Run the DB::Query */
		if(DB::Query("UPDATE `order` SET $Column='$AddressID' WHERE TempBasketID='$TempBasketID'")) {
			return true;
		} else {
			return false;
		}
		
	}
	
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
	    $customer = $this->Customer();
	    
		$fields = FieldList::create(
		
			Tabset::create(
				"Root",
					
				Tabset::create(
					"Order",
					
					Tab::create(
						"CustomerDetails",

						/* CUSTOMER SELECTION
						 * If the order record exists show a prompt to inform the admin they could move 
						 * move the order to another customer. Otherwise prompt the user to select the customer
						 * this order will apply to. 
						 */					
						HeaderField::create("Customer Selection"),
						CompositeField::create(
							
							DropdownField::create(
								"CustomerID",
								($this->exists()) ? "Customer" : "Select a Customer",
								Customer::get()->sort(array('Surname'=>'ASC', 'FirstName'=>'ASC'))->map()
							)
							->setEmptyString("(Select a Customer)")
							->setRightTitle( 
								($this->exists()) 
								? "To change the customer against this order select the new customer above (when editing from the
								'Store Orders' screen) and click save."
								: "Which customer is this order for?" 
							)
							
						),
						
						/* CUSTOMER DETAILS (IF ORDER RECORD EXISTS)
						 * Retreive the customers details and show them to the admin. i.e. Contact Details etc.
						 */
						($this->exists()) ? HeaderField::create("Customer Details") : null,
						($this->exists()) ?
						CompositeField::create(
							
							ReadonlyField::create("Customer.FirstName", "First Name", $customer->FirstName),
							ReadonlyField::create("Customer.Surname", "Surname", $customer->Surname),
							ReadonlyField::create("Customer.CompanyName", "Company", $customer->CompanyName),
							ReadonlyField::create("Customer.Landline", "Landline Number", $customer->LandlineNumber),
							ReadonlyField::create("Customer.Mobile", "Mobile Number", $customer->MobileNumber)
							
						):""
						
					),
					
					/* Order Details */
					Tab::create(
						"OrderDetails",
						
						//Order Details
						HeaderField::create("Order Details"),
						CompositeField::create(
							
							DropdownField::create(
								"Status",
								"Order Status",
								Order_Statuses::get()->map()
							),
							
							DropdownField::create(
								"BillingAddressID",
								"Billing Address",
								DataObject::get("Customer_AddressBook", "(`CustomerID`=".$this->Customer()->ID.")")->map()
							)
							->setRightTitle("Customers' billing address.")
							->setEmptyString("(Select one)"),
							
							DropdownField::create(
								"ShippingAddressID",
								"Shipping Address",
								DataObject::get("Customer_AddressBook", "(`CustomerID`=".$this->Customer()->ID.")")->map()
							)
							->setRightTitle("Customers' shipping address.")
							->setEmptyString("(Select one)")
							
						),
						
						//Order Items
						HeaderField::create("Order Items (Basket)"),
						CompositeField::create(
							
							$items = GridField::create(
								"OrderItems",
								"",
								$this->Order_Items(),
								GridFieldConfig_RecordEditor::create()
							),
							
							ReadonlyField::create(
								"SubTotal",
								"Basket Total (" . Product::getDefaultCurrency() . ")",
								$this->calculateSubTotal()
							)
							
						),
						
						//Shipping
						HeaderField::create("Shipping"),
						CompositeField::create(
							
							FieldGroup::create(
							
								DropdownField::create( "Courier", "Courier", $this->getCouriers( $this->ID ) )
								->setRightTitle("Which courier is being used for this order?")
								->setEmptyString("(Select one)"),
							
								Textfield::create("TrackingNo", "Tracking Number (Optional)")
								->setRightTitle("If the shipment has a tracking number, enter it here.")
							
							),
							
							ReadonlyField::create(
								"Shipping",
								"Shipping Total (" . Product::getDefaultCurrency() . ")",
								$this->calculateShippingTotal()
							)
							
						),
						
						//Order Totals
						HeaderField::create("Order Totals"),
						CompositeField::create(
							
							FieldGroup::create("Tax (" . Product::getDefaultCurrency() . ")",
							
								ReadonlyField::create(
									"ProductTaxInclusive",
									"Product Tax (Inclusive)",
									$this->calculateProductTax(1)
								)
								->setRightTitle("Basket total is inclusive of this tax."),
								
								ReadonlyField::create(
									"ProductTaxExclusive",
									"Product Tax (Exclusive)",
									$this->calculateProductTax(2)
								)
								->setRightTitle("Basket total is exclusive of this tax."),
								
								ReadonlyField::create(
									"ShippingTax",
									"Shipping Tax",
									$this->calculateShippingTax( $this->calculateShippingTotal() )
								)
								->setRightTitle(
									(StoreSettings::get_settings()->TaxSettings_ShippingInclusiveExclusive==1)
									? "Shipping price is inclusive of this tax."
									: "Shipping price is exclusive of this tax."
								)
							
							),
							
							FieldGroup::create("Final Totals (" . Product::getDefaultCurrency() . ")",
							
								ReadonlyField::create(
									"Total",
									"Final Total",
									$this->calculateOrderTotal()
								),
								
								ReadonlyField::create(
									"TotalPayments",
									"Total Payments",
									$this->calculatePaymentTotal()
								),
								
								ReadonlyField::create(
									"OutstandingBalance",
									"Outstanding Balance",
									$this->calculateRemainingBalance()
								)
							
							)
							
						),
						
						//Payment
						HeaderField::create("Payments"),
						CompositeField::create(
							
							$items = GridField::create(
								"OrderPayments",
								"",
								$this->Order_Payment(),
								GridFieldConfig_RecordEditor::create()
							)
							
						)
						
					),
					
					/* Order Comments */
					Tab::create(
						"OrderComments",
						
						HeaderField::create("Order Comments"),
						CompositeField::create(
							
							ReadonlyField::create("CustomerComments", "Customer Comments")
							->setRightTitle("These are the comments of this customer."),
							
							TextareaField::create("AdminComments", "Admin Comments")
							->setRightTitle("Only store admins can see these comments")
							
						)
						
					),
					
					/* Order Emails */
					Tab::create(
						"OrderEmails",
						
						HeaderField::create("Order Emails"),
						CompositeField::create(
							
							$items = GridField::create(
								"Order_Emails",
								"",
								$this->Order_Emails(),
								GridFieldConfig_RecordViewer::create()
							)
							
						)
						
					)
					
				)
				
			)
		
		);
		
		//If record doesn't exist yet, hide certain tabs.
		(!$this->exists()) ? $fields->RemoveFieldsFromTab("Root.Order", array(
			"OrderDetails",
			"OrderComments",
			"OrderEmails"			
		)) : null;
		
		/* 
		 * If record exists, and order is unpaid show warning message to prevent shipment of unpaid orders.
		 * Otherwise show a success message.
		*/ 
		if($this->exists() && $this->calculateRemainingBalance()>0) {
			
			$alert = new LiteralField("OrderPayment_LiteralField",
				"<div class=\"literal-field field\">
					<div class=\"message error\">
						<strong>ORDER REMAINS UNPAID</strong> - This order has an outstanding balance of " .
						 Product::getDefaultCurrency() . $this->calculateRemainingBalance() . ". If the customer has paid then the
						 payment gateway may not have provided a payment status yet.
					</div>
				</div>"
			);
			$fields->addFieldToTab("Root.Order", $alert, "Status");
			
		} elseif($this->exists() && $this->calculateRemainingBalance()==0) {
			
			$alert = new LiteralField("OrderPayment_LiteralField",
				"<div class=\"literal-field field\">
					<div class=\"message good\">
						<strong>BALANCE PAID</strong> - This balance of this order has been paid. You may now prepare and 
						dispatch the order items below.
					</div> 
				</div>"
			);
			$fields->addFieldToTab("Root.Order", $alert, "Status");
			
		}

		return $fields;
		
	}
	
	/* Get Couriers
	 *
	 * To do this we are going to fetch all couriers in the Courier DataObject and display them as
	 * valid options where:
	 * 
	 *		a) The couriers' Enabled field is set to 1
	 *		b) The couriers' check_criteria_met() method returns true.
	 *
	 * @param int $order_id The ID of this order. This is passed to the check_criteria_met() functions.
	 * @param Boolean $show_price If true, show the price for the shipping method next to its name.
	 * @return Array
	*/
	public function getCouriers( $order_id=null, $show_price=null ) {
		
		if($this->exists() || !is_null($order_id)) {
		
			//Variable to hold available couriers
			$couriers = array();
			
			//Fetch all Enabled Couriers
			$query = new SQLQuery();
			$query->setFrom('Courier')->addWhere("(`Enabled`='1')");
			
			//Result of query
			$result = $query->execute();
			
			//Iterate over each courier and if check_criteria_met() method returns true, add to the available array.
			foreach($result as $row) { 
				
				//Class of the Courier
				$ClassName = $row["ClassName"];
				
				//Does check_criteria_met() equal true, if yes add to couriers array.
				if( $ClassName::check_criteria_met( $order_id ) ) {
					
					//If $show_price equal to true, show the price with the Courier name.
					if($show_price) {
						$title = $row["Title"] . " (" .Product::getDefaultCurrency() . $ClassName::calculate_shipping_total($order_id). ")";
					} else {
						$title = $row["Title"];
					}
					
					$couriers[ $row["ID"] ] = $title;
					
				} else {
					
				} 
					
			}
			
			return $couriers;
		
		}
		
	}
	
	/**
	 * Specifiy which form fields are required 
	 */
	public static function getCMSValidator() {
		return RequiredFields::create(
			array(
				"CustomerID",
				"BillingAddressID",
				"ShippingAddressID"
			)
		);
	}
	
	/** 
	 * getCustomerName
	 * Return the Customer Name from the Customer/Member relationship 
	 *
	 * @return String
	 */
	public function getCustomerName() {
		$customer = $this->Customer()->FirstName." ".$this->Customer()->Surname;
		$company = $this->Customer()->CompanyName;
		return ($company) ? $customer." (".$company.")" : $customer;
	}
	
	/**
	 * getOrderStatus
	 * Get the Title of the Order's Status 
	 */
	public function getOrderStatus() {
		$query = new SQLQuery("Title");
		$query->setFrom("Order_Statuses")->addWhere("(`id`=".$this->Status.")");
		return $query->execute()->value();
	}	
	
	/**
	 * getCustomerPhoneNumbers
	 * Return the Customer Phone Numbers from the Customer/Member Relationship 
	 */
	public function getCustomerPhoneNumbers() {
		
		$customer = $this->Customer();
		
		if( $customer->LandlineNumber && $customer->MobileNumber ) { 
		
			return $customer->LandlineNumber." / ".$customer->MobileNumber;
			
		} elseif( !empty($customer->LandlineNumber) && empty($customer->MobileNumber) ) {
			
			return $customer->LandlineNumber;
			
		} else {
			
			return $customer->MobileNumber;
			
		}
		
	}
	
	/**
	 * getTitle
	 * As $this->Title does not exist on this Object,
	 * lets u the order number instead. i.e. "Order Number 2".
	 *
	 * @return String
	 */
	public function getTitle() {
		return $this->ID;
	}
	
	/**
	 * calculateSubTotal
	 * Calculate Subtotal
	 *
	 * @param int $order_int The ID of the order to calculate with. Defaults to $this->ID.
	 * @return float The Order Subtotal 
	 */
	public function calculateSubTotal($order_id=null) {
		
		/**
		 * This function should only return if it has been passed a valid order. 
		 * Either within the CMS with $this->exists() or by passing $order_id from
		 * elsewhere in the store. 
		 */
		if( $this->exists() ) {
			$continue = true;
		} elseif(!is_null($order_id)) {
			if(DataObject::get_by_id("Order", $order_id)->exists()) { 
				$continue = true; 
			}
		} else {
			$continue = false;
		}
		
		if( $continue ) {
			
			$order_id = (is_null($order_id)) ? $this->ID : $order_id;
		
			//Prepare the query to retreive the products in this order
			$product = new SQLQuery();
			$product->setFrom("Order_Items")->addWhere("(`OrderID`=".$order_id.")");
			
			//Execute the query
			$result = $product->execute();
			
			//Variable to hold subtotal
			$subtotal = 0;
			
			//Loop through each products in the order and add to the subtotal
			foreach( $result as $row ) { $subtotal = ( $subtotal + ( $row["Price"] * $row["Quantity"] ) ); }
			
			//Return the subtotal
			return StoreCurrency::convertToCurrency($subtotal);
		
		}
		
	}
	
	/*
	 * calculateProductTax
	 * Calculate Product Tax 
	 *
	 * @param Int $type The type of tax to calculate (1 = inclusive / 2 = exclusive)
	 * @param Object $order If provided, use the provided Order Object we are to using to calculate totals.
	 * @return float Total product tax
	 */
	public function calculateProductTax($type, $order=null) {
		
		if( $this->exists() || !is_null($order) ) {
			
			$order_id = (isset($order)) ? $order->ID : $this->ID;
		
			//Prepare the query to retreive the products in this order
			$product = new SQLQuery();
			$product->setFrom("Order_Items")->addWhere("(`OrderID`=".$order_id.") AND (`TaxCalculation`=".$type.")");
			
			//Execute the query
			$result = $product->execute();
			
			//Variable to hold tax subtotal
			$tax = 0;
			
			//Loop through each product in the order and add to the subtotal
			foreach( $result as $row ) { 
				
				/* Multipler for Tax Calculation */
				$inc_multiplier = ( ($row["TaxClassRate"] / 100) + 1 );
				$exc_multiplier = ( $row["TaxClassRate"] / 100 );
				
				/* Price = Product * Quantity */
				$price = ( $row["Price"] * $row["Quantity"] );
				
				/* Calculate Tax (TaxCalculation 1 means Inclusive, 2 means Exclusive) */
				if( $row["TaxCalculation"]==1 ) { 
					$tax = ( $tax + ( $price - ($price / $inc_multiplier) ) );
				} elseif( $row["TaxCalculation"]==2 ) {
					$tax = ( $tax + ( $price * $exc_multiplier ) );
				}
				
			}
			
			//Return the tax value
			return StoreCurrency::convertToCurrency($tax);
		
		}
		
	}
	
	/* 
	 * calculateShippingTotal 
	 * To do this we retreive the ClassName of the courier from the Courier field in the database and then call the method 
	 * calculate_shipping_total($order_no) within it retreive our shipping cost.
	 *
	 * @param Int $order_id If provided this order Id will be used during calcluations.
	 * @param Int $courer If provided this courier will be used in calculations.
	 *
	 */
	public function calculateShippingTotal($order_id=null, $courier=null) {
		
		if( ($this->exists() && $this->Courier) || ($order_id && $courier) ) {
			
			$order_id = (isset($order_id)) ? $order_id : $this->ID;
			$courier = (isset($courier)) ? $courier : $this->Courier;
		
			/* ClassName of Courier */
			$query = new SQLQuery();
			$query->setFrom("Courier")->setSelect("ClassName")->addWhere("(`id`=" . $courier . ")");
			$ClassName = $query->execute()->value();
						
			/* Calculate shipping cost */
			$shipping_cost = $ClassName::calculate_shipping_total($order_id);
			
			/* Return */
			return StoreCurrency::convertToCurrency( $shipping_cost );
		
		}
		
	}
	
	/** 
	 * calculateShippingTax
	 * Calculate Shipping Tax 
	 *
	 * @param Float $shipping_cost The total cost of shipping to which tax needs to be calculated on.
	 * @param Object $order If provided, use this Order object for calculations.
	 * @return float
	 */
	public function calculateShippingTax($shipping_cost, $order=null) {
		
		if( $this->exists() || !is_null($order) ) {
			
			$order = (isset($order)) ? $order : $this;
		
			/* 
		     * How is shipping tax to be calculated, based on Shipping/Billing/Store Address 
		     *
		     * VALUES:
		     * 1 - Billing Address
		     * 2 - Shipping Address
		     * 3 - Store Address
		     */
		    $calculate = StoreSettings::get_settings()->TaxSettings_CalculateUsing;
		    if($calculate==1) { $tax_address_country = $order->BillingAddress()->Country; }  //Based on Billing Address
		    elseif($calculate==2) { $tax_address_country = $order->ShippingAddress()->Country; } //Based on Shipping Address
		    else { $tax_address_country = StoreSettings::get_settings()->StoreSettings_StoreCountry; } //Based on Store Address
			
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
			
			/* If a tax rate does not exist for the shipping tax class in the shipping country tax zone default to the 'All' zone */
			$count = new SQLQuery("COUNT(*)");
			$count->setFrom("`TaxRates`")->addWhere("`TaxZoneID`='$tax_zone_id' AND `TaxClass`='5'");
			if( $count->execute()->value() > 0 ) {
				$rate = new SQLQuery("rate");
				$rate->setFrom("`TaxRates`")->addWhere("`TaxZoneID`='$tax_zone_id' AND `TaxClass`='5'");
				$rate = $rate->execute()->value();
			} else {
				$rate = new SQLQuery("rate");
				$rate->setFrom("`TaxRates`")->addWhere("`TaxZoneID`='1' AND `TaxClass`='5'");
				$rate = $rate->execute()->value();
			}
			
			/* Multipler for Tax Calculation */
			$inc_multiplier = ( ($rate / 100) + 1 );
			$exc_multiplier = ( $rate / 100 );
	
			/* Is shipping price to be inclusive or exclusive of Tax? 1 = Inclusive, 2 = Exclusive */
			$tax_calc = StoreSettings::get_settings()->TaxSettings_ShippingInclusiveExclusive;
			if( $tax_calc==1 ) { 
				$tax = ( $shipping_cost - ($shipping_cost / $inc_multiplier) );
			} elseif( $tax_calc==2 ) {
				$tax = ( $shipping_cost * $exc_multiplier );
			}
			
			return StoreCurrency::convertToCurrency($tax);
		
		}
		
	}
	
	/**
	 * calculateOrderTotal
	 * Calculate Order Total 
	 *
	 * @param Object $order If provided, use this order object in calculations.
	 * @return float
	 */
	public function calculateOrderTotal($order=null) {
		
		if( $this->exists() || !is_null($order) ) {
		
			//Subtotal of all products
			$subtotal = (!isset($order)) ? $this->calculateSubTotal() : $this->calculateSubTotal($order->ID);		
			
			//Total of tax yet to be added (Exclusive of Tax)
			$excl_tax = (!isset($order)) ? $this->calculateProductTax(2) : $this->calculateProductTax(2, $order);		
			
			//Shipping cost
			$shipping = (!isset($order)) ? $this->calculateShippingTotal() : $this->calculateShippingTotal($order->ID, $order->Courier);
			
			//Shipping tax (0 if tax is inclusive, or the value of tax if not)
			$shipping_tax = 
			(StoreSettings::get_settings()->TaxSettings_ShippingInclusiveExclusive==1) ? 0 : 
			(!isset($order)) ? $this->calculateShippingTax($shipping) : $this->calculateShippingTax($shipping, $order);
			
			return StoreCurrency::convertToCurrency( ($subtotal + $excl_tax + $shipping + $shipping_tax) );
		
		}
		
	}
	
	/**
	 * calculatePaymentTotal
	 * Calculate Payment Total 
	 *
	 * @return float
	 */
	public function calculatePaymentTotal() {
		
		//Sum the total payments against this order.
		$query = new SQLQuery("SUM(Amount)");
		$query->setFrom("`Order_Payment`")->addWhere("`OrderId`='".$this->ID."' AND `Status`='Completed'");		
		return StoreCurrency::convertToCurrency( $query->execute()->value() );
		
	}
	
	/**
	 * calculateRemainingBalance
	 * Calculate Remaining Order Balance (Totals - Payment Total) 
	 *
	 * @return float
	 */
	public function calculateRemainingBalance() {
		return StoreCurrency::convertToCurrency( $this->calculateOrderTotal() - $this->calculatePaymentTotal() );
	}
	
	/**
	 * onBeforeDelete
	 * Remove all items related to this order. i.e. has_one and has_many relationship records.
	 *
	 * @todo Find a way to inform the admin that deleting will re-stock inventory.
	 */
	protected function onBeforeDelete() {
		
		parent::onBeforeDelete();
		
		/* 1 - Delete Order_Items records if they exist */
		if($this->Order_Items()->exists()) { $this->Order_Items()->removeAll(); }
		
		/* 2 - Delete Order_Payment records if they exist */
		if($this->Order_Payment()->exists()) { $this->Order_Payment()->removeAll(); }
		
		/* 3 - Delete Order_Emails records if they exist */
		if($this->Order_Emails()->exists()) { $this->Order_Emails()->removeAll(); }
		
	}
	
	public function canView( $member = null ) { return true; }
	public function canEdit( $member = null ) { return true; }
	public function canCreate( $member = null ) { return true; }
	public function canDelete( $member = null ) { return true; }

}