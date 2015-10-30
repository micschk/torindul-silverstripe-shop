<?php
/**
 * Customer New Address Form.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class CustomerNewAddressForm extends Form {
	 
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
			
			DropdownField::create(
				"AddressType",
				"Address Type",
				array(
					"1" => "Residential",
					"2" => "Commercial"
				)
			),
			
			TextField::create("FirstName", "First Name"),
			TextField::create("Surname", "Surname"),
			TextField::create("CompanyName", "Company Name"),
			TextField::create("AddressLine1", "Address Line 1"),
			TextField::create("AddressLine2", "Address Line 2"),
			TextField::create("City", "Town/City"),
			TextField::create("StateCounty", "State/County"),
			TextField::create("Postcode", "Zip/Postcode"),
			CountryDropdownField::create("Country", "Country"),
			TextField::create("PhoneNumber", "Contact Number"),
			HiddenField::create("Type", "Type", $type)	
			
			
		);	
		
		/* Actions */
		$actions = FieldList::create(
			CompositeField::create(
				FormAction::create( 'newaddress', 'Use Address &amp; Continue')
			)
		);
		
		/* Required Fields */
        $required = new RequiredFields(array(
			"FirstName",
			"Surname",
			"AddressLine1",
			"City",
			"StateCounty",
			"Country",
			"Postcode",
			"AddressType",
			"PhoneNumber"
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