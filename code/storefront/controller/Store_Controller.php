<?php
class Store extends Page {}	
	
class Store_Controller extends Page_Controller {	
	
	/**
	 * Allowed Actions 
	 *
	 * index - The StoreFront
	 * product - The individual product page
	 */
	private static $allowed_actions = array(
		"logout",
		"product",
		"basket",
		"order",
		"test"
	);
	
	/**
	 * get_link
	 * Return the link for this controller.
	 *
	 * @return URL
	 */
	public static function get_link() {
		return Store_Controller::create()->link();
	}
	
	/** 
	 * Override Link 
	 */
	public function link($action = null) {
		return Director::BaseURL() . DataObject::get_one("SiteTree", "ClassName='Store'")->URLSegment;
	}
	
	/**
	 * dologout
	 * Pass control to the logout action 
	 */
	public static function dologout() {
		return $this->redirect( $this->link() . "/logout" );
	}
	
	public function test() {
		Order_Emails::create()->customerOrderStatusUpdate("74", "Cancelled");
	}
	
	/**
	 * ACTION /product
	 * Passes the control to the Controller 'Store_ProductController'
	 */
	public function product() {
		return Store_ProductController::create()->handleRequest($this->request, $this->model);
	}
	
	/**
	 * ACTION /basket
	 * Passes the control to the Controller 'Store_BasketController'
	 */
	public function basket() {
		return Store_BasketController::create()->handleRequest($this->request, $this->model);
	}
	
	/**
	 * ACTION /order
	 * Passes the control to the Controller 'Store_OrderController'
	 */
	public function order() {
		return Store_OrderController::create()->handleRequest($this->request, $this->model);
	}
	
	/**
	 * ACTION /logout
	 * Logout the user and redirect to the basket.
	 */
	public static function logout() {
		$security = new Security();
		$security->logout(false);
		return $this->redirect( $this->link() );
	}
	
	/**
	 * getFeaturedProducts
	 * Retrieve featured products from Product DataObject
	 */
	public function getFeaturedProducts() {
		return DataObject::get(
			"Product",
			Product::get_out_of_stock_filter() . " AND (1=Featured)",
			StoreSettings::get_settings()->DisplaySettings_ProductSort,
			null,
			StoreSettings::get_settings()->StoreDisplaySettings_FeaturedProducts
		);
	}
	
	/**
	 * getNewProducts
	 * Retrieve latest products from Product DataObject
	 */
	public function getNewProducts() {
		return DataObject::get(
			"Product",
			Product::get_out_of_stock_filter(),
			"Created DESC",
			"",
			StoreSettings::get_settings()->DisplaySettings_NewProducts
		);
	}
	
	/**
	 * @param SS_HTTPRequest $request
	 * @param $model
	 *
	 * @return HTMLText|SS_HTTPResponse
	 */
	protected function handleAction($request, $model) {
		/**
		 * We return nested controllers, so the parsed URL params need
		 * to be discarded for the subsequent controllers to work
		 */
		$request->shiftAllParams();
		return parent::handleAction($request, $model);
	}
	
	/**
	 * LogoutLink
	 * Return a logout link 
	 *
	 * @param String $location The location to direct to. i.e. storefront, basket, placeorder
	 * @return URL
	 */
	public function LogoutLink($location=null) {
		$security = new Security();
		
		/* Set $BackURL based on $location */
		switch($location) {
			
			/* Basket */
			case "basket":
				$Store_BasketController = new Store_BasketController();
				$BackURL = $Store_BasketController->link();
				break;
				
			/* Order Step 1 */
			case "placeorder":
				$Store_OrderController = new Store_OrderController();
				$BackURL = $Store_OrderController->link() . "/place/one";
				break;
			
			/* Storefront */
			default:
				$BackURL = self::get_link();			
				break;
			
		}
		
		return $security->Link('logout') . "?BackURL=" . $BackURL;
	}
	
}
?>