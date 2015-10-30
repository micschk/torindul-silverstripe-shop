<?php
class Store_ProductController extends ContentController {	
	
	/**
	 * Allowed Actions 
	 *
	 * index - The StoreFront
	 * product - The individual product page
	 */
	private static $allowed_actions = array(
		"index",
		"view",
		"addtobasket"
	);
	
	/**
	 * init
	 * Ensure that this controller maps to the Store page (so navigation is enabled)
	 */
	public function init() {
		parent::init();
		Director::set_current_page( DataObject::get_one("SiteTree", "ClassName='Store'") );	
	}
	
	/**
	 * ACTION /
	 * Redirect back to the storefront as /product/ has no content.
	 */
	public function index() {
		return $this->redirect( Store_Controller::get_link(), 404 );
	}
	
	/**
	 * ACTION /view
	 * Fetch the requested product and render its product page.
	 * If it doesn't exist, return HTTP 404.
	 *
	 * @return HTMLText
	 */
	public function view() {
		
		/**
		 * StoreSettings 
		 */
		$conf = StoreSettings::get_settings();
	 
		/**
		 * Get the URLSegment of the Product from the request 
		 */
		$URLSegment = $this->request->param('ID');
		
		/**
		 * If URLSegment doesn't exist redirect with Error 404 redirect to the StoreFront.
		 */
		if(!$URLSegment) {
			return $this->httpError(404);
		}
		
		/* The Product selected */
		$Product = DataObject::get_one("Product", "`URLSegment`='$URLSegment'");
		
		/**
		 * If the Product doesn't exist, fail with httpError(404).
		 */
		if(!$Product) {
			return $this->httpError(404);	
		} else {
			
			/**
			 * If product is out of stock, and admin has defined it should be completely
			 * hidden, then return HTTP error 302 and temporarily redirect to the storefront.
			 * Error 302 should prevent Search Engines modifying their entry for this product
			 * whilst its hidden.
			 */
			if( ($Product->StockLevel <= $conf->Stock_OutOfStockThreshold) && ($conf->Stock_ProductOutOfStock==1) ) {
				return $this->redirect( Director::BaseURL() . DataObject::get_one("SiteTree", "ClassName='Store'")->URLSegment, 302);
			} 
			
			/**
			 * If Product visibility is set to false return with httpError(404) otherwise
			 * return the Product page with renderWith()
			 */
			return ( !$Product->Visible ) ? $this->httpError(404) : $this->customise(
				array(
					"Title" => $Product->Title,
					"Product" => $Product
				)
			)->renderWith( array("Store_Product", "Page") );
			
		}
	
	}
	
	/**
	 * ACTION /addtobasket
	 * Add the requested item to the basket.
	 */
	public function addtobasket($data) {
		
		/* Retreive the TempBasketID (Cookie) for the current users basket. If it doesn't exist, create one */
		if(Store_BasketController::get_temp_basket_id()) {
			$TempBasketID = Store_BasketController::get_temp_basket_id();
		} else {
			$TempBasketID = Store_BasketController::set_temp_basket_id();
		}
		
		/* Try to fetch an Order record using the TempBasketID */
		$Order = DataObject::get_one("Order", "(`TempBasketID`='".$TempBasketID."')");
		
		/**
		 * If an Order record doesn't exist, create the Order record first.
		 */
		if(!$Order) {
			$n = new Order();
			$n->TempBasketID = $TempBasketID;
			$n->write();
			$Order = DataObject::get_one("Order", "(`TempBasketID`='".$TempBasketID."')");
		}
		
		/**
		 * Do any Order_Items exist for this Product in the current Order? If yes, increment Quantity.
		 * Otherwise, add a new item.
		 */
		$count = new SQLQuery("COUNT(*)");
		$count->setFrom("Order_Items")->addWhere("(`OriginalProductID`='".$data["ProductID"]."' AND `TempBasketID`='".$TempBasketID."')");
		if( $count->execute()->value()>0 ) {
			
			DB::Query("UPDATE Order_Items SET Quantity=Quantity + ".$data["Qty"]." WHERE (`OriginalProductID`='".$data["ProductID"]."' AND `TempBasketID`='".$TempBasketID."')");
			
		} else {
			
			/**
			 * Proceed to add the selected Product to the order as an Order_Items with the same TempBasketID.
			 */
			$order_item = new Order_Items();
			$order_item->OrderID = $Order->ID;
			$order_item->OriginalProductID = $data["ProductID"];
			$order_item->Quantity = $data["Qty"];
			$order_item->TempBasketID = $TempBasketID;
			
			/**
			 * As this order is still in its 'Shopping Basket' stages we will have no customer information
			 * to calculate tax with at this time. Set tax rate and class to zero for now.
			 */
			$order_item->TaxClassName = "To Be Calculated";
			$order_item->TaxClassRate = "0.00";
			
			/* Write to the database */		
			$order_item->write();
			
		}
		
		/* Take the user to their Basket */
		return $this->redirect( Store_BasketController::get_link() );
				
	}
		
}
?>