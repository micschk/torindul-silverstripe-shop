<?php
/**
 * Model to store both the system default and custom order statuses.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Order_Statuses extends DataObject {

	private static $singular_name = "Order Status";
	private static $plural_name = "Order Statuses";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"SystemTitle" => "Varchar",
		"Content" => "Text",
		"SystemCreated" => "Int",
	);	
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
	    'Title' => 'Display Name',
	    'Content' => 'Description'
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

					HeaderField::create("Add/Edit Order Status"),
					CompositeField::create(
						
						TextField::create(
							"Title",
							"Friendly Name"
						)->setRightTitle("The name of your custom order status. i.e. Pending, Awaiting Stock."),
							
						TextareaField::create(
							"Content",
							"Friendly Description"
						)->setRightTitle("This will be shown to your customers. What do you wish to tell them about this status?")
						
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
				"Content"
			)
		);
	}
	
	/**
	 * Create a SystemTitle on initial write.
	 */
	public function onBeforeWrite() {
		
		parent::onBeforeWrite();
		
		/* If this record does not yet exist, set the SystemTitle */
		if(!$this->exists()) { 
			$this->SystemTitle = $this->Title;
		}	
		
	}
	
	/**
	 * TODO - Dependency checks before deleting
	 */
	public function onBeforeDelete() {
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
					"Title" => "Pending / Awaiting Payment",
					"SystemTitle" => "Pending / Awaiting Payment",
					"Content" => "An order has been received but payment or notification of successful payment is still pending."
				),
				
				array(
					"Title" => "Processing",
					"SystemTitle" => "Processing",
					"Content" => "Your payment has been received and your order is being processed."
				),
				
				array(
					"Title" => "Awaiting Stock",
					"SystemTitle" => "Awaiting Stock",
					"Content" => "We are currently awaiting stock before we can progress your order."
				),
				
				array(
					"Title" => "Completed",
					"SystemTitle" => "Completed",
					"Content" => "Your order is now complete."
				),
				
				array(
					"Title" => "Shipped",
					"SystemTitle" => "Shipped",
					"Content" => "Your order has been handed to the courier and is on its way."
				),
				
				array(
					"Title" => "Refunded",
					"SystemTitle" => "Refunded",
					"Content" => "Your order has been refunded."
				),
				
				array(
					"Title" => "Part Refunded",
					"SystemTitle" => "Part Refunded",
					"Content" => "Your order has been part refunded."
				),
				
				array(
					"Title" => "Cancelled",
					"SystemTitle" => "Cancelled",
					"Content" => "Your order has been cancelled."
				)
				
			);
			 
			foreach($defaults as $default) {
				 
				$n = new Order_Statuses();
				$n->Title = $default["Title"];
				$n->SystemTitle = $default["SystemTitle"];
				$n->Content = $default["Content"];
				$n->SystemCreated = "1";
				$n->write();
				unset($n);
			 
			}
			
			DB::alteration_message('Created default order statuses', 'created');
			 		 
		}
		 
	}

}