<?php
/**
 * Model to store the store settings.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class StoreSettings extends DataObject {

	/* Database Fields */
	private static $db = array(
		"StoreSettings_StoreAvailable" => "Boolean",
		"StoreSettings_StoreAvailableMessage" => "Text",
		"StoreSettings_StoreName" => "Varchar",
		"StoreSettings_StoreAddress" => "Text",
		"StoreSettings_StoreCountry" => "Varchar",
		"StoreSettings_ProductWeight" => "Varchar",
		"StoreSettings_ProductDimensions" => "Varchar",
		'StoreSettings_SSLToggle' => 'Boolean',			
		
		"DisplaySettings_FeaturedProducts" => "Int",
		"DisplaySettings_NewProducts" => "Int",
		"DisplaySettings_CartQuantity" => "Int",
		"DisplaySettings_ShowPrice" => "Boolean",
		'DisplaySettings_ShowSKU' => 'Boolean',	
		"DisplaySettings_ShowWeight" => "Boolean",
		"DisplaySettings_ShowDimensions" => "Boolean",
		'DisplaySettings_ProductSort' => 'Varchar',	
		"DisplaySettings_ProductPagePhotoWidth" => "Int",
		'DisplaySettings_ProductPagePhotoHeight' => 'Int',
		"DisplaySettings_ProductThumbnailPhotoWidth" => "Int",
		'DisplaySettings_ProductThumbnailPhotoHeight' => 'Int',
		"DisplaySettings_ProductEnlargedPhotoWidth" => "Int",
		'DisplaySettings_ProductEnlargedPhotoHeight' => 'Int',
		
		"CheckoutSettings_InitialStatus" => "Int",
		"CheckoutSettings_GuestCheckout" => "Boolean",
		"CheckoutSettings_GuestCheckoutAccount" => "Boolean",
		"CheckoutSettings_OrderComments" => "Boolean",
		"CheckoutSettings_TermsAndConditions" => "Boolean",
		"CheckoutSettings_TermsAndConditionsSiteTree" => "Varchar",

		"EmailNotification_SendEmailsFrom" => "Varchar",
		"EmailNotification_AdminNewOrder" => "Varchar",		
		"EmailNotification_AccountCreated" => "Boolean",
		"EmailNotification_OrderPlaced" => "Boolean",
		"EmailNotification_OrderStatuses" => "Varchar",
		
		"Stock_StockManagement" => "Boolean",
		"Stock_PendingOrdersFreezeStock" => "Int",
		"Stock_LowStockThreshold" => "Int",
		"Stock_OutOfStockThreshold" => "Int",
		"Stock_OutofStockMessage" => "Text",
		"Stock_ProductOutOfStock" => "Int",
		"Stock_OptionOutOfStock" => "Int",
		"Stock_StockLevelDisplay" => "Int",
		
		"TaxSettings_InclusiveExclusive" => "Int",
		"TaxSettings_ShippingInclusiveExclusive" => "Int",
		"TaxSettings_CalculateUsing" => "Int",	
		
		"ProductReviewSettings_EnableReviews" => "Int",
		"ProductReviewSettings_ApprovedPurchaserOnly" => "Int",
		"ProductReviewSettings_AdminApproval" => "Int"		
	);	
	
	/* Set the required fields */
	static public function getCMSValidator() {
		return RequiredFields::create( 
			array()
		);
	}
	
	/**
	 * Get the current sites StoreSettings. If no settings and creates a new one
	 * through {@link create_settings()} if none is found.
	 *
	 * @return DataObject
	 */
	public static function get_settings() {
		return DataObject::get_one('StoreSettings');
	}
	 
	/**
	 * Add default records to database. This function is called whenever the
	 * database is built, after the database tables have all been created.
	 * 
	 * @uses DataExtension->requireDefaultRecords()
	 */
	public function requireDefaultRecords() {
		 
		/* Inherit Default Record Creation */
		parent::requireDefaultRecords();
		 
		/* If no records exist, create defaults */
		if( !StoreSettings::get_settings() ) {
			 
			//Fetch SiteConfig
			$SiteConfig = SiteConfig::current_site_config();
			
			$n = new StoreSettings();
			
			$n->StoreSettings_StoreAvailable = "1";
			$n->StoreSettings_StoreAvailableMessage = "Our store is currently offline for maintenance and will be back online soon.";
			$n->StoreSettings_StoreName = $SiteConfig->Title . " Online Store";
			$n->StoreSettings_StoreCountry = "GB";
			$n->StoreSettings_ProductWeight = "Grams";
			$n->StoreSettings_ProductDimensions = "Centimetres";
			
			$n->DisplaySettings_FeaturedProducts = "5";
			$n->DisplaySettings_NewProducts = "5";
			$n->DisplaySettings_CartQuantity = "1";
			
			$n->DisplaySettings_ShowPrice = "1";
			$n->DisplaySettings_ShowSKU = "1";
			$n->DisplaySettings_ShowWeight = "1";						
			$n->DisplaySettings_ShowDimensions = "1";	
			$n->DisplaySettings_ProductSort = "Created DESC";
			$n->DisplaySettings_ProductPagePhotoWidth = "250";
			$n->DisplaySettings_ProductPagePhotoHeight = "250";
			$n->DisplaySettings_ProductThumbnailPhotoWidth = "125";
			$n->DisplaySettings_ProductThumbnailPhotoHeight = "125";
			$n->DisplaySettings_ProductEnlargedPhotoWidth = "1280";
			$n->DisplaySettings_ProductEnlargedPhotoHeight = "768";
			
			$n->CheckoutSettings_InitialStatus = "1";
			$n->CheckoutSettings_GuestCheckout = "1";
			$n->CheckoutSettings_GuestCheckoutAccount = "1";
			$n->CheckoutSettings_OrderComments = "1";
			
			$n->EmailNotification_AccountCreated = "1";
			$n->EmailNotification_OrderPlaced = "1";
			$n->EmailNotification_OrderStatuses = "3,8,4,7,2,6,5";
			
			$n->Stock_StockManagement = "1";
			$n->Stock_PendingOrdersFreezeStock = "90";
			$n->Stock_LowStockThreshold = "2";
			$n->Stock_OutOfStockThreshold = "0";
			$n->Stock_OutofStockMessage = "Out of Stock";
			$n->Stock_ProductOutOfStock = "3";
			$n->Stock_OptionOutOfStock = "2";
			$n->Stock_StockLevelDisplay = "3";
			
			$n->TaxSettings_InclusiveExclusive = "1";
			$n->TaxSettings_ShippingInclusiveExclusive = "1";
			$n->TaxSettings_CalculateUsing = "1";
			
			$n->write();
			
			unset($n);
			
			DB::alteration_message('Created default store configuration', 'created');
			 		 
		}
		
	}

}