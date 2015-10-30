<?php
/**
 * Model to store both the system default and custom tax classes.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class TaxClasses extends DataObject {
	
	private static $singular_name = "Tax Class";
	private static $plural_name = "Tax Classes";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"SystemCreated" => "Int"
	);
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array(
		"SystemCreated" => "0"
	);
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"Title" => "Class Name",
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

					HeaderField::create("Add/Edit Tax Class"),
					CompositeField::create(
						
						TextField::create(
							"Title",
							"Class Name"
						)
						->setRightTitle("i.e. Zero Rate, Standard Rate.")
					
					)
					
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
	 * TODO - Dependency checks before deleting. DONT ALLOW DELETION IF CLASS IS IN USE WITHIN A ZONE.
	 */
	protected function onBeforeDelete() { parent::onBeforeDelete(); }
	
	public function canView( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0; }
	public function canDelete( $member = null ) {
		
		if($this->SystemCreated==1) { 
			return 0; 
		} else {
			return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0;
		}
		
	}
		
	/**
	 * Add default records to database. This function is called whenever the
	 * database is built, after the database tables have all been created.
	 * 
	 * @uses DataExtension->requireDefaultRecords()
	 */
	public function requireDefaultRecords() {
		 
		/* Inherit Default Record Creation */
		parent::requireDefaultRecords();
		 
		/* If no records exist, create defaults */
		if( !DataObject::get_one( get_class($this) )  ) {
			
			$defaults = array(
				"Standard Rate",
				"Zero Rate",
				"Non-Taxable Goods",
				"Reduced Rate",
				"Shipping",
			);
			 
			foreach($defaults as $value) {
				 
				$n = new TaxClasses();
				$n->Title = $value;
				$n->SystemCreated = "1";
				$n->write();
				unset($n);
			 
			}
			
			DB::alteration_message('Created default tax classes', 'created');
			 		 
		}
		
	}


}