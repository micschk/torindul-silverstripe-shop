<?php
/** 
 * Defines the base class for Couriers within the store.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Courier extends DataObject {
	
	private static $singular_name = "Courier";
	private static $plural_name = "Couriers";
	
    /**
     * check_criteria_met
     * Is the given order eligible for this courier?
     * 
     * @param int $order_no  
     * @return boolean
     */
	public static function check_criteria_met($order_no) {}	
	
    /**
     * calculate_shipping_total
     * Given the order in question, how much does shipping cost when using this courier?
     * 
     * @param int $order_no  
     * @return float
     */
	public static function calculate_shipping_total($order_no) {}

	/**
	 * Define the base database fields for this courier.
	 * This can be extended in the courier classes themselves to
	 * provide a level of configuration storage.
	 *
	 * @see Courier_FreeShipping For an example of Courier specific fields.
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
		"SystemName" => "Courier Name",
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
						->setRightTitle("This is the name your customers would see against this courier."),
						
						DropdownField::create("Enabled", "Enable this courier?", array("0" => "No", "1" => "Yes"))
						->setEmptyString("(Select one)")
						->setRightTitle("Can customers use this courier?")
					
					)
					
				)
			
			)
			
		);
		
		return $fields;
		
	}
	
    /**
     * getCMSValidator
     * Return an Array of required fields which extensions of
     * courier must use when invoking RequiredFields with an array_merge.
     * 
     * @see Courier_FreeShipping For an example of invoking RequiredFields.
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
	protected function onBeforeDelete() { parent::onBeforeDelete(); }
	
	public function canView( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }
	public function canEdit( $member = null ) {	return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }
	public function canDelete( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }

}