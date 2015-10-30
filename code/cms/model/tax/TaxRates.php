<?php
/**
 * Model to store both tax rates against a has_one tax zone relationship
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class TaxRates extends DataObject {
	
	private static $singular_name = "Tax Rate";
	private static $plural_name = "Tax Rates";	

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"TaxClass" => "Varchar",
		"Rate" => "Varchar",
		"SystemCreated" => "Int"
	);	
	
	/**
	 * Specify Has One Relationships 
	 */
	private static $has_one = array(
		"TaxZone" => "TaxZones",
	);
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array(
		"SystemCreated" => "0"
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
				"TaxClass",
				"Rate",
				"TaxZoneID",
				"SystemCreated"
			)
		);
		
		$fields->addFieldsToTab(
			"Root.Main",
			array(
					
				HeaderField::create("Add/Edit Tax Rate"),
				CompositeField::create(
					
					TextField::create(
						"Title",
						"Friendly Name"
					)
					->setRightTitle("Enter a friendly name for this Tax Rate. i.e. VAT or GST."),
						
					DropdownField::create(
						"TaxClass",
						"Tax Class",
						DataObject::get("TaxClasses", "", "Title ASC")->map("ID", "Title")
					)
					->setRightTitle("Select the Tax class you wish to set the rate for?"),
					
					TextField::create(
						"Rate",
						"Tax Rate"
					)
					->setRightTitle("Enter the rate of Tax without the % sign, i.e. 20 for 20%.")
						
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
				"TaxClass",
				"Rate",
			)
		);
	}
		
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"Title" => "Rate Name",
		"getTaxClassName" => "Rate Class",
		"gettidyRate" => "Rate Percentage",
	);
	
	/**
	 * Get Class Name for Summary Fields 
	 * 
	 * @return String Tax class name
	 */
	public function getTaxClassName() {
		
		if($this->TaxClass) {
		
			$sqlQuery = new SQLQuery("Title");
			$sqlQuery->setFrom('TaxClasses')->addWhere('ID='.$this->TaxClass.'');
			return $sqlQuery->execute()->value();
			
		}
		
	}
	
	/**
	 * gettidyRate
	 * Add percentage sign to the rate for Summary Fields 
	 *
	 * @return String
	 */
	public function getgettidyRate() {
		return $this->Rate . "%";
	}
	
	/**
	 * TODO - Dependency checks before deleting
	 */
	protected function onBeforeDelete() {
		parent::onBeforeDelete();
	}
	
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
	
				array(
					"Title" => "VAT",
					"TaxClass" => "1",
					"Rate" => "0",
					"TaxZoneID" => "1",
					"SystemCreated" => "1"
				),
				
				array(
					"Title" => "Zero Rate",
					"TaxClass" => "2",
					"Rate" => "0",
					"TaxZoneID" => "1",
					"SystemCreated" => "1"
				),
				
				array(
					"Title" => "Non-Taxable Goods",
					"TaxClass" => "3",
					"Rate" => "0",
					"TaxZoneID" => "1",
					"SystemCreated" => "1"
				),
				
				array(
					"Title" => "Reduced Rate",
					"TaxClass" => "4",
					"Rate" => "0",
					"TaxZoneID" => "1",
					"SystemCreated" => "1"
				),
				
				array(
					"Title" => "Shipping Tax",
					"TaxClass" => "5",
					"Rate" => "0",
					"TaxZoneID" => "1",
					"SystemCreated" => "1"
					
				)
				
			);
			 
			foreach($defaults as $default) {
				 
				$n = new TaxRates();
				$n->Title = $default["Title"];
				$n->TaxClass = $default["TaxClass"];
				$n->Rate = $default["Rate"];
				$n->TaxZoneID = $default["TaxZoneID"];
				$n->SystemCreated = $default["SystemCreated"];
				$n->write();
				unset($n);
			 
			}
			
			DB::alteration_message('Created default tax rates' , 'created');	
			
		}
		
	}


}