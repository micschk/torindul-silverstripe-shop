<?php
/**
 * Customer Register Form.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class CustomerRegisterForm extends Form {
	 
	/*
	 * Return a registration form for new customers.
	 *
	 * @param String $controller The controller to handle this form.
	 * @param String $name The method on $controller to handle this form.
	 *
	 * @return Form.
	 */
    public function __construct($controller, $name) {
	    	    
		/* Fields */
		$fields = FieldList::create(
			
			TextField::create("FirstName", "First Name"),
			TextField::create("Surname", "Surname"),
			TextField::create("CompanyName", "Company Name (optional)"),
			EmailField::create("Email", "Email Address"),
			ConfirmedPasswordField::create("Password", "Password"),
			TextField::create("LandlineNumber", "Landline Number"),
			TextField::create("MobileNumber", "Mobile Number")			
			
		);	
		
		/* Actions */
		$actions = FieldList::create(
			CompositeField::create(
				FormAction::create('createaccount', 'Create Account')
			)
		);
		
		/* Required Fields */
        $required = new RequiredFields(array(
			"FirstName",
			"Surname",
			"Email",
			"Password",
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