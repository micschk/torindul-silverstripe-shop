<?php
/**
 * Shopping Basket Form.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class BasketForm extends Form {
	 
	/*
	 * Shows basket items in a GridField and offers actions to continue
	 * the shopping card process.
	 *
	 * @param String $controller The controller to handle this form.
	 * @param String $name The method on $controller to handle this form.
	 * @param Boolean $show_actions Defaults to true. If false, actions are removed.
	 *
	 * @return Form.
	 */
    public function __construct($controller, $name, $show_actions=true) {
	    
	    $TempBasketID = Store_BasketController::get_temp_basket_id();
	    $order_id = DB::Query("SELECT id FROM `order` WHERE (`TempBasketID`='".$TempBasketID."')")->value();
	    
	    /* Basket GridField */
	    $config = new GridFieldConfig();
	    $dataColumns = new GridFieldDataColumns();
		$dataColumns->setDisplayFields(array(
			'getPhoto' => "Photo",
		    'Title' => 'Product',
		    'Price' => 'Item Price',
		    'Quantity' => 'Quantity',
		    'productPrice' => 'Total Price',
			'getfriendlyTaxCalculation' => 'Tax Inc/Exc',
			'TaxClassName' => 'Tax'
		));
		$config->addComponent($dataColumns);
		$config->addComponent(new GridFieldTitleHeader());
	    $basket = GridField::create(
	    	"BasketItems",
	    	"",
	    	DataObject::get("Order_Items", "(OrderID='".$order_id."')"),
	    	$config
	    );
	    
	    /* Basket Subtotal */
	    $subtotal = new Order();
	    $subtotal = $subtotal->calculateSubTotal( $order_id );
	    $subtotal = ReadonlyField::create(
			"SubTotal",
			"Basket Total (" . Product::getDefaultCurrency() . ")",
			$subtotal
		);
	    	    
		/* Fields */
		$fields = FieldList::create(
			
			$basket,
			$subtotal,
			ReadonlyField::create("Tax", "Tax", "Calculated on the Order Summary page.")
			
		);	
		
		/* Actions */
		$actions = FieldList::create(
			CompositeField::create(
				FormAction::create('continueshopping', 'Continue Shopping'),
				FormAction::create('placeorder', 'Place Order')
			)
		);
		
		/* Required Fields */
        $required = new RequiredFields(array());		
	    
        /*
	     * Now we create the actual form with our fields and actions defined 
         * within this class.
         */
	    return parent::__construct(
        	$controller,
        	$name,
        	$fields,
        	($show_actions) ? $actions : FieldList::create(),
        	$required
	    ); 
        
    }    
    
}