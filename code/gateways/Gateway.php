<?php
/** 
 * Defines the base class for Gateways within the store.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Gateway extends DataObject {
	
	private static $singular_name = "Payment Gateway";
	private static $plural_name = "Payment Gateways";
	
    /**
     * check_criteria_met
     * Is the given order eligible for this gateway?
     * 
     * @param int $order_no  
     * @return boolean
     */
	public static function check_criteria_met($order_no) {}	

	/**
	 * Define the base database fields for this gateway.
	 * This can be extended in the gateway classes themselves to
	 * provide a level of configuration storage.
	 *
	 * @see Gateway_PayPal For an example of gateway specific fields.
	 */
	private static $db = array(
		"SystemName" => "Varchar",
		"Title" => "Varchar",
		"Enabled" => "Int"
	);	
	
	/* Set defaults on record creation in the database */
	private static $defaults = array( 
		"Enabled" => "0",
	);
	
	/* Specify fields to display in GridFields */	
	public static $summary_fields = array(
		"getEnabledText" => "Enabled",
		"SystemName" => "Gateway Name",
		"Title" => "Friendly Name"
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

					HeaderField::create($this->SystemName),
					CompositeField::create(
						
						TextField::create("Title", "Friendly Name")
						->setRightTitle("This is the name your customers would see against this payment gateway."),
						
						DropdownField::create("Enabled", "Enable this payment gateway?", array("0" => "No", "1" => "Yes"))
						->setEmptyString("(Select one)")
						->setRightTitle("Can customers pay with this method?")
					
					)
					
				)
			
			)
			
		);
		
		return $fields;
		
	}
	
	/**
	 * getGateways
	 * Create Gateways List - To do this we are going to fetch all
	 * gateways in the Gateway DataObject and display them as
	 * valid options where:
	 * 
	 *	a) The gateways' Enabled field is set to 1
	 *	b) The gateways' checkCriteriaMet() method returns true.
	 *
	 * @param Object $order Order to use.
	 * @param Boolean $admin If true, replace Gateway_ with Order_Payment_ for use in the CMS.
	 * @return Array
	 */
	public function getGateways($order, $admin=null) {
		
		if($order) {
			
			//Variable to hold available gateways
			$gateways = array();
			
			//Fetch all Enabled Couriers
			$query = new SQLQuery();
			$query->setFrom('Gateway')->addWhere("(`Enabled`='1')");
			
			//Result of query
			$result = $query->execute();
			
			//Iterate over each courier...
			foreach($result as $row) { 
				
				//Class of the Gateway
				$ClassName = $row["ClassName"];
				
				//If the criteria is met for this gateway, add it to the gateways array.
				if( $ClassName::check_criteria_met( $order->ID ) ) {
					
					if($admin) $ClassName = str_replace("Gateway_", "Order_Payment_", $ClassName);
					
					//Set the ClassName as the key and the Friendly Name as the value.
					$gateways[$ClassName] = $row["Title"];
					
				}
			
			}
			
			return $gateways;		
			
		}		
		
	}
	
    /**
     * getCMSValidator
     * Return an Array of required fields which extensions of
     * gateway must use when invoking RequiredFields with an array_merge.
     * 
     * @see Gateway_PayPal For an example of invoking RequiredFields.
     * @return Array
     */
	public static function getCMSValidator() {		
		return array("Title");
	}
	
    /**
     * getEnabledText
	 * Translates a numerical boolean to Yes/No.
     * 
     * @return String
     */
	public function getEnabledText() {
		return ($this->Enabled==0) ? "No" : "Yes";
	}
		
	/**
	 * TODO - Dependency checks before deleting
	 */
	public function onBeforeDelete() { parent::onBeforeDelete(); }
	
	public function canView( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }	
	public function canCreate( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }	
	public function canDelete( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }

}