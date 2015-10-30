<?php
/**
 * Order Summary Totals.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class OrderSummaryTotals extends Form {
	 
	/*
	 * Return a ReadOnlyField's displaying price totals, i.e. Tax etc.
	 *
	 * @param String $controller The controller to handle this form.
	 * @param String $name The method on $controller to handle this form.
	 * @param Int $order The Order Object we are to using to calculate totals.
	 *
	 * @return Form.
	 */
    public function __construct($controller, $name, $order) {
	    	    
		/* Fields */
		$fields = FieldList::create(
			
			ReadonlyField::create(
				"Shipping",
				"Shipping Total (" . Product::getDefaultCurrency() . ")",
				$shipping_total = Order::create()->calculateShippingTotal($order->ID, $order->Courier)
			),
			
			FieldGroup::create("Tax (" . Product::getDefaultCurrency() . ")",
			
				ReadonlyField::create(
					"ProductTaxInclusive",
					"Product Tax (Inclusive)",
					Order::create()->calculateProductTax(1, $order)
				)
				->setRightTitle("Basket total is inclusive of this tax."),
				
				ReadonlyField::create(
					"ProductTaxExclusive",
					"Product Tax (Exclusive)",
					$exc_product_tax = Order::create()->calculateProductTax(2, $order)
				)
				->setRightTitle("Basket total is exclusive of this tax."),
				
				ReadonlyField::create(
					"ShippingTax",
					"Shipping Tax",
					$shipping_tax = Order::create()->calculateShippingTax( 
						Order::create()->calculateShippingTotal($order->ID, $order->Courier),
						$order 
					)
				)
				->setRightTitle(
					(StoreSettings::get_settings()->TaxSettings_ShippingInclusiveExclusive==1)
					? "Shipping price is inclusive of this tax."
					: "Shipping price is exclusive of this tax."
				)
			
			),
			
			ReadonlyField::create(
				"Total",
				"Final Total",
				Order::create()->calculateOrderTotal($order)
			)
							
		);	
		
		/* Actions */
		$actions = FieldList::create(
		);
		
		/* Required Fields */
        $required = new RequiredFields(array(
		
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