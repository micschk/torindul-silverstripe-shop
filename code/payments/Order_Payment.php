<?php
/**
 * Model to store order payment information
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Order_Payment extends DataObject {
	
	private static $singular_name = "Payment";
	private static $plural_name = "Payments";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		'Date' => 'Date',
		'Status' => "Varchar",
		'Amount' => 'Decimal(10,2)',
		'Currency' => 'Int'
	);	
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"Date.Nice" => "Payment Date",
		"getPaymentMethod" => "Payment Method",
		"Amount" => "Payment Amount",
		"Status" => "Payment Status"
	);
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array();
	
	/**
	 * Has One Relationship 
	 */
	public static $has_one = array(
		"Order" => "Order"
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
		
		//Create the FieldList and push the Root TabSet on to it.
		$fields = FieldList::create(
			
			$root = TabSet::create(
				'Root',
				
				Tab::create(
					"Main",
					
					HeaderField::create("Add/Edit Payment")
					
				)
				
			)
			
		);
		
		/* 
		 * If a Payment Gateway/Method hasn't been selected yet, prompt for one.
		 * Otherwise, show the form fields for the database fields in the Order_Payment DataObject.
		*/
		if(!$this->exists()) {
			
			$fields->addFieldsToTab("Root.Main", array(
				
				CompositeField::create(
					
					DropdownField::create("ClassName", "Payment Method", Gateway::create()->getGateways( $this->Order(), true ) )
					->setRightTitle("Which payment method did the customer pay with?")
					->setEmptyString("(Select one)")
					
				)
					
			));
			
		} else {
			
			$fields->addFieldsToTab("Root.Main", array(
				
				CompositeField::create(
					
					DateField::create("Date", "Payment Date")
					->setConfig('dateformat', 'dd-MM-yyyy')
					->setConfig('showcalendar', true)
					->setRightTitle('To open a pop-up calendar click on the text field above.'),
					
					DropdownField::create("Status", "Payment Status", array(
						"Pending" => "Pending",
						"Processing" => "Processing",
						"Denied" => "Denied",
						"Completed" => "Completed",
						"Refunded" => "Refunded"
					)),
					
					FieldGroup::create("Amounts",
					
						NumericField::create("Amount", "Payment Amount")
						->setRightTitle("How much was paid?"),
						
						DropdownField::create(
							"Currency", 
							"Payment Currency", 
							DataObject::get("StoreCurrency", "(`Enabled`=1)")->map("ID", "Code")
						)
						->setRightTitle("In which currency was the payment?")
						
					)
					
				)				
				
			));
			
		}
		
		return $fields;
		
	}
	
	/**
	 * Specifiy which form fields are required 
	 */
	public static function getCMSValidator() {
		return RequiredFields::create( 
			array()
		);
	}
	
	/**
	 * getPaymentMethod 
	 * Return the Friendly Name of a given Payment Gateway
	 *
	 * @return String The friendly name of the payment gateway
	 */
	public function getPaymentMethod() {
		
		//The ClassName of the Payment Method
		$ClassName = $this->ClassName;
		
		//Strip Order_Payment_ and replace with Gateway_
		$ClassName = str_replace("Order_Payment_", "Gateway_", $ClassName);
		
		//Get the DataObject for the new ClassName
		$Gateway = DataObject::get_one($ClassName);
		
		//Return Friendly Name
		return $Gateway->Title;
		
	}
	
	/**
	 * TODO - Prevent duplciate payment records.
	 */
	protected function onBeforeWrite() {
		parent::onBeforeWrite();
	}
		
	/**
	 * TODO - Dependency checks before deleting
	 */
	protected function onBeforeDelete() {
		parent::onBeforeDelete();
	}
	
	public function canView( $member = null ) { return ( Permission::check("SHOP_ACCESS_Orders") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("SHOP_ACCESS_Orders") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("SHOP_ACCESS_Orders") ) ? 1 : 0; }
	public function canDelete( $member = null ) { return ( Permission::check("SHOP_ACCESS_Orders") ) ? 1 : 0; }

}