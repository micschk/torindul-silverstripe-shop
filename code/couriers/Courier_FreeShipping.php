<?php
/**
 * COURIER NAME: FREE SHIPPING
 *
 * @author George Botley - Torindul Business Solutions
 */
class Courier_FreeShipping extends Courier {
	
	/**
	 * If the total order spend exceeds the defined minimum spend value, make courier available. 
	 */
	public static function check_criteria_met($order_no) {
		
		//This method will be called statically and is not the Controller ($this) so store the database fields in $conf.
		$conf = DataObject::get_one( get_class() );
		
		//Fetch the total price for all products in the given order
		$product = new SQLQuery();
		$product->setFrom("Order_Items")->addWhere("(`OrderID`=".$order_no.")");
		$result = $product->execute();
		$total_spend = 0;
		foreach( $result as $row ) { $total_spend = ( $total_spend + ( $row["Price"] * $row["Quantity"] ) ); }
		
		//If the total spend exceeds the defined minimum spend value, make courier available.
		return ( $total_spend>=$conf->MinSpend ) ? true : false;
	
	}
	
	/**
	 * As this courier is by name "free shipping" then return 0 as the cost.
	 */
	public static function calculate_shipping_total($order_no) {
		return "0.00";
	}

	/**
	 * Add fields to the database for this couriers settings
	 */
	private static $db = array(
		"MinSpend" => "Decimal(10,2)"
	);	
	
    /**
	 * Add fields to the CMS for this courier.
     */
	public function getCMSFields() {
		
		//Fetch the fields from the Courier DataObject
		$fields = parent::getCMSFields();
		
		//Add new fields
		$fields->addFieldsToTab("Root.Main", array(
			
			HeaderField::create("Minimum Spend"),
			CompositeField::create(
				
				NumericField::create("MinSpend", "Qualifying Spend (" . Product::getDefaultCurrency() . ")")
				->setRightTitle("Enter the amount a customer must spend before qualifying for Free Shipping.")
				
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
		$required = array("MinSpend");
		
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
			$n->SystemName = "Free Shipping";
			
			//Friendly name for this courier.
			$n->Title = "Free Shipping";
			
			//Default minimum spend
			$n->MinSpend = 0;
			
			//Write our configuration changes to the courier database tables.
			$n->write();
			
			unset($n);
			
			DB::alteration_message('Successfully installed the courier "' . $courier_name . '"', 'created');
			 		 
		}
		
	}
	


}