<?php
/**
 * Create the add to basket form.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class AddToBasketForm extends Form {
	 
	/*
	 * Display an Add To Basket form or Out of Stock message.
	 *
	 * @param String $controller The controller to handle this form.
	 * @param String $name The method on $controller to handle this form.
	 * @param Int $product_id The ID of the product to add to the basket.
	 *
	 * @return The Form.
	 */
    public function __construct($controller, $name, $product_id) {
	    
	    /* Store Configuration */
	    $conf = StoreSettings::get_settings();
		
		/*
		 * If the product isn't in stock and the store's stock management
		 * is enabled, then create form to display out of stock. 
		 */
		if( ($this->getStockLevel($product_id) <= $conf->Stock_OutOfStockThreshold) && ($conf->Stock_StockManagement==1) ) { 
			
			/* Fields */
			$fields = FieldList::create(
				LiteralField::create("OutofStock", $conf->Stock_OutofStockMessage)			
			);	
			
			/* Actions */
			$actions = FieldList::create();
			
			/* Required Fields */
	        $required = new RequiredFields(array());		
	        
		}
		
		/* Otherwise, return the standard form */
		else {
			
		    /* Are we using a Dropdown or TextBox? */
		    if( $conf->DisplaySettings_CartQuantity==1 ) {
			    $QuantityField = DropdownField::create( 
			    	"Qty", 
			    	"Quantity", 
			    	$this->getQtyArray($product_id) 
			    );
			} else {
				$QuantityField = TextField::create( "Qty", "Quantity" );
			}
	    
			/* Fields */
			$fields = FieldList::create(
				$QuantityField,
				HiddenField::create("ProductID", "", $product_id)			
			);	
	
			/* Actions */
			$actions = FieldList::create(
				FormAction::create('addToBasket', 'Add To Basket')
			);
	
			/* Required Fields */
	        $required = new RequiredFields(array(
	            "Qty",
	        ));
	        
	    }
	    
        /*
	     * Now we create the actual form with our fields and actions defined 
         * within this class. We should only do this though, if the product
         * in question has its EnablePurchases flag set to true. If its false
         * then just return a blank form.
         */
        if(DataObject::get_by_id("Product", $product_id)->EnablePurchases==true) { 
	        return parent::__construct(
	        	$controller,
	        	$name,
	        	$fields,
	        	$actions,
	        	$required
	        ); 
	    } else { 
		    return parent::__construct(
		    	$controller,
		    	null,
		    	FieldList::create(),
		    	FieldList::create(),
		    	RequiredFields::create()
		    ); 
		}
        
    }
    
    /**
	 * If the store's stock management is enabled then fetch the total 
	 * stock of the product and produce an array for the dropdown menu.
	 * No more than 20, values however. 
	 *
	 * If the store has stock management disabled then only show 20 
	 * options as standard.
	 *
	 * @param Int $product_id The ID of the product to add to the basket.
	 * @return Array|Boolean
	 */
    public function getQtyArray($product_id) {
	    
	    /* Store Settings */
	    $conf = StoreSettings::get_settings();
	    
	    /*
		 * If the store's stock management is enabled get the stock level, otherwise use 20. */
	    $qty = ($conf->Stock_StockManagement==1) ? $this->getStockLevel($product_id) : 20;
	    
	    /* If stock level is 0, return false */
	    if( $qty == 0 ) { return false; }
	    
	    /* Stock Level Array */
	    $stock = array();
	    
	    /* Loop and push items on to array */
	    for($i=1; $i<=$qty; $i++) { 
		    
		    //Push
		    $stock[$i] = $i;
		    
		    //If $i=20, break loop 
		    if($i==20) { break; }
		    
	    }
	    
	    return $stock;
	    
    }
    
    /** 
	 * Get current stock level
	 *
	 * @param Int $product_id The ID of the product to add to the basket.
	 * @return Int
	 */
	public function getStockLevel($product_id) {
	    $qty = new SQLQuery("StockLevel");
	    $qty->setFrom("Product")->addWhere("`ID`='".$product_id."'");
	    return $qty->execute()->value();
	}
    
    
}