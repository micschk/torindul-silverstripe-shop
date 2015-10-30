<?php
class Store_BasketController extends ContentController {	
	
	/**
	 * Allowed Actions 
	 *
	 * index - The basket page.
	 */
	private static $allowed_actions = array(
		"index",
		"BasketForm",
		"continueshopping",
		"placeorder",
	);
	
	/**
	 * get_link
	 * Return the link for this controller.
	 *
	 * @return URL
	 */
	public static function get_link() {
		return self::link();
	}
	
	/** 
	 * get_temp_basket_id
	 * Return the TempBasketID for the current user.
	 *
	 * @return String
	 */
	public static function get_temp_basket_id() {
		return Cookie::get('TempBasketID');
	}
	
	
	/** 
	 * set_temp_basket_id
	 * Create a TempBasketID for the current user
	 * and then return its value. 
	 * 
	 * This uses the following segments, separated by dashes:
	 * 1 - ISO8601 Date At Creation Minus Dashes
	 * 2 - 24 Hour Time At Creation Minus Colon
	 * 3 - Users IP Address Minus Periods
	 * 
	 * For example:
	 * 20151006-1245-127001
	 *
	 * This TempBasketID is to be stored in the database against the order and its items and 
	 * can be retreived by the user provided the cookie value matches it. The cookie will 
	 * last for 1 minutes before being deleted.
	 *
	 * @return String
	 */
	public static function set_temp_basket_id() {
		Cookie::set( 
			'TempBasketID',
			date("Ymd") . "-" . date("His") . "-" . str_replace(".", "", $_SERVER["REMOTE_ADDR"]),
			1
		);
		return self::get_temp_basket_id();
	}
	
	/** 
	 * destroy_temp_basket_id
	 * Destroy the TempBasketID for the current user.
	 *
	 * @return Boolean
	 */
	public static function destroy_temp_basket_id() {
		return (Cookie::force_expiry('TempBasketID')) ? true : false;
	}
	 
	 
	/**
	 * is_basket_full
	 * Check if the basket has items.
	 *
	 * @return Boolean
	 */
	public static function is_basket_full() {
		
	    $TempBasketID = Store_BasketController::get_temp_basket_id();
		
	    /**
		 * If a TempBasketID doesn't exist then return false.
		 */
	    if( !$TempBasketID ) {
			return false;		    
	    }
	    
	    /**
		 * Else If a TempBasketID does exist, are there any items
		 * in the basket for that customer? If not, then return
		 * false.
		 */
		elseif( DataObject::get("Order_Items", "(`TempBasketID`='".$TempBasketID."')")->count() == 0 ) {
			return false;
		}
		
		/**
		 * Basket has items, return true.
		 */
		else { return true; }
		
	}
	
	/**
	 * BasketForm
	 * Display the shopping basket.
	 * 
	 * @return Form
	 */
	public function BasketForm() {
		
		/**
		 * If basket has items, show them.
		 */
		if( $this->is_basket_full() ) {
			
			/* Initiate our form. */
			$form = BasketForm::create($this, 'BasketForm');
			
			/* Force POST and Strict Method Check */
			$form->setFormMethod('POST');
			$form->setStrictFormMethodCheck(true);
			
			/* Return the form */
			return $form;
		
		}
		
		/* Otherwise, return false. */
		else { 
			return false;
		}
		
	}
	
	/**
	 * Get the store URL for use in templates
	 */
	public function getStoreURL() {
		return Store_Controller::get_link();
	}
	
	/**
	 * Return the URL to the this controller.
	 */
	public function link($action=null) {
		return Store_Controller::get_link() . "/basket";
	}
	
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
	 * View the basket.
	 */
	public function index() {
		return $this->customise(array(
			"Title" => "Shopping Basket"
		))->renderWith( array("Store_Basket", "Page"));
	}
	
	/**
	 * ACTION /continueshopping
	 * Return the customer to the store front.
	 */
	public function continueshopping($data) {
		return $this->redirect( Store_Controller::get_link() );
	}
	
	
	/**
	 * ACTION /placeorder
	 * Redirect the user to /order/place action on Store_Controller to pickup order process.
	 */
	public function placeorder($data) {
		return $this->redirect( Store_OrderController::get_link() . "/place" );
	}
		
}
?>