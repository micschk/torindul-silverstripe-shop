<?php
/**
 * Create a PayPal Payments Standard Form.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Gateway_PayPal_Form extends Form {
	 
	/*
	 * Return a Form for use in initiating the PayPal Payments Standard process.
	 *
	 * @param String $controller The controller to handle this form.
	 * @param String $name The method on $controller to handle this form.
	 * @param Object $Order The order object we are collecting payment for.
	 *
	 * @return Form.
	 */
    public function __construct($controller, $name, $order_id) {
	    
		$Order = Order::get_by_id("Order", $order_id);
	    	    
		/* Fields */
		$fields = FieldList::create(
			
			/* PayPal Account Fields */
			HiddenField::create("business", "business", DataObject::get_one("Gateway_PayPal")->EmailAddress),
			HiddenField::create("cmd", "cmd", "_xclick"),
			HiddenField::create(
				"notify_url",
				"notify_url",
				Director::absoluteURL( Store_OrderController::create()->link() . "/payment/response?gateway=Gateway_PayPal")
			),
			HiddenField::create("custom", "custom", $order_id),
			
			/* Transaction Fields */
			HiddenField::create("item_name", "item_name", "Order No. ".$order_id." @ ".StoreSettings::get_settings()->StoreSettings_StoreName),
			HiddenField::create("amount", "amount", Order::create()->calculateOrderTotal($Order)),
			HiddenField::create("currency_code", "currency_code", DataObject::get_one("StoreCurrency", "(`SystemCreated`='1')")->Code),
			HiddenField::create("no_note", "no_note", "1"),
			HiddenField::create("no_shipping", "no_shipping", "1"),
			
			/* Return Fields */
			HiddenField::create(
				"return",
				"return",
				Director::absoluteURL( Store_OrderController::create()->link() ) . "/payment/success?gateway=Gateway_PayPal"
			),
			HiddenField::create("rm", "rm", "2"),
			HiddenField::create("cbt", "cbt", "Return to ".StoreSettings::get_settings()->StoreSettings_StoreName),
			HiddenField::create(
				"cancel_return",
				"cancel_return",
				Director::absoluteURL( Store_OrderController::create()->link() ) . "/payment/cancelled?gateway=Gateway_PayPal"
			)
			
		);	
		
		/* Actions */
		$actions = FieldList::create(
			FormAction::create('', 'If you are not transferred to PayPal in 5 seconds, click here.')
		);
		
		/* Required Fields */
        $required = new RequiredFields(array(
			"business",
			"cmd",
			"notify_url",
			"item_name",
			"amount",
			"currency_code",
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