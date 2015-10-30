<?php
/**
 * Model to store both the system default and custom currencies.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class StoreCurrency extends DataObject {
	
	private static $singular_name = "Currency";
	private static $plural_name = "Currencies";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"Enabled" => "Int",
		"Code" => "Varchar",
		"ExchangeRate" => "Decimal(3,2)",
		"Symbol" => "Varchar",
		"SymbolLocation" => "Int",
		"DecimalSeparator" => "Varchar",
		"DecimalPlaces"=> "Int",
		"ThousandsSeparator" => "Varchar",
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
		"Code" => "Currency Code",
		"Title" => "Currency Name",
		"ExchangeRate" => "Exchange Rate",
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

					HeaderField::create("Add/Edit Currency"),
					CompositeField::create(
				
						//Show enabled dropdown as editable if not the default currency
						DropdownField::create(
							"Enabled",
							"Enable Currency?",
							array(
								"1" => "Yes",
								"2" => "No",
							)
						)
						->setRightTitle( 
							($this->SystemCreated==1) ? 
							"DISABLED: You can not disable the default currency." : "Select the country this zone applies to." 
						)				
						->setDisabled( ($this->SystemCreated==1) ? true : false ),
						
						TextField::create(
							"Title",
							"Currency Name"
						)
						->setRightTitle("i.e. Great British Pound."),
						
						TextField::create(
							"Code",
							"Currency Code"
						)
						->setRightTitle("i.e. GBP, USD, EUR."),
						
						TextField::create(
							"ExchangeRate",
							"Exchange Rate"
						)
						->setRightTitle("i.e. Your new currency is USD, a conversion rate of 1.53 may apply against the local currency."),
						
						TextField::create(
							"Symbol",
							"Currency Symbol"
						)
						->setRightTitle("i.e. &pound;, $, &euro;."),
						
						DropdownField::create(
							"SymbolLocation",
							"Symbol Location",
							array(
								"1" => "On the left, i.e. $1.00",
								"2" => "On the right, i.e. 1.00$",
								"3" => "Display the currency code instead, i.e. 1 USD."
							)
						)
						->setRightTitle("Where should the currency symbol be placed?"),
						
						TextField::create(
							"DecimalSeperator",
							"Decimal Separator"
						)
						->setRightTitle("What decimal separator does this currency use?"),
						
						TextField::create(
							"ThousandsSeparator",
							"Thousands Separator"
						)
						->setRightTitle("What thousands separator does this currency use?"),
						
						NumericField::create(
							"DecimalPlaces",
							"Decimal Places"
						)
						->setRightTitle("How many decimal places does this currency use?")
					
					)
					
				)
				
			)
			
		);
		
		return $fields;
		
	}
	
	/* Set the required fields */
	public static function getCMSValidator() {
		return RequiredFields::create( 
			array(
				"Title",
				"Country",
				"Code",
				"ExchangeRate",
				"Symbol",
				"SymbolLocation",
				"DecimalSeparator",
				"DecimalPlaces",
				"ThousandsSeparator"
			)
		);
	}
	
	/** 
	 * convertToCurrency
	 * Convert the provided string/number to a number_format() string using configuration of the given currency.
	 *
	 * @param int $number The number we are to convert to the currency defined in $currency.
	 * @param int $currency The database id of the currency to display. If blank, defaults to the system default.
	 *
	 * @return float
	 */
	public static function convertToCurrency($number, $currency=1) {
		
		//Fetch the config for the currency provided
		$conf = DataObject::get_by_id("StoreCurrency", $currency);
		
		//Return our currency.
		return number_format($number, $conf->DecimalPlaces, $conf->DecimalSeperator, $conf->ThousandsSeperator);
		
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
			
			$n = new StoreCurrency();
			
			$n->Title = "Great British Pound";
			$n->Enabled = "1";
			$n->Code = "GBP";
			$n->ExchangeRate = "1.00";
			$n->Symbol = "&pound;";
			$n->SymbolLocation = "1";
			$n->DecimalSeparator = ".";
			$n->DecimalPlaces = "2";
			$n->ThousandsSeparator = ",";
			$n->SystemCreated = "1";
			
			$n->write();
			
			unset($n);
			
			DB::alteration_message('Created default store currency', 'created');
			 		 
		}
		
	}


}