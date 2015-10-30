<?php
/**
 * COURIER NAME: FLAT RATE PER ITEM
 * 
 * @author George Botley - Torindul Business Solutions
 */
class Courier_FlatRatePerItem extends Courier {
	
	/**
	 * There is no criteria for this courier so return true.
	 */
	public static function check_criteria_met($order_no) {
		return true;
	}
	
	/**
	 * Count the total number of items in this order and multiple
	 * them by the item flat rate as defined in this couriers
	 * settings.
	 */
	public static function calculate_shipping_total($order_no) {
		
		//This method will be called statically and is not the Controller ($this) so store the database fields in $conf.
		$conf = DataObject::get_one( get_class() );
		
		//Count the total number of items in this order.
		$product = new SQLQuery();
		$product->setFrom("Order_Items")->addWhere("(`OrderID`=".$order_no.")");
		$result = $product->execute();
		$total_items = 0;
		foreach( $result as $row ) { $total_items = ($total_items + $row["Quantity"]); }
		
		//Return the shipping cost.
		return ($total_items * $conf->FlatRate);
		
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
			
			HeaderField::create("Item Rate"),
			CompositeField::create(
				
				NumericField::create("FlatRate", "Item Rate (" . Product::getDefaultCurrency() . ")")
				->setRightTitle("Enter a flat rate of shipping to charge for every item in this order.")
				
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
			$n->SystemName = "Flat Rate Per Item";
			
			//Friendly name for this courier.
			$n->Title = "Flat Rate Per Item";
			
			//Default minimum spend
			$n->FlatRate = 0;
			
			//Write our configuration changes to the courier database tables.
			$n->write();
			
			unset($n);
			
			DB::alteration_message('Successfully installed the courier "' . $courier_name . '"', 'created');
			 		 
		}
		
	}
	


}