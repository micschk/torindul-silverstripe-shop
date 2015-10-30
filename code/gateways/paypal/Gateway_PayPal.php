<?php
/**
 * GATEWAY NAME: PayPal Payments Standard
 *
 * DESCRIPTION: When enabled, the customer will be able to pay using PayPal.
 * 
 * @author George Botley - Torindul Business Solutions
 */
class Gateway_PayPal extends Gateway {
	
	/**
	 * There is no criteria for this gateway so return true.
	 */
	public static function check_criteria_met($order_no) {
		return true;
	}

	/**
	 * Add fields to the database for this gateways settings
	 */
	private static $db = array(
		"EmailAddress" => "Varchar",
		"PDTToken" => "Varchar",
		"Sandbox" => "Int",
		"Debug" => "Int"
	);	
	
    /**
	 * Add fields to the CMS for this gateway.
     */
	public function getCMSFields() {
		
		//Fetch the fields from the Courier DataObject
		$fields = parent::getCMSFields();
		
		//Add new fields
		$fields->addFieldsToTab("Root.Main", array(
			
			HeaderField::create("Your PayPal Account Details"),
			CompositeField::create(
				
				TextField::create("EmailAddress", "Account Email")
				->setRightTitle("Enter the email address associated with PayPal account where customer funds will be deposited."),
				
				TextField::create("PDTToken", "PDT Token")
				->setRightTitle("Enter your PayPal account 'Payment Data Transfer' (PDT) Token. Find out how to get this 
				<a href='http://bit.ly/RMhgZQ'>here</a>.")
				
			),
			
			HeaderField::create("ADVANCED USERS ONLY: Sandbox Mode & Debug"),
			CompositeField::create(
			
				DropdownField::create("Sandbox", "Sandbox Mode", array("0" => "No", "1" => "Yes"))
				->setRightTitle("Do you wish to test your store use PayPal's test environment. <em>i.e. no real transactions?</em> See 
				<a href='https://developer.paypal.com/developer/accounts/'>developer.paypal.com</a> for more information"),
				
				DropdownField::create( "Debug", "Debug Mode", array("0" => "No", "1" => "Yes") )
				->setRightTitle("If enabled, all IPN communications will saved to 'ipn.log' in your installations root folder.")
				
			)
			
		));
		
		return $fields;
		
	}
	
	/**
	 * Set the required form fields for this gateway, taking those
	 * defined in Gateway in to account.
	 */
	public static function getCMSValidator() {
		
		//Get required fields from Gateway DataObject.
		$parent_required = (is_array(parent::getCMSValidator())) ? parent::getCMSValidator() : array();
		
		//Specify our own required fields.
		$required = array("EmailAddress", "PDTToken");
		
		//Return the required fields.
		return RequiredFields::create( array_merge( $parent_required, $required ) );
		
	}
	
	/**
	 * requireDefaultRecords
	 * Populate the Gateway DataObject with information about our gateway, so that it installs correctly.
	 *
	 * @return void
	 */
	public function requireDefaultRecords() {
		 
		/* Inherit Default Record Creation */
		parent::requireDefaultRecords();
		
		$gateway_name = get_class($this);
		 
		/* If no records exist, create defaults */
		if(!DataObject::get_one($gateway_name)) {
			
			$n = new $gateway_name();
			
			//Disable the courier by default.
			$n->Enabled = 0;
			
			//System name for this gateway
			$n->SystemName = "PayPal Payments Standard";
			
			//Friendly name for this courier.
			$n->Title = "PayPal";
			
			//Sandbox
			$n->Sandbox = 0;
			
			//Debug 
			$n->Debug = "0";
			
			//Write our configuration changes to the courier database tables.
			$n->write();
			
			unset($n);
			
			DB::alteration_message('Successfully installed the gateway "' . $gateway_name . '"', 'created');
			 		 
		}
		
	}
	
	/**
	 * newPayment
	 * Begin payment process for an order using this payment method.
	 *
	 * @param Object $order_id The ID of the order we are to take payment for.
	 * @return Void
	 */
	public function newPayment($order_id) {
		
		$Order = Order::get_by_id("Order", $order_id);
		
		/* First things first, lets check that for this order:
		 *
		 * 1 - Courier is set
		 * 2 - BillingAddressID is set
		 * 3 - ShippingAddressID
		 * 4 - TempBasketID is null
		 *
		 * If all checks pass, continue. Otherwise, show an unknown error.
		 */
		if( $Order->Courier && $Order->BillingAddressID && $Order->ShippingAddressID && is_null($Order->TempBasketID) ) {
			
			return Store_OrderController::create()->customise(array(
				"Title" => "Transferring to PayPal...",
				"PayPalForm" => $this->PayPalForm($order_id),
			))->renderWith( array("Store_Order_Payment_PayPal", "Page") );	
									
		} else {
			return Store_OrderController::create()->redirect( Store_OrderController::create()->link() . "/place/error" );
		}
		
	}
	
	/**
	 * newPaymentSuccess
	 * A payment has been made. Make the database adjustments for this gateway (if applicable).
	 * 
	 * @param SS_HTTPRequest $request The GET/POST variables and URL parameters.
	 * @return HTMLText
	 */
	public function newPaymentSuccess($request) {
		
		/* Show customer a success screen. Back office is handled via the IPN. */
		return Store_OrderController::create()->customise(array(
			"Title" => "Thanks for your payment",
			"OrderNo" => $request->postVar("custom"),
			"Customer" => Customer::currentUser(),
			"Transaction" => array(
				"ID" => $request->postVar("txn_id"),
				"Amount" => DataObject::get_one("StoreCurrency", "(`SystemCreated`='1')")->Symbol . $request->postVar("mc_gross"),
			)
		))->renderWith(array("Store_Order_Payment_PayPal_Success", "Page"));
		
	}
	
	/**
	 * handleGatewayResponse
	 * This action should be used when a gateway submits a POST/GET response
	 * for which we need to action. In this case, the PayPal IPN. We shall
	 * return void as nothing is returned from this method. It is not public
	 * facing and is present to handle system to system communications over 
	 * HTTP communications only. If the gateway doesn't support POST/GET type
	 * responses, implement the back office order updating within the
	 * newPaymentSuccess() method instead.
	 *
	 * ATTRIBUTION
	 * Snippets of IPN code were used from PayPal's GitHub samples on 15-10-2015.
	 * https://github.com/paypal/ipn-code-samples/blob/master/paypal_ipn.php
	 * @author PayPal 
	 * 
	 * @param SS_HTTPRequest $request The GET/POST variables and URL parameters.
	 * @return Void
	 */
	public function handleGatewayResponse($request) {
				
		/**
		 * Only proceed if we have postVars set
		 */
		if($request->postVars()) {

			$gateway = DataObject::get_one("Gateway_PayPal");
			$debug = $gateway->Debug;
		
			/**
			 * STEP ONE 
			 * Prepend cmd=_notify-validate to the POST request from PayPal.
			 * Reading posted data direction from $request->postVars() may
			 * cause serialization isusues with array data. We therefore
			 * will read directly from the input stream instead.
			 */
			$raw_post_data = file_get_contents('php://input');
			$raw_post_array = explode('&', $raw_post_data);
			$myPost = array();
			foreach ($raw_post_array as $keyval) {
				$keyval = explode ('=', $keyval);
				if (count($keyval) == 2)
					$myPost[$keyval[0]] = urldecode($keyval[1]);
			}
			$req = 'cmd=_notify-validate';
			if( function_exists('get_magic_quotes_gpc') ) {
				$get_magic_quotes_exists = true; 
			}
			foreach ($myPost as $key => $value) {
				if( $get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1 ) {
					$value = urlencode(stripslashes($value));
				} else {
					$value = urlencode($value);
				}
				$req .= "&$key=$value";
			}
			
			/**
			 * STEP TWO
			 * Which PayPal URL are we dealing with?
			 */
			if(DataObject::get_one("Gateway_PayPal")->Sandbox) { 
				$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
			} else {
				$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
			}
			 
			/**
			 * STEP THREE
			 * Initiate curl IPN callback to post IPN data back to PayPal
			 * to validate the IPN data is genuine. Without this step anyone
			 * can fake IPN data and mess with your order system.
			 */
			$ch = curl_init($paypal_url);
			if ($ch == FALSE) {
				return FALSE; 
			}
			
			/* Set curl Options */
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
			
			/* Set TCP timeout to 30 seconds */
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
			
			/* Execute Curl and Store Response in $res */
			$res = curl_exec($ch);
			
			/* Are there curl errors? If yes, log them if Debug is enabled. */
			if(curl_errno($ch) != 0) {

				if($debug == 1) { $this->newLogEntry( "Can't connect to PayPal to validate IPN message: ". curl_error($ch) ); }
				curl_close($ch);
				exit;
				
			/* No errors */
			} else {
				
				/* If Debug is enabled, save to the log. */
				if( $debug == 1 ) {
					$this->newLogEntry( "HTTP request of validation request". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" );
					$this->newLogEntry( "HTTP response of validation request: $res" );
				}
				curl_close($ch);
				
			}
			
			/**
			 * STEP FOUR
			 * Inspect IPN validation result and act accordingly.
			 * 1 - Split response headers and payload, a better way for strcmp.
			 * 2 - Do the actions, based on response. 
			 */
			$tokens = explode("\r\n\r\n", trim($res));
			$res = trim(end($tokens));
			if (strcmp ($res, "VERIFIED") == 0) {
				
				/**
			     * DEBUG
			     * If debug is enabled, log the details
			     * of this IPN response. 
			     */
				if( $debug ) { $this->newLogEntry( "Verified IPN: $req " ); }
			
				/**
				 * ERROR CHECK 1
				 * txn_type must be of type 'web_accept'. (Buy Now Button) 
				 */
				if( !$request->postVar("txn_type")=="web_accept") { 
					if( $debug == 1 ) {	
						$this->newLogEntry("ERROR: (txn_id: " . $request->postVar("txn_id") . ") txn_type is not of type 'web_accept'.");
					}
					return Store_Controller::create()->httpError(400);
					exit;
				} 
				
				/** 
				 * ERROR CHECK 2
				 * We must be the intended recipient for the transaction. 
				 */
				if( $gateway->EmailAddress != $request->postVar("receiver_email")) { 
					if( $debug == 1 ) {	
						$this->newLogEntry( 
							"ERROR: (txn_id: " . $request->postVar("txn_id") . ") Intended recipient ".
							"(".$request->postVar("receiver_email").") does not ".
							"match that set in the gateway settings."
						);
					}
					return Store_Controller::create()->httpError(400);
					exit;
				} 
				
				/**
				 * ERROR CHECK 3
				 * An order related to this payment must exist. 
				 */
				$order = new SQLQuery("COUNT(*)");
				$order->setFrom("`order`")->addWhere("(`id`='".$request->postVar("custom")."')");
				if( $order->execute()->value()<1 ) { 
					if( $debug == 1 ) {	
						$this->newLogEntry( 
							"ERROR: (txn_id: " . $request->postVar("txn_id") . ") The order number defined in 'custom' ".
							"(".$request->postVar("custom").") does not exist in the system."
						);
					}	
					return Store_Controller::create()->httpError(400);
					exit;		
				}
				
				/**
				 * ERROR CHECK 4
				 * This IPN message can not be a duplicate. 
				 */
				$dup = new SQLQuery("COUNT(*)");
				$dup->setFrom("`Order_Payment_PayPal`");
				$dup->addWhere("(`txn_id`='".$request->postVar("txn_id")."') AND (`payment_status`='".$request->postVar("payment_status")."')"); 
				$dup_count = $dup->execute()->value();
				if( $dup_count > 0 ) {
					if( $debug == 1 ) {	
						$this->newLogEntry( 
							"ERROR: (txn_id: " . $request->postVar("txn_id") . ") The IPN message received is a duplicate of one ".
							"previously received."
						);
					}	
					return Store_Controller::create()->httpError(400);
					exit;
				}

				/** 
				 * ERROR CHECK 5
				 * The mc_gross has to match the total order price. 
				 */
				$order_total = DataObject::get_by_id("Order", $request->postVar("custom"))->calculateOrderTotal();
				$mc_gross = $request->postVar("mc_gross");
				if( $order_total != $mc_gross ) {
					if( $debug == 1 ) {	
						$this->newLogEntry( 
							"ERROR: (txn_id: " . $request->postVar("txn_id") . ") The payment amount did not match the order amount."
						);
					}	
					return Store_Controller::create()->httpError(400);
					exit;
				}
				
				/**
				 * ERROR CHECK 6
				 * If this IPN is not a duplicate, are there
				 * any other entries for this txn_id?
				 */
				if( $dup_count<1 ) {
				
					/* Count how many entries there are with the IPNs txn_id */
					$record_count = new SQLQuery("COUNT(*)");
					$record_count->setFrom("Order_Payment_PayPal");
					$record_count->addWhere("(`txn_id`='".$request->postVar("txn_id")."')");
					$record_count = $record_count->execute()->value();
					
					/* The row ID for the record that was found, if one exists */
					$payment_record_id = new SQLQuery("`id`");
					$payment_record_id->setFrom("Order_Payment_PayPal")->addWhere("(`txn_id`='".$request->postVar("txn_id")."')");
					$payment_record_id = $payment_record_id->execute()->value();
				
				}
				
				/**
				 * VERIFIED STEP ONE 
				 * 
				 * Either create a payment record or update an existing one an send the applicable emails.
				 */
				switch($request->postVar("payment_status") ) {
					
					/* Payment has cleared, order can progress. */
					case "Completed":
					
						//Send email to admin notification email address
						Order_Emails::create()->adminNewOrderNotification($request->postVar("custom"));
						
						//Send email to the customer confirming their order, if they haven't had one already.
						Order_Emails::create()->customerNewOrderConfirmation($request->postVar("custom"));
					
						if($record_count>0) { 
							$this->updatePaymentRecord(
								$request, 
								$payment_record_id,
								"Completed",
								"Processing"
							);
						} else {
							$this->newPaymentRecord(
								$request, 
								"Completed",
								"Processing"
							);
						}
						
						break;
					
					/* The payment is pending. See pending_reason for more information.	*/
					case "Pending":	
					
						/**
						 * We don't send emails for this status as 'Pending' orders are still awaiting a response from
						 * a payment gateway and should not be dispatched. It is safe to send a confirmation email to
						 * the customer, however.
						 */
						
						//Send email to the customer confirming their order is currently pending
						Order_Emails::create()->customerNewOrderConfirmation($request->postVar("custom"), "Pending");
					
						if($record_count>0) { 
							$this->updatePaymentRecord(
								$request,
								$payment_record_id,
								"Pending",
								"Pending / Awaiting Payment"
							);
						} else {
							$this->newPaymentRecord(
								$request, 
								"Pending",
								"Pending / Awaiting Payment"
							);
						}
					
						break;
						
					/* You refunded the payment. */
					case "Refunded":
					
						/* Notify the customer of a change to their order status */
						Order_Emails::create()->customerOrderStatusUpdate($request->postVar("custom"), "Refunded");
					
						if($record_count>0) { 
							$this->updatePaymentRecord(
								$request, 
								$payment_record_id,
								"Refunded",
								"Refunded"
							);
						} else {
							$this->newPaymentRecord(
								$request, 
								"Refunded",
								"Refunded"
							);
						}
					
						break;
					
					/**
					 * A payment was reversed due to a chargeback or other type of reversal.
					 * The funds have been removed from your account balance and returned to the buyer.
					 * The reason for the reversal is specified in the ReasonCode element.
					 */	
					case "Reversed":
					
						/* Notify the admin that an order has had an order has been reversed */
					
						/* Notify the customer of a change to their order status */
						Order_Emails::create()->customerOrderStatusUpdate($request->postVar("custom"), "Cancelled");
					
						if($record_count>0) { 
							$this->updatePaymentRecord(
								$request, 
								$payment_record_id,
								"Refunded",
								"Cancelled"
							);
						} else {
							$this->newPaymentRecord(
								$request, 
								"Refunded",
								"Cancelled"
							);
						}
					
						break;
					
					/* The reveral was cancelled */	
					case "Canceled_Reversal":
					
						/* Notify an admin that an order reversal has been cancelled */
						
						/**
						 * We don't send customers an email update for this status as it might
						 * cause confustion.
						*/
					
						/**
						 * For canceled reversals, lets set the order to Pending as an admin will need to manually review it.
						 * we don't want it to fall in the standard Processing queue as goods could be shipped twice.
						 */
						if($record_count>0) { 
							$this->updatePaymentRecord(
								$request, 
								$payment_record_id,
								"Pending",
								"Pending / Awaiting Payment"
							);
						} else {
							$this->newPaymentRecord(
								$request, 
								"Pending",
								"Pending / Awaiting Payment"
							);
						}
					
						break;
						
					/* This authorization has been voided. */
					case "Voided":
					
						/* Notify the customer of a change to their order status */
						Order_Emails::create()->customerOrderStatusUpdate($request->postVar("custom"), "Cancelled");
					
						if($record_count>0) { 
							$this->updatePaymentRecord(
								$request, 
								$payment_record_id,
								"Refunded",
								"Cancelled"
							);
						} else {
							$this->newPaymentRecord(
								$request, 
								"Refunded",
								"Cancelled"
							);
						}
						
						break;
					
					/**
					 * The payment has failed.
					 */
					case "Failed":
					
						/* Notify the customer of a change to their order status */
						Order_Emails::create()->customerOrderStatusUpdate($request->postVar("custom"), "Cancelled");
					
						if($record_count>0) { 
							$this->updatePaymentRecord(
								$request, 
								$payment_record_id,
								"Refunded",
								"Cancelled"
							);
						} else {
							$this->newPaymentRecord(
								$request, 
								"Refunded",
								"Cancelled"
							);
						}
					
						break;
					
					/* Other IPN statuses are ignored. */
					default:
						exit;
						break;
					
				}
				
			} elseif(strcmp ($res, "INVALID") == 0) {
				
				$status = "INVALID";
				
				// log for manual investigation
				// Add business logic here which deals with invalid IPN messages
				
				/* If Debug is enabled, log response */
				if( $debug == 1 ) {
					error_log(date('[Y-m-d H:i e] ')."Invalid IPN: $req".PHP_EOL,	3, "../ipn.log");
				}
				
			}
		
		}
		
	}
	
	/**
	 * newLogEntry 
	 * Creates a new log entry in the file ipn.log in the installations 
	 * top level directory. 
	 *
	 * @return null.
	 */	
	public function newLogEntry($message) {
		$date = date('[Y-m-d H:i e] ');
		$type = 3;
		$file = "../ipn.log";
		error_log( "===\n\n" . $date . "\n" . $message . "\n" . PHP_EOL, $type, $file );
	}
	
	/**
	 * newPaymentRecord
	 * Create a new Order_Payment_PayPal record. 
	 *
	 * @param SS_HTTPRequest $request
	 * @param String $status The value of status field on the new Order_Payment record.
	 * @param String $order_status The SystemTitle of the Order_Status we are to use on the Order record.
	 * @return Boolean
	 */
	public function newPaymentRecord($request, $status, $order_status) {
		
		/* Create new Order_Payment_PayPal Object */
		$n = new Order_Payment_PayPal();
		
		/**
		 * Array of fields values to be set.
		 * Items without a value will use $request->postVar($key) 
		 * in the for loop below.
		 */ 
		$fields = array(
			"Date" => date("Y-m-d"),
			"Status" => $status,
			"Currency" => 1,
			"Amount" => $request->postVar("mc_gross"),
			"OrderID" => $request->postVar("custom"),
			"test_ipn" => null,
			"txn_id" => null,
			"parent_txn_id" => null,
			"txn_type" => null,
			"payer_id" => null,
			"payer_status" => null,
			"custom" => null,
			"payment_date" => null,
			"mc_fee" => null,
			"mc_gross" => null,
			"payment_status" => null,
			"pending_reason" => null,
			"payment_type" => null,
			"reason_code" => null,
			"protection_eligibility" => null
		);
		
		/* Set the fields to their defined values. */
		foreach($fields as $field => $value) {
			$n->$field = ($value == null) ? $request->postVar($field) : $value;
		}
		
		/* Write to the DataObject */
		$created = (is_int($n->write())) ? true : false;
		
		/* Update Order Status */
		$order_status = DB::Query("
			UPDATE
				`order`
			SET 
				`status` = '" . DataObject::get_one("Order_Statuses", "(`SystemTitle`='".$order_status."')")->ID . "'
			WHERE 
				`id` = '" . $request->postVar("custom") . "'
		");
	
		/* If all has been successful, return true. */		
		if($order_status && $created) {
			return Store_Controller::create()->httpError(200);
		} else {
			$this->newLogEntry( 
				"Could not create new payment record".
				"MySQL error in Gateway_PayPal::newPaymentRecord()."
			);
			exit;
		}
		
	}
	 
	/**
	 * updatePaymentRecord
	 * Take the existing Order_Payment_PayPal record and update it with
	 * the data from the current IPN response.
	 *
	 * @param SS_HTTPRequest $request
	 * @param Int $payment_record_id The ID of the Order_Payment_PayPal record we are updating.
	 * @param String $status The value of status field on the new Order_Payment record.
	 * @param String $order_status The SystemTitle of the Order_Status we are to use on the Order record.
	 * @return true
	 */
	public function updatePaymentRecord($request, $payment_record_id, $status, $order_status) {
		
		/**
		 * Array of fields values to be updated.
		 * Items without a value will use $request->postVar($key) 
		 * in the for loop below.
		 */ 
		$fields = array(
			"order_payment.status" => $status,
			"order.status" => DataObject::get_one("Order_Statuses", "(`SystemTitle`='".$order_status."')")->ID,
			"test_ipn" => null,
			"txn_id" => null,
			"parent_txn_id" => null,
			"txn_type" => null,
			"payer_id" => null,
			"payer_status" => null,
			"custom" => null,
			"payment_date" => null,
			"mc_fee" => null,
			"mc_gross" => null,
			"payment_status" => null,
			"pending_reason" => null,
			"payment_type" => null,
			"reason_code" => null,
			"protection_eligibility" => null
		);		
		
		/* Form a string for the SQL SET segment. */
		$set_statement = "";
		$fields_count = count($fields);
		$i = 1;
		foreach($fields as $field_name => $value) {
			$set_statement .= $field_name . "='";
			$set_statement .= ($value == null) ? $request->postVar($field_name) : $value;
			$set_statement .= "'";
			if($i < $fields_count) { $set_statement .= ", "; }
			$i++;
		}
		
		/* Update Order_Payment_PayPal record */
		$record_update = DB::Query("
			UPDATE 
				order_payment_paypal
				LEFT JOIN order_payment ON order_payment.id = order_payment_paypal.id 
				LEFT JOIN `order` ON `order`.`id` = order_payment.OrderID
			SET 
				$set_statement
			WHERE 
				order_payment_paypal.id = '$payment_record_id'
		");
		
		/* If all has been successful, return true. */		
		if($record_update) {
			return Store_Controller::create()->httpError(200);
		} else {
			$this->newLogEntry( 
				"Could not update record".
				"MySQL error in Gateway_PayPal::newPaymentRecord()."
			);
			exit;
		}
		
	}
	
	/**
	 * Return the PayPal Payments Standard Form
	 *
	 * @param Int $order_id The ID of the order we are collecting payment for.
	 * @return Form
	 */
	public function PayPalForm($order_id) {
		
		$Form = Gateway_PayPal_Form::create(Store_OrderController::create() , "PayPalForm", $order_id);	
		
		/* Set Form Action */
		$URL = "https://www.paypal.com/cgi-bin/webscr";
		$SandboxURL = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		$Form->setFormAction((DataObject::get_one("Gateway_PayPal")->Sandbox) ? $SandboxURL : $URL);
		$Form->setFormMethod("POST");

		return $Form;

	} 
	 
}