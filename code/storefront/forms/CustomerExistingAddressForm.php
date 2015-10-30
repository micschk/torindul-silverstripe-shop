<?php
/**
 * Customer Existing Address Form.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class CustomerExistingAddressForm extends Form {
	 
	/*
	 * Return a form allowing a customer to create a new Customer_AddressBook item.
	 *
	 * @param String $controller The controller to handle this form.
	 * @param String $name The method on $controller to handle this form.
	 * @param String $type The address type. Either billing/shipping. Defines the forms action.
	 *
	 * @return Form.
	 */
    public function __construct($controller, $name, $type) {
	    	    
		/* Fields */
		$fields = FieldList::create(
			
			OptionsetField::create(
				"AddressID",
				"Choose an address",
				DataObject::get("Customer_AddressBook", "(`CustomerID`=".Member::currentUserID().")")->map()
			)->setEmptyString("(Select one)"),
						
			HiddenField::create("Type", "Type", $type)
			
		);	
		
		/* Actions */
		$actions = FieldList::create(
			CompositeField::create(
				FormAction::create('existingaddress', 'Use Address &amp; Continue')
			)
		);
		
		/* Required Fields */
        $required = new RequiredFields(array(
			"AddressID"
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