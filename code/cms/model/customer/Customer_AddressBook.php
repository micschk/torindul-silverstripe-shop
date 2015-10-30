<?php
/**
 * Model to store customer addresses.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Customer_AddressBook extends DataObject {
	
	private static $singular_name = "Address";
	private static $plural_name = "Addresses";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"FirstName" => "Varchar",
		"Surname" => "Varchar",
		"CompanyName" => "Varchar",
		"PhoneNumber" => "Varchar",
		"AddressLine1" => "Varchar",
		"AddressLine2" => "Varchar",
		"City" => "Varchar",
		"StateCounty" => "Varchar",
		"Country" => "Varchar",
		"Postcode" => "Varchar",
		"AddressType" => "Int"
	);	
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array();
	
	/** Set the has one relationships
	 * 
	 * We use 'Member' as the class here, instead of 'Customer', to prevent strange behaviour encountered
	 * when using the 'Customer' class. Furthermore, we use onBeforeWrite() to set the Data Model relationship
	 * field 'CustomerID' as we also experienced some strange behaviour with SilverStripe's default ability to set
	 * this upon calling DataOject::create() 
	 *
	 */
	private static $has_one = array(
		"Customer" => "Member" 
	);
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"getNiceAddressType" => "Address Type",
 		"AddressLine1" => "Address Line 1",
		"City" => "City",
		"StateCounty" => "State / County",
		"Postcode" => "Postcode",
		"Country" => "Country"
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
		
		/**
		 * In order to keep the automatic construction of the has_one relationship in the backgroud we will make 
		 * use of the parent's getCMSFields() and not create our own FieldList as in other models 
		 */
		$fields = parent::getCMSFields();
		
		//Remove fields
		$fields->removeFieldsFromTab(
			"Root.Main", 
			array(
				"FirstName",
				"Surname",
				"CompanyName",
				"PhoneNumber",
				"AddressLine1",
				"AddressLine2",
				"City",
				"StateCounty",
				"Country",
				"Postcode",
				"AddressType",
				"CustomerID"			
			)
		);
		
		$fields->addFieldsToTab(
			"Root.Main",
			array(
				
				HeaderField::create("Add/Edit Address"),
				CompositeField::create(
					
					DropdownField::create(
						"AddressType",
						"Address Type",
						array(
							"1" => "Residential",
							"2" => "Commercial"
						)
					),
					
					TextField::create("FirstName", "First Name"),
					TextField::create("Surname", "Surname"),
					TextField::create("CompanyName", "Company Name"),
					TextField::create("AddressLine1", "Address Line 1"),
					TextField::create("AddressLine2", "Address Line 2"),
					TextField::create("City", "Town/City"),
					TextField::create("StateCounty", "State/County"),
					TextField::create("Postcode", "Zip/Postcode"),
					CountryDropdownField::create("Country", "Country"),
					TextField::create("PhoneNumber", "Contact Number")
					
				)
					
			)		
		);
		
		return $fields;
		
	}
	
	/**
	 * Specific which form fields are required 
	 */
	public static function getCMSValidator() {
		return RequiredFields::create(
			array(
				"FirstName",
				"Surname",
				"AddressLine1",
				"City",
				"StateCounty",
				"Country",
				"Postcode",
				"AddressType",
				"PhoneNumber"
			)
		);
	}
	
	/**
	 * As the Title field does not exist on this
	 * object, lets use the customers full address 
	 * in its place.
	 */
	public function getTitle() {
		$addressline2 = ($this->AddressLine2) ? $this->AddressLine2 . ", " . $this->City : $this->City;
		return $this->FirstName . " " . $this->Surname . ", " . $this->AddressLine1 . ", " . $addressline2 . ", " . $this->StateCounty . ", " . $this->Postcode . ", " . $this->Country;
	}
	
	/**
	 * Convert the boolean address type in $this->AddressType
	 * to a user friendly 'Residential' and 'Commercial'.
	 */
	public function getNiceAddressType() {
		return ($this->AddressType<2) ? "Residential" : "Commercial";
	}
	
	/**
	 * TODO - Dependency checks before deleting
	 */
	protected function onBeforeDelete() { parent::onBeforeDelete(); }
	
	/**
	 * PREVENTATIVE MEASURE
	 *
	 * To prevent some undesirable behaviour where the $has_one relationship
	 * field 'CustomerID' wasnt being set properly, we shall manually set its
	 * value to $this->Customer()->ID before we write it to the database. 
	 */
	protected function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->CustomerID = $this->Customer()->ID;
	}
	
	public function canView( $member = null ) { return ( Permission::check("SHOP_ACCESS_Customers") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("SHOP_ACCESS_Customers") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("SHOP_ACCESS_Customers") ) ? 1 : 0; }
	public function canDelete( $member = null ) { return ( Permission::check("SHOP_ACCESS_Customers") ) ? 1 : 0; }

}