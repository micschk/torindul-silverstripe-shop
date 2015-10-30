<?php
/**
 * Model to store custom fields defined in a product.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Product_CustomFields extends DataObject {
	
	private static $singular_name = "Custom Field";
	private static $plural_name = "Custom Fields";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"Value" => "Varchar",
	);
	
	/**
	 * Specific Has One Relationships 
	 */
	private static $has_one = array(
		"Product" => "Product"	
	);
	
	/** 
	 * Set defaults on record creation in the database 		
	*/
	private static $defaults = array();
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"Title" => "Field Name",
		"Value" => "Field Value"
	);
	
    /**
     * getCMSFields
	 * Customise the FieldList used in the CMS.
     * 
     * @return FieldList
     */
	public function getCMSFields() {
		
	    Requirements::css('torindul-silverstripe-shop/css/LeftAndMain.css');
		
		/*
		 * In order to keep the automatic construction of the has_one relationship in the backgroud we will make 
		 * use of the parent's getCMSFields() and not create our own FieldList as in other models 
		 */
		$fields = parent::getCMSFields();
		
		//Remove fields
		$fields->removeFieldsFromTab(
			"Root.Main",
			array(
				"ProductID",
				"Title",
				"Value",
			)
		);
		
		$fields->addFieldsToTab(
			"Root.Main",
			array(
					
				HeaderField::create("Add/Edit Custom Field"),
				CompositeField::create(
					
					TextField::create(
						"Title",
						"Custom Field Name"
					)
					->setRightTitle("The description for the field as shown to site visitors. i.e. ISBN Number."),
					
					TextField::create(
						"Value",
						"Custom Field Value"
					)
					->setRightTitle("The value for this custom field. i.e. 9788071457060")
						
				),
			
			)
		);
		
		return $fields;
		
	}
	
	/**
	 * Set the required fields 
	 */
	public static function getCMSValidator() {
		return RequiredFields::create( 
			array(
				"Title",
				"Value"
			)
		);
	}
	
	public function canView( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canDelete( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }

}