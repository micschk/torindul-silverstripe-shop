<?php
/**
 * Model to store both the system default and custom tax zones.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class TaxZones extends DataObject {
	
	private static $singular_name = "Tax Zone";
	private static $plural_name = "Tax Zones";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Enabled" => "Int",
		"Title" => "Varchar",
		"SystemCreated" => "Int"
	);	
	
	/**
	 * Specify Has Many Relationships 
	 */
	private static $has_many = array(
		"TaxRates" => "TaxRates", 
	);
	
	/* Specify fields to display in GridFields */	
	public static $summary_fields = array(
		"getCountry" => "Zone Country",
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
		
		//Create the FieldList and push the Root TabSet on to it.
		$fields = FieldList::create(
			
			$root = TabSet::create(
				'Root',
				
				Tab::create(
					"Main",

					HeaderField::create("Add/Edit Tax Zone"),
					CompositeField::create(
					
						//Show enabled dropdown as editable if not the default zone
						DropdownField::create(
							"Enabled",
							"Enable this zone?",
							array(
								"1" => "Yes",
								"2" => "No"
							)
						)
						->setRightTitle( 
							( $this->SystemCreated==1 && $this->exists() ) ? 
							"DISABLED: You can not disable the default tax zone." : 
							"If enabled your store will use the rates defined in this zone for customers in the selected country." 
						)		
						->setDisabled( ( $this->SystemCreated==1 && $this->exists() ) ? true : false ),
						
						//Show the Zone country dropdown if not System Created
						( !$this->exists() ) ?
						
							CountryDropdownField::create(
								"Title",
								"Country"
							)
							->setRightTitle( 
								( $this->SystemCreated==1 && $this->exists() ) ? 
								"DISABLED: You can not select a country as this zone applies to all countries." : 
								"Select the country this zone applies to." 
							)				
							->setDisabled( ( $this->SystemCreated==1 && $this->exists() ) ? true : false )
							
						:"",
						
						//Show the Tax Rates GridField only if the Zone exists in the database first.
						($this->exists()) ?
						
							GridField::create(
								"TaxZones_TaxRates",
								"Tax Rates within the '" . $this->Title . "' Tax Zone",
								$this->TaxRates(),
								GridFieldConfig_RecordEditor::create()
							)
							
						:""
					
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
	 * Get Country Flag and Name for Summary Fields 
	 *
	 * @return LiteralField
	 */
	public function getCountry() {
		
		if($this->Title && $this->Title!="All") {
			
			//Convert country name to lowercase for file name
			$country = strtolower( $this->Title );
			
			//Location of icon
			$img = 'torindul-silverstripe-shop/images/icons/flags/' . $country . '.png';
			
			//Construct img tab
			$img = '<img src='.$img.' alt='.$this->Title.' />';
			
			//Add country name to the title
			$img = $img . " " . $this->Title;
			
			//Return to the summary field
			return LiteralField::create("Flag", $img);
			
		}
		
		else {
			return $this->Title;
		}
		
	}
	
	/**
	 * Set SystemCreated to 0 when creating new DataObject record 
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->SystemCreated = 0;
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
			
			$n = new TaxZones();
			
			$n->Enabled = "1";
			$n->Title = "All";
			$n->SystemCreated = "1";
			
			$n->write();
			
			unset($n);
			
			DB::alteration_message('Created default store tax zone', 'created');
			 		 
		}
		
	}


}