<?php
/**
 * Model to store product reviews for a product.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Product_Reviews extends DataObject {
	
	private static $singular_name = "Product Review";
	private static $plural_name = "Product Reviews";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"Date" => "Date",
		"Author" => "Varchar",
		"Content" => "Text"
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
	private static $defaults = array(
		"Author" => "Anonymous"
	);
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"Title" => "Review Title",
		"Date" => "Review Date"
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
				"Author",
				"Date",
				"Content"
			)
		);
		
		$fields->addFieldsToTab(
			"Root.Main",
			array(
					
				HeaderField::create("Add/Edit Custom Field"),
				CompositeField::create(
					
					TextField::create(
						"Title",
						"Review Title"
					)
					->setRightTitle("A title related to the content of the review. i.e. 'Awesome Purchase'."),
					
					DateField::create(
						"Date",
						"Date of Review"
					)
					->setRightTitle("The date this review was made. Format as DD-MM-YYYY.")
					->setConfig('dateformat', 'dd-MM-yyyy')
					->setConfig('showcalendar', true),
					
					TextareaField::create(
						"Content",
						"Review Content"
					)
					->setRightTitle("The contents of the review.")
						
				)
			
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
				"Date",
				"Author",
				"Content",
			)
		);
	}
	
	public function canView( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canDelete( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }

}