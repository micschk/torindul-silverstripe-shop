<?php
/**
 * Model to store product brands
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Product_Brands extends DataObject {
	
	private static $singular_name = "Product Brand";
	private static $plural_name = "Product Brands";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"URLSegment" => "Text"
	);	
	
	/**
	 * Specifiy Has One Relationships 
	 */
	private static $has_one = array(
		"Logo" => "Image"	
	);
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array();
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"getBrandLogo" => "Brand Logo",
		"Title" => "Brand Name",
		"URLSegment" => "URL Segment"
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
				"Title",
				"Logo",
				"URLSegment",
			)
		);
		
		$fields->addFieldsToTab(
			"Root.Main",
			array(
					
				HeaderField::create("Add/Edit Product Brand"),					
				CompositeField::create(
					
					TextField::create(
						"Title",
						"Brand Name"
					)
					->setRightTitle("Enter a brand name."),
					
					UploadField::create(
						"Logo",
						"Brand Logo (optional)"
					)
					->setAllowedFileCategories('image')
					->setAllowedMaxFileNumber(1)
					->setFolderName('product-brands')
						
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
			)
		);
	}
	
	/**
	 * COPIED FROM SITETREE
	 *
	 * Generate a URL segment based on the title provided.
	 * 
	 * @param string $title Product title
	 * @return string Generated url segment
	 */
	public function generateURLSegment($title){
		$filter = URLSegmentFilter::create();
		$t = $filter->filter($title);
		
		// Fallback to generic page name if path is empty (= no valid, convertable characters)
		if(!$t || $t == '-' || $t == '-1') $t = "page-$this->ID";
		
		// Hook for extensions
		$this->extend('updateURLSegment', $t, $title);
		
		// Check to see if URLSegment exists already, if it does, append -* where * is COUNT()+1
		$seg = new SQLQuery('COUNT(*)');
		$seg->setFrom( get_class($this) )->addWhere("`URLSegment` LIKE '%$t%'");
		$count = $seg->execute()->value();
		if($count > 0) { 
			$count++; 
			return $t . "-" . $count;
		} else {
			return $t;
		}
		
	}
	
	/**
	 * Product Photo to display in the GridField 
	 */
	public function getBrandLogo() {
		return ($this->Logo()->ID) ? $this->Logo()->setWidth('75') : "No Image";
	}
	
	/** 
	 * onBeforeWrite
	 * Create SEO Friendly URLSegment
	 */
	protected function onBeforeWrite() {
		
		parent::onBeforeWrite();
		
		/* If no URLSegment is set, set one */
		if(!$this->URLSegment && $this->Title) {
			$this->URLSegment = $this->generateURLSegment( $this->Title );
		}
		
		/* If there is a URLSegment already and the Product Title has changed, update it. */
		else if( $this->isChanged('Title') ) {
			$this->URLSegment = $this->generateURLSegment( $this->Title );
		}
		
	}
	
	/**
	 * TODO - Dependency checks before deleting. DO NOT ALLOW DELETION IF BRAND IS IN USE ON PRODUCTS.
	 */
	protected function onBeforeDelete() { parent::onBeforeDelete(); }
	
	public function canView( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canDelete( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }

}