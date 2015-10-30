<?php
/**
 * COURIER NAME: FLAT RATE PER ORDER
 * 
 * @author George Botley - Torindul Business Solutions
 */
class Courier_FlatRatePerOrder extends Courier {
	
	/**
	 * There is no criteria for this courier so return true.
	 */
	public static function check_criteria_met($order_no) {
		return true;
	}
	
	/**
	 * Return the flat rate as defined in this couriers settings.
	 */
	public static function calculate_shipping_total($order_no) {
		return DataObject::get_one( get_class() )->FlatRate;
	}

	/**
	 * Add fields to the database for this couriers settings
	 */
	private static $db = array(
		"FlatRate" => "Decimal(10,2)"
	);	
	
    /**
	 * Add fields to the CMS for this courier.
     */
	public function getCMSFields() {
		
		//Fetch the fields from the Courier DataObject
		$fields = parent::getCMSFields();
		
		//Add new fields
		$fields->addFieldsToTab("Root.Main", array(
			
			HeaderField::create("Order Rate"),
			CompositeField::create(
				
				NumericField::create("FlatRate", "Flat Rate (" . Product::getDefaultCurrency() . ")")
				->setRightTitle("Enter a flat rate of shipping. This will be charged once per order.")
				
			)
			
		));
		
		return $fields;
		
	}
	
	/**
	 * Set the required form fields for this courier, taking those
	 * defined in Courier in to account.
	 */
	public static function getCMSValidator() {
		
		//Get required fields from Courier DataObject.
		$parent_required = (is_array(parent::getCMSValidator())) ? parent::getCMSValidator() : array();
		
		//Specify our own required fields.
		$required = array("FlatRate");
		
		//Return the required fields.
		return RequiredFields::create( array_merge( $parent_required, $required ) );
		
	}
	
	/**
	 * requireDefaultRecords
	 * Populate the Courier DataObject with information about our courier, so that it installs correctly.
	 *
	 * @return void
	 */
	public function requireDefaultRecords() {
		 
		/* Inherit Default Record Creation */
		parent::requireDefaultRecords();
		
		$courier_name = get_class($this);
		 
		/* If no records exist, create defaults */
		if( !DataObject::get_one( $courier_name )  ) {
			
			$n = new $courier_name();
			
			//Disable the courier by default.
			$n->Enabled = 0;
			
			//System name for this gateway
			$n->SystemName = "Flat Rate Per Order";
			
			//Friendly name for this courier.
			$n->Title = "Flat Rate Per Order";
			
			//Default minimum spend
			$n->FlatRate = 0;
			
			//Write our configuration changes to the courier database tables.
			$n->write();
			
			unset($n);
			
			DB::alteration_message('Successfully installed the courier "' . $courier_name . '"', 'created');
			 		 
		}
		
	}

}