<?php
class Store_OrderController extends ContentController {	
	
	/**
	 * Allowed Actions 
	 *
	 * index - The basket page.
	 */
	private static $allowed_actions = array(
		"index",
		"place",
		"LoginForm",
		"doLogin",
		"CustomerRegisterForm",
		"CustomerNewAddressForm",
		"newaddress",
		"CustomerExistingAddressForm",
		"existingaddress",
		"OrderCourierChoices",
		"selectcourier",
		"OrderSummaryTotals",
		"OrderPaymentForm",
		"payment",
	);
	
	/**
	 * get_link
	 * Return the link for this controller.
	 *
	 * @return URL
	 */
	public static function get_link() {
		return self::link();
	}
	
	/**
	 * Return the URL to the this controller.
	 *
	 * @return URL
	 */
	public function link($action=null) {
		return Store_Controller::get_link() . "/order";
	}
	
	/**
	 * Get the basket URL for use in templates.
	 *
	 * @return URL
	 */
	public function getBasketURL() {
		return Store_Controller::get_link() . "/basket";
	}
	
	/**
	 * CONTROLLER ACTION /
	 * Redirect back to the storefront as /order has no content.
	 */
	public function index() {
		return $this->redirect( Store_Controller::get_link(), 404 );
	}
	
	/**
	 * CONTROLLER ACTION /place
	 * Start the order process using the current basket.
	 */
	public function place(SS_HTTPRequest $request) {
		
		/** 
		 * If the basket is empty there is no order to process,
		 * so redirect the user to their basket.
		 */
		if( !Store_BasketController::is_basket_full() ) {
			return $this->redirect( Store_BasketController::get_link() );
		}
		
		/**
		 * If no-body is currently signed in, redirect them to the login/register forms. 
		 */
		if( !Customer::currentUser() && $request->param("ID")!=="one" ) {
			return $this->redirect( $this->link() . "/place/one", 403 );
		} 
		
		/**
		 * If the signed in user is not part of the 'Customers' group
		 * then they are not permitted to make an order so render an
		 * order error page that informs them of such.
		 */
		if( Customer::currentUser() ) {
			
			if(DB::Query("
				SELECT COUNT(*) FROM group_members
				WHERE (`GroupID`='".DataObject::get_one("Group", "(`Title`='Customers')")->ID."' 
				AND `MemberID`='".Customer::currentUserID()."')
			")->value() < 1) {
		
				return $this->customise(array(
					"Title" => "An unexpected error occurred."
				))->renderWith( array( "Store_Order_Error_MemberGroup", "Page") );	
				
			}
			
		}
		
		/**
		 * If customer is signed in, but no ID is set in the URL, redirect to /two.
		 */
		if( Customer::currentUser() && !$request->param("ID") ) {
			return $this->redirect( $this->link() . "/place/two", 403 );
		}
		 
		/**
		 * Use switch() on $request->param("ID") to determine 
		 * the stage of the order process.
		 */			
		switch( $request->param("ID") ) {
			
			/**
			 * ORDER PROCESS STEP ONE 
			 * Prompt the user to login or create an account.
			 * If the user is already signed in, redirect to stage two. 
			 */
			case "one":
			
				if(!Customer::currentUser()) { 			
					Session::set( 'BackURL', $this->link() . "/place/two" );								
					return $this->customise(array(
						"Title" => "Login/Register"
					))->renderWith( array( "Store_Order_Step1", "Page") );
				} else {
					return $this->redirect( $this->link() . "/place/two" );	
				}
			
				break;
			
			/**
			 * ORDER PROCESS STEP TWO 
			 * Prompt the user to select their billing address from their
			 * Customer_AddressBook. Also provide forms for the customer to complete 
			 * should they wish to enter new address.
			 */
			 case "two":	
			 
				return $this->customise(array(
					"Title" => "Select Billing Address"
				))->renderWith( array( "Store_Order_Step2", "Page") );	
				
				break;
				
			/**
			 * ORDER PROCESS STEP THREE 
			 * Prompt the user to select their shipping address from their
			 * Customer_AddressBook. Also provide forms for the customer to complete 
			 * should they wish to enter new address.
			 */
			 case "three":	
			 
				return $this->customise(array(
					"Title" => "Select Delivery Address"
				))->renderWith( array( "Store_Order_Step3", "Page") );	
				
				break;
				
			/**
			 * ORDER PROCESS STEP FOUR
			 * Prompt the user to select their preferred courier
			 * should more than one be available.
			 */
			case "four":
			 
				return $this->customise(array(
					"Title" => "Select Courier"
				))->renderWith( array( "Store_Order_Step4", "Page") );	
				
				break;
			 	
			/**
			 * ORDER PROCESS STEP FIVE
			 * Based on all of the information entered show the final order summary
			 * including tax with a choice of payment method.
			 */
			case "five":
			
				return $this->customise(array(
					"Title" => "Order Summary &amp; Payment"
				))->renderWith( array( "Store_Order_Step5", "Page") );	
				
				break; 
				
			/**
			 * ORDER PROCESS ERRORS
			 * If this switch statement is used then the order process hasn't followed
			 * the correct process or has encountered an error. Render an appropriate error.
			 */
			default:
			
				switch( $request->param("ID") ) {
					
					/* There doesn't appear to enough stock to satisfy your order at this time. */
					case "order-stock":
					
						return $this->customise(array(
							"Title" => "An unexpected error occurred."
						))->renderWith( array( "Store_Order_Error_Stock", "Page") );		
					
						break;
					
					/* Default Error Message */
					default:
					
						return $this->customise(array(
							"Title" => "An unexpected error occurred."
						))->renderWith( array( "Store_Order_Error", "Page") );	
						
						break;
							
				}
				
				break;
		
		}
		
	}
	
	/**
	 * FORM ACTION /payment
	 * Take the given order, take payment and process it.
	 * 
	 * @param $data
	 * @param $form
	 * @param SS_HTTPRequest $request
	 * @return Various
	 */
	public function payment($data, $form=null, SS_HTTPRequest $request) {
		
		/**
		 * If a TempBasketID exists, process new payment.
		 */
		if( Store_BasketController::get_temp_basket_id() ) {
				
			/* Temporary Basket Identifier */
			$TempBasketID = Store_BasketController::get_temp_basket_id();
			
			/* Order Object */
			$Order = Order::get_one("Order", "(TempBasketID='$TempBasketID')");
			
			/* Order ID */
			$order_id = new SQLQuery("id");;
			$order_id = $order_id->setFrom("`order`")->addWhere("(TempBasketID='$TempBasketID')")->execute()->value();
			
			/**
			 * STEP ONE - Alter the Order record.
			 *
			 * 1 - Alter the Order status to 1 ("Pending/Awaiting Payment") so store admins can now it.
			 * 2 - Assign CustomerID to this Order so that it is now linked to the customers account.
			 * 3 - As the order is now linked to a customer, remove all of the TempBasketID references.
			 * 4 - Add any CustomerComments (if entered)
			 */
			DB::Query("
			UPDATE `order`
			SET `Status`='1',
			`CustomerID`='".Customer::currentUserID()."',
			`TempBasketID`=NULL,
			CustomerComments='".$data["CustomerComments"]."'
			WHERE `ID`='".$order_id."'"
			);
			
			/**
			 * STEP TWO - Remove TempBasketID references from Order_Items records.
			 */
			DB::Query("UPDATE `order_items` SET `TempBasketID`=NULL WHERE `OrderID`='".$order_id."'");
			
			/**
			 * STEP THREE - Destroy TempBasketID Cookies so this customer can shop again later.
			 */
			Store_BasketController::destroy_temp_basket_id();
			 
			/**
			 * STEP FOUR - Handle Stock (if Stock Management Enabled)
			 *
			 * 1 - Check desired products are still in stock.
			 * 2 - Deduct requested stock from inventory to prevent double-allocation.
			 */
			if(StoreSettings::get_settings()->Stock_StockManagement) {
				
				//Prepare the query to retreive the products in this order
				$product = new SQLQuery();
				$product->setFrom("Order_Items")->addWhere("(`OrderID`=".$order_id.")");
				
				//Execute the query
				$result = $product->execute();
				
				//Loop through each product in the order and check stock still exists in the inventory.
				foreach( $result as $row ) { 
					
					//Products ID
					$product_id = $row["OriginalProductID"];
					
					//If not in stock, break loop by failing.
					if( DataObject::get_by_id("Product", $product_id)->StockLevel < $row["Quantity"] ) {
						return $this->redirect( $this->link() . "/place/order-stock" );
					}
					
					//Otherwise, deduct requested stock from inventory.
					DB::Query("UPDATE `product` SET `StockLevel`=StockLevel - ".$row["Quantity"]." WHERE `ID`='".$product_id."'");
				}
				
			}
			
			/** 
			 * STEP FIVE
			 * Hand control over to the requested payment gateway class to continue with remainder of the order.
			 * 
			 * 1 - Get the ClassName of the selected payment gateway and create a new Object.
			 * 2 - Call upon the method newPayment() within the gateway's class.
			 */
			$ClassName = ($data["PaymentMethod"]) ? $data["PaymentMethod"] : null;
			return (!is_null($ClassName)) ? $ClassName::create()->newPayment($order_id) : null;
					
		}
		
		/**
		 * If a TempBasketID does not exist, switch the ID from the $request
		 * and take appropriate action */	
		else {
			
			switch($this->request->param("ID")) {
				
				/** 
				 * A user has been returned after payment from their selected
				 * payment gateway. Pass control to the object of the
				 * Gateway class they used in order to fetch instructions.
				 */
				case "success":
				
					/**
					 * STEP ONE
					 * Does a Class exist for the requested Gateway?
					 * If not, throw HTTP 403 - Forbidden.
					 */
					$request = $this->request;
					if( class_exists($request->getVar("gateway")) ) {
						
						/**
						 * STEP TWO
						 * Is this gateway even enabled? 
						 * If not, throw HTTP 403 - Forbidden.
						 */
						$Gateway = $request->getVar("gateway");
						if( DataObject::get_one($Gateway)->Enabled ) {
							
							/**
							 * STEP THREE
							 * Initiate the Gateway class and pass the 
							 * control to its newPaymentSuccess() method.
							 */
							return $Gateway::create()->newPaymentSuccess($request);
							
						} else {
							return $this->httpError(403);	
						}
						
					} else {
						return $this->httpError(403);
					}
					
					break;
				
				/**
				 * A user has cancelled making a payment for a given order.
				 */
				case "cancelled":
				
					return $this->customise(array(
						"Title" => "An unexpected error occurred."
					))->renderWith( array( "Store_Order_Error_PaymentCancelled", "Page") );
				
					break;
					
				/**
				 * A payment gateway has sent a response for a payment. We need to process that.
				 */
				case "response":
				
					/**
					 * STEP ONE
					 * Does a Class exist for the requested Gateway?
					 * If not, throw HTTP 403 - Forbidden.
					 */
					$request = $this->request;
					if( class_exists( $request->getVar("gateway") ) ) {
						
						/**
						 * STEP TWO
						 * Is this gateway even enabled? 
						 * If not, throw HTTP 403 - Forbidden.
						 */
						$Gateway = $request->getVar("gateway");
						if( DataObject::get_one($Gateway)->Enabled ) {
							
							/**
							 * STEP THREE
							 * Initiate the Gateway class and pass the 
							 * control to its handleGatewayResponse() method.
							 */
							return $Gateway::create()->handleGatewayResponse($request);
							
						} else {
							return $this->httpError(403);	
						}
						
					} else {
						return $this->httpError(403);
					}
				
					break;
				
				/**
				 * Default is duplicate basket error.
				 */
				default:
				
					return $this->customise(array(
						"Title" => "An unexpected error occurred."
					))->renderWith( array( "Store_Order_Error_Duplicate", "Page") );
					
					break;
				
			}				
			
						
		}
		
	}
	
	/**
	 * init
	 * Ensure that this controller maps to the Store page (so navigation is enabled)
	 */
	public function init() {
		parent::init();
		Director::set_current_page( DataObject::get_one("SiteTree", "ClassName='Store'") );	
	}
	
    /** 
	 * FORM ACTION /createaccount
	 * Create a new record for the customer in to the Customer DataObject, 
	 * send them a confirmation email, sign their new account in to the
	 * site and redirect them to stage two of the order process.
	 *
	 * @return null.
	 */
	public function createaccount($data, $form) {
		
		/* Save Data */
        $customer = new Customer();
        $form->saveInto( $customer );
        $customer->write();
        
        /* TODO - Send Confirmation Email */
        
        /* If the new customer can be signed in, redirect to order stage two. */
        if( Customer::get_one("Customer", "(`Email`='".$customer->Email."')")->logIn() ) {
	    	return $this->redirect( Store_OrderController::get_link() . "/place/two" );    
        }
        
        /* Otherwise, redirect them to the form with an error. */
        else {
	        $form->sessionMessage("An unexpected error occurred, please try again.", "bad");
	        return $this->redirectBack();
        }
        
	} 
	
    /** 
	 * FORM ACTION /newaddress
	 * Create a new Customer_AddressBook record and then assign the new
	 * record to the current orders BillingAddressID/ShippingAddressID fields.
	 */
	public function newaddress($data, $form) {
		
		/* Save Data */
        $addressbook = new Customer_AddressBook();
        $addressbook->CustomerID = Customer::currentUserID();
        $form->saveInto( $addressbook );
        $addressbook->write();
        
        /* Get ID of new address */
        $AddressID = $addressbook->ID;
        
        /* Get TempBasketID */
        $TempBasketID = Store_BasketController::get_temp_basket_id();
        
        if( Order::update_temp_order_addresses($TempBasketID, $AddressID, $data["Type"]) ) {
	        
	        if($data["Type"]=="billing") {
	        	return $this->redirect( $this->link() . "/place/three");
	        } else {
	        	return $this->redirect( $this->link() . "/place/four");
	        }
        } else {
	        return $this->redirect( $this->link() . "/place/error/");
        }
		
	}
	
    /** 
	 * FORM ACTION /existingaddresss
	 * Take the selected Customer_AddressBook record and assign it to 
	 * the current orders BillingAddressID/ShippingAddressID fields.
	 */
	public function existingaddress($data, $form) {
		
        /* Get ID of existing address */
        $AddressID = $data["AddressID"];
		
		/* Check the submitted address actually exists before proceeding */
		if( DB::Query(
			"SELECT COUNT(*) 
			FROM Customer_AddressBook 
			WHERE (`ID`='".$data["AddressID"]."' 
			AND CustomerID='".Customer::currentUserID()."')
		")->value()>0 ) {
	        
	        /* Get TempBasketID */
	        $TempBasketID = Store_BasketController::get_temp_basket_id();
	        
	        if(Order::update_temp_order_addresses(
	        	$TempBasketID, 
	        	$AddressID, 
	        	$data["Type"]
	        )) {
		        
		        if($data["Type"]=="billing") { 
			        return $this->redirect( $this->link() . "/place/three"); 
			    } else { return $this->redirect( $this->link() . "/place/four"); }
		        
	        } else {
		        return $this->redirect( $this->link() . "/place/error/");
	        }
        
        } else {
			return $this->redirect( $this->link() . "/place/error/");  
        }
		
	}
	
    /** 
	 * FORM ACTION /selectcourier
	 * Take the selected Courier and assign it to the current order
	 * recrods Courier field. Then, as we also have both the billing and
	 * and shipping address details available, calculate the TaxClassRate and
	 * TaxClassName based on the stores Tax Configuration.
	 */
	public function selectcourier($data, $form) {
		
        /* Get TempBasketID */
        $TempBasketID = Store_BasketController::get_temp_basket_id();		
		
		/**
		 * ONE - Check the submitted courier actually exists before proceeding
		 */
        if( DB::Query("
        	SELECT COUNT(*) 
        	FROM Courier 
        	WHERE (`ID`='".$data["Courier"]."' 
        	AND Enabled='1')
        ")->value()>0 ) {
	        
			/* Update the Order record with the Courier choice, showing an error if it fails. */
			if(!DB::Query("
				UPDATE `order` 
				SET Courier='".$data["Courier"]."' 
				WHERE TempBasketID='$TempBasketID'
			")) {
				return $this->redirect( $this->link() . "/place/error/");
			}
			
		}
		
		/**
		 * TWO - Update Order tax information, based on Store Settings, so customers see tax information for 
		 * the Zone/Class/Rate applicable to them. 
		 */
		 
			/* Get the Customer_AddressBook Objects */
			$shipping = DataObject::get_one("Order", "(`TempBasketID`='".$TempBasketID."')")->ShippingAddress();
			$billing = DataObject::get_one("Order", "(`TempBasketID`='".$TempBasketID."')")->BillingAddress();

			/* 
		     * How is product tax to be calculated, based on Shipping/Billing/Store Address?
		     *
		     * VALUES:
		     * 1 - Billing Address
		     * 2 - Shipping Address
		     * 3 - Store Address
		     */
		    $calculate = StoreSettings::get_settings()->TaxSettings_CalculateUsing;
		    if($calculate==1) { $tax_address_country = $billing->Country; }  //Based on Billing Address
		    elseif($calculate==2) { $tax_address_country = $shipping->ShippingAddress()->Country; } //Based on Shipping Address
		    else { $tax_address_country = StoreSettings::get_settings()->StoreSettings_StoreCountry; } //Based on Store Address
		    
			/* Get the ID of the Tax Zone for the tax country if it exists. Otherwise, use the All zone. */
			$count = new SQLQuery("COUNT(*)");
			$count->setFrom("`TaxZones`")->addWhere("`Title`='$tax_address_country'");
			if( $count->execute()->value() > 0 ) {	
				$tax_zone_id = DataObject::get_one("TaxZones", "(`Title`='$tax_address_country')")->ID;
			} else {
				//This is the ID of the 'All' zone.
				$tax_zone_id = 1;
			}
			
			/* Using the Tax Zone ID, loop through the Order_Items and update the TaxClassRate and TaxClassName based on the Zone. */
			$order_items = new SQLQuery();
			$order_items->setFrom("Order_Items")->addWhere("(`TempBasketID`='".$TempBasketID."')");
			$result = $order_items->execute();
			foreach( $result as $row ) {
				
				/* First, get the ID of the products TaxClass. */
				$TaxClassID = DataObject::get_one("TaxClasses", $row["TaxClass"])->ID;
				
				/* Next get the TaxClassRate and TaxClassName within the given zone. */
				$TaxClassInfo = DataObject::get_one("TaxRates", "(`TaxClass`='".$TaxClassID."' AND `TaxZoneID`='".$tax_zone_id."')");
				$rate = $TaxClassInfo->Rate;
				$title = $TaxClassInfo->Title;
				
				/* Finally, we update the Order_Item itself with these new values. */
				DB::Query("
					UPDATE Order_Items 
					SET TaxClassName='".$title."', 
					TaxClassRate='".$rate."' 
					WHERE `ID`='".$row["ID"]."'
				");
				
			}
			
		/**
		 * THREE - Redirect to Step Five.
		 */
		return $this->redirect( $this->link() . "/place/five");
		
	}	
	
	/**
	 * LoginForm
	 * Return a LoginForm.
	 *
	 * @return OrderLoginForm
	 */
	public function LoginForm() {
		return OrderLoginForm::create($this, "LoginForm");
	}
	
	/**
	 * CustomerRegisterForm
	 * Return a Form which creates new Customer records.
	 *
	 * @return CustomerRegisterForm
	 */
	public function CustomerRegisterForm() {
		return CustomerRegisterForm::create($this, "CustomerRegisterForm");
	}
	
	/**
	 * CustomerHasAddressBook
	 * Checks if the current has any address book entries. 
	 *
	 * @return Boolean
	 */
	public function CustomerHasAddressBook() {
		return (DataObject::get("Customer_AddressBook", "(`CustomerID`='".Customer::currentUserID()."')")->count()>0) ? true : false;
	}
	
	/**
	 * CustomerNewAddressForm 
	 * Return a form allowing a customer to create a new Customer_AddressBook record.
	 *
	 * @param String $type The address type. Either billing/shipping. Defines the forms action.
	 * @return CustomerNewAddressForm
	 */
	public function CustomerNewAddressForm($type) {
		return CustomerNewAddressForm::create($this, "CustomerNewAddressForm", $type);
	}
	
	/**
	 * CustomerExistingAddressForm
	 * Return a form allowing a customer to select an existing Customer_AddressBook record.
	 *
	 * @param String $type The address type. Etiehr billing/shipping. Defines the forms action.
	 * @return CustomerExistingAddressForm
	 */
	public function CustomerExistingAddressForm($type) {
		return CustomerExistingAddressForm::create($this, "CustomerExistingAddressForm", $type);
	}
	
	/**
	 * OrderCourierChoices
	 * Return a form allowing a customer to select from a choice of couriers.
	 *
	 * @return CustomerExistingAddressForm
	 */
	public function OrderCourierChoices() {
		$order_id = DataObject::get_one("Order", "(`TempBasketID`='".Store_BasketController::get_temp_basket_id()."')")->ID;
		return OrderCourierChoices::create($this, "OrderCourierChoices", $order_id);
	}
	
	/**
	 * BasketForm
	 * Display the shopping basket.
	 * 
	 * @return Form
	 */
	public function BasketForm() {
			
		/* Initiate our form, hiding form actions. */
		$form = BasketForm::create($this, 'BasketForm', false);
		
		/* Force POST and Strict Method Check */
		$form->setFormMethod('POST');
		$form->setStrictFormMethodCheck(true);
			
		/* Return the form */
		return $form;
		
	}
	
	/**
	 * OrderPaymentForm
	 * Display a form to initiate order processing and payment.
	 *
	 * @return Form
	 */
	public function OrderPaymentForm() {
		$order = DataObject::get_one("Order", "(`TempBasketID`='".Store_BasketController::get_temp_basket_id()."')");
		return OrderPaymentForm::create($this, "OrderPaymentForm", $order);
	}
	
	/**
	 * OrderSummaryTotals
	 * Return a form showing the totals from a given order.
	 *
	 * @return CustomerExistingAddressForm
	 */
	public function OrderSummaryTotals() {
		$order = DataObject::get_one("Order", "(`TempBasketID`='".Store_BasketController::get_temp_basket_id()."')");
		return OrderSummaryTotals::create($this, "OrderSummaryTotals", $order);	
	}
	
	/**
	 * LogoutLink
	 * Return a logout link 
	 *
	 * @uses Store_Controller::LogoutLink
	 * @param String $location The location to direct to. i.e. storefront, basket, placeorder
	 * @return URL
	 */
	public function LogoutLink($location=null) {
		$Store_Controller = new Store_Controller();
		return $Store_Controller->LogoutLink($location);
	}
	
		
}
?>