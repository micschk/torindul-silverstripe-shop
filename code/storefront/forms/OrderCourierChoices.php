<?php
/**
 * Order Courier Choices.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class OrderCourierChoices extends Form {
	 
	/*
	 * Return a radio button list of couriers available for an order.
	 *
	 * @param String $controller The controller to handle this form.
	 * @param String $name The method on $controller to handle this form.
	 * @param Int $order_id The ID of the current order.
	 *
	 * @return Form.
	 */
    public function __construct($controller, $name, $order_id) {
	    
	    $Order = new Order();
	    	    
		/* Fields */
		$fields = FieldList::create(
			
			OptionsetField::create( "Courier", "Courier", $Order->getCouriers( $order_id, true ) )		
			
		);	
		
		/* Actions */
		$actions = FieldList::create(
			FormAction::create('selectcourier', 'Select Courier &amp; Proceed To Order Summary')
		);
		
		/* Required Fields */
        $required = new RequiredFields(array(
			"Courier"
        ));		
	    
        /*
	     * Now we create the actual form with our fields and actions defined 
         * within this class.
         */
	    return parent::__construct(
        	$controller,
        	$name,
        	$fields,
        	$actions,
        	$required
	    ); 
        
    }   
    
}