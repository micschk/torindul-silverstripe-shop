<?php
/**
 * Model to store emails associated to an order and handle the 
 * sending of said emails.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Order_Emails extends DataObject {
	
	private static $singular_name = "Order Email";
	private static $plural_name = "Order Emails";
	
	/**
	 * Database Fields.
	 */
	private static $db = array(
		"SentTo" => "Varchar",
		"Subject" => "Varchar",
		"Body" => "HTMLText",
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
		'Created' => 'Date',
		'SentTo' => "Recipient",
	    'Subject' => 'Email Subject',
	);
	
    /**
	 * Add fields to the CMS.
     */
	public function getCMSFields() {
		
		//Create the FieldList and push the Root TabSet on to it.
		$fields = FieldList::create(
			
			$root = TabSet::create(
				'Root',
				
				Tab::create(
					"Main",
					
					HeaderField::create("Order Email"),
					CompositeField::create(
						
						ReadonlyField::create("OrderID", "Order ID")
						->setRightTitle("The order this email is related to."),
						
						ReadonlyField::create("Created", "Email Sent On"),
						
						ReadonlyField::create("SentTo", "Email Recipient"),
						
						ReadonlyField::create("Subject", "Email Subject"),
						
						FieldGroup::create("Email Content",
						
							LiteralField::create("Body", $this->Body)
							
						)
						
					)
					
				)
				
			)
		
		);
		
		return $fields;
		
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
		$file = "../order_errors.log";
		error_log( "===\n\n" . $date . "\n" . $message . "\n" . PHP_EOL, $type, $file );
	}
	
	/* adminNewOrderNotification
	 * Notify the admin notification email address of a new order. 
	 *
	 * @param Int $order_no The order to notify of.
	 * @return Boolean
	 */
	public function adminNewOrderNotification($order_no) { 
		
		//Get Store Settings
		$settings = StoreSettings::get_settings();
		
		//Admin notification email
		$admin_email = $settings->EmailNotification_AdminNewOrder;
		
		/**
		 * If the notifcation email isn't present, fail and write to the order log.
		 * Otherwise, continue to send the admin an order notification.
		 */
		if(!$admin_email || is_null($admin_email) || $admin_email=="") {
			$this->newLogEntry( "Order notification for order number " . $order_no . " could not be sent. No admin notification email provided.");
			return false;
		} else {
			
			//Get the order
			$order = DataObject::get_by_id("Order", $order_no);
			
			$email = new Email();
			$email
			    ->setFrom( $settings->EmailNotification_SendEmailsFrom )
			    ->setTo( $admin_email )
			    ->setSubject( 'New Order [' . $order_no . '] on ' . $settings->StoreSettings_StoreName)
			    ->setTemplate('Email_Order_Admin_NewNotification')
			    ->populateTemplate(new ArrayData(array(
			        'StoreName' => $settings->StoreSettings_StoreName,
			        'Order' => $order,
			        'OrderItems' => DataObject::get("Order_Items", "(`OrderID`='" . $order->ID . "')"),
			        'ShippingAddress' => DataObject::get_one("Customer_AddressBook", "(`id`='" . $order->BillingAddressID . "')"), 
			        'OrderLink' => StoreOrdersAdmin::create()->Link() . "/getEditForm/field/AllOrders/item/" . $order_no . "/edit",
			    )));
			$email->send();
			
			//Store the email in the order email log
			$this->SentTo = $admin_email . " (ADMIN)";
			$this->Subject = 'New Order [' . $order_no . '] on ' . $settings->StoreSettings_StoreName;
			$this->Body = $email->body;
			$this->OrderID = $order_no;
			$this->write();
			
			return true;
			
		}
		
		
	}
	
	/* customerNewOrderConfirmation
	 * Notify the customer by email of their new order but,
	 * if this request is a duplicate and they've already 
	 * received it, then ignore.
	 *
	 * @param Int $order_no The order to notify of.
	 * @param String $type Normally null but if equal to 'Pending' we change the outbound email template.
	 * @return Boolean
	 */
	public function customerNewOrderConfirmation($order_no, $type=null) { 
		
		//Get Store Settings
		$settings = StoreSettings::get_settings();
		
		//Get the details of both the order and customer who placed it.
		$order = DataObject::get_by_id("Order", $order_no);
		$customer = DataObject::get_by_id("Customer", $order->CustomerID);
		
		//Only send this email if the customer is yet to receive it.
		if($order->ConfirmationEmailSent==0) {
			
			//Send The Email
			$email = new Email();
			$email
			    ->setFrom( $settings->EmailNotification_SendEmailsFrom )
			    ->setTo( $customer->Email )
			    ->setSubject( 'Your ' . $settings->StoreSettings_StoreName . ' Order [' . $order_no . ']' )
			    ->setTemplate( ($type=='Pending') ? 'Email_Order_Confirmation_Pending' : 'Email_Order_Confirmation' )
			    ->populateTemplate(new ArrayData(array(
			        'StoreName' => $settings->StoreSettings_StoreName,
			        'Order' => $order,
			        'OrderItems' => DataObject::get("Order_Items", "(`OrderID`='" . $order->ID . "')"),
			        'OrderCourier' => DataObject::get_one("Courier", "(`id`='" . $order->Courier . "')")->Title,
			        'OrderLink' => '',
			        'ProductTax' => StoreCurrency::convertToCurrency(($order->calculateProductTax(1) + $order->calculateProductTax(2))),
			        'BillingAddress' => DataObject::get_one("Customer_AddressBook", "(`id`='" . $order->BillingAddressID . "')"),
			        'ShippingAddress' => DataObject::get_one("Customer_AddressBook", "(`id`='" . $order->BillingAddressID . "')"),			        
			        'Customer' => $customer,
			        'CurrencySymbol' => Product::getDefaultCurrency(),
			    )));
			$email->send();
			
			//Store the email in the order email log
			$this->SentTo = $customer->Email . " (CUSTOMER)";
			$this->Subject = 'Your ' . $settings->StoreSettings_StoreName . ' Order [' . $order_no . ']';
			$this->Body = $email->body;
			$this->OrderID = $order_no;
			$this->write();
			
			//Set the ConfirmationEmailSent value to 1 if $type does not equal pending to prevent future confirmation emails
			if($type!="Pending") { 
				DB::Query("UPDATE `order` SET `ConfirmationEmailSent`='1' WHERE `id`='" . $order_no . "'");
			}
			
			return true;
			
		} else {
			return false;
		}
			
	}
	
	/* customerOrderStatusUpdate
	 * Notify the customer by email of a change to their orders status.
	 * Only sent the email should the store notification settings dictate we
	 * send order status emails for the given status.
	 *
	 * @param Int $order_no The order in question.
	 * @param String $status The SystemTitle of the new order status
	 * @param Boolean $orderride If true, ignore the store notification settings and send anyway.
	 * @return Boolean
	 */
	public function customerOrderStatusUpdate($order_no, $status, $override=false) {
		
		//Get Store Settings
		$settings = StoreSettings::get_settings();
		
		//Get the details of both the order, the customer who placed it and the status
		$order = DataObject::get_by_id("Order", $order_no);
		$customer = DataObject::get_by_id("Customer", $order->CustomerID);
		$status = DataObject::get_one("Order_Statuses", "(`SystemTitle`='" . $status . "')");
		
		/**
		 * If override is set, set the send flag to true.
		 * Otherwise, check the store notification settings tpo
		 * see if we're allowed to send this status update email. 
		 */
		if($override) { 
			$send = true; 
		} else {
			
			//Convert store notification settings to array
			$enabled_statuses = explode(",", $settings->EmailNotification_OrderStatuses);
			
			//Is the new status in this array, if yes, send send to true.
			$send = (in_array($status->ID, $enabled_statuses)) ? true : false;
			
		}
		
		/**
		 * If $send is equal to true send the email notification.
		 */
		if($send) {
			
			//Send The Email
			$email = new Email();
			$email
			    ->setFrom( $settings->EmailNotification_SendEmailsFrom )
			    ->setTo( $customer->Email )
			    ->setSubject( 'Order [' . $order_no . '] has been updated' )
			    ->setTemplate( 'Email_Order_StatusUpdate' )
			    ->populateTemplate(new ArrayData(array(
			        'StoreName' => $settings->StoreSettings_StoreName,
			        'Customer' => $customer,
			        'Order' => $order,
			        'OrderStatus' => $status,
			        'OrderLink' => '',
			        'OrderItems' => DataObject::get("Order_Items", "(`OrderID`='" . $order->ID . "')"),
			        'OrderCourier' => DataObject::get_one("Courier", "(`id`='" . $order->Courier . "')")->Title,
			        'OrderTrackingNo' => ($order->TrackingNo) ? $order->TrackingNo : "No tracking number provided for this order",
			        'ProductTax' => StoreCurrency::convertToCurrency(($order->calculateProductTax(1) + $order->calculateProductTax(2))),		        
			        'BillingAddress' => DataObject::get_one("Customer_AddressBook", "(`id`='" . $order->BillingAddressID . "')"),
			        'ShippingAddress' => DataObject::get_one("Customer_AddressBook", "(`id`='" . $order->BillingAddressID . "')"), 
			        'CurrencySymbol' => Product::getDefaultCurrency(),
			    )));
			$email->send();
			
			//Store the email in the order email log
			$this->SentTo = $customer->Email . " (CUSTOMER)";
			$this->Subject = 'Order [' . $order_no . '] has been updated';
			$this->Body = $email->body;
			$this->OrderID = $order_no;
			$this->write();
			
			return true;
			
		} else {
			return false;
		}
		
	}
	
	public function canDelete($member = null) { return false; }
	
}