<?php
/**
 * Order Payment Form.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class OrderPaymentForm extends Form {
	 
	/*
	 * Return a choice of payment methods and, depending on StoreSettings a comments
	 * box and a checkbox for terms and conditions.
	 *
	 * @param String $controller The controller to handle this form.
	 * @param String $name The method on $controller to handle this form.
	 * @param Int $order The Order Object we are to using to calculate totals.
	 *
	 * @return Form.
	 */
    public function __construct($controller, $name, $order) {
	    
	    /* Store Settings Object */
		$conf = StoreSettings::get_settings();
		
		/* Comments Box, if enabled */
		if( $conf->CheckoutSettings_OrderComments ) {
			$comments = TextareaField::create("CustomerComments", "Order Comments");
			$comments->setRightTitle("These comments will be seen by staff.");
		} else {
			$comments = HiddenField::create("CustomerComments", "");
		}
		
		/* Terms and Conditions, if enabled */
		if( $conf->CheckoutSettings_TermsAndConditions ) {
			$terms = CheckboxField::create(
				"Terms", 
				"I agree to ".$conf->StoreSettings_StoreName."'s ".
				"<a href=".DataObject::get_by_id("SiteTree", $conf->CheckoutSettings_TermsAndConditionsSiteTree)->URLSegment.">".
				"Terms &amp; Conditions</a>."
			);
		} else {
			$terms = HiddenField::create("Terms", "");
		}
	    	    
		/* Fields */
		$fields = FieldList::create(
			$comments,
			OptionsetField::create( "PaymentMethod", "Payment Method", Gateway::create()->getGateways($order) ),
			($terms) ? HeaderField::create("Terms and Conditions", 5) : HiddenField::create("TermsHeaderField", ""),
			$terms
		);	
		
		/* Actions */
		$actions = FieldList::create(
			FormAction::create('payment', 'Place Order &amp; Continue to Payment')
		);
		
		/* Required Fields */
        $required = new RequiredFields(array(
			"PaymentMethod",
			($terms) ? "Terms" : null,
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