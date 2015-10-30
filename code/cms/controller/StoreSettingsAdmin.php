<?php
/**
 * A subclass of LeftMain to construct the UI for the Store Settings.
 * 
 * @package torindul-silverstripe-shop
 * @subpackage controller
 */
class StoreSettingsAdmin extends LeftAndMain implements PermissionProvider { 
	
    private static $url_segment = "store-settings"; 
    private static $menu_title = "Store Settings";  
    private static $menu_icon = "torindul-silverstripe-shop/images/icons/store-settings-cms-icon.png";
    private static $allowed_actions = array(
	    'getEditForm',
    	'savesettings',
    );

	/**
	 * @uses LeftAndMain::index()
	 */	
    public function index($index) {
	    return parent::index($index);
    }     

	/**
	 * Includes custom styling and javascript for this LeftAndMain subclass
	 *
	 * @uses LeftAndMain::init()
	 */
    public function init() {
	    parent::init();
	    Requirements::css('torindul-silverstripe-shop/css/LeftAndMain.css');
	    Requirements::css('torindul-silverstripe-shop/font-awesome/css/font-awesome.min.css');
    }
    
    /**
	 * Checks if a user has Store Access permissions
	 *
	 * @return boolean
	 */
	public function hasPermission() {
		return ( Permission::check("CMS_ACCESS_StoreSettings") ) ? 1 : 0;
	}
    
	/**
	 * @uses self::hasPermission()
	 */
	public function canView( $member = null ) {
		return self::hasPermission();
	}
	
	/**
	 * @uses self::hasPermission()
	 */
	public function canEdit( $member = null ) {
		return self::hasPermission();
	}
	
	/* 
	 * @uses LeftAndMain::Breadcrumbs()
	 */
	public function Breadcrumbs($unlinked = false) {
		return parent::Breadcrumbs($unlinked = false);
	}
    
    /*
	 * Overrides parent::getResponseNegotiator() 
	 */
	public function getResponseNegotiator() {
		if(!$this->responseNegotiator) {
			$controller = $this;
			$this->responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
					},
					'Content' => function() use(&$controller) {
						return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
					},
					'Breadcrumbs' => function() use (&$controller) {
						return $controller->renderWith('CMSBreadcrumbs');
					},
					'default' => function() use(&$controller) {
						return $controller->renderWith($controller->getViewer('show'));
					}
				),
				$this->response
			);
		}
		return $this->responseNegotiator;
	}
    
    /* 
	 * Construct the Edit Form within the Main screen.
	 *
	 * @uses self::construct_cms_fields()
	 * @uses self::construct_cms_actions()
	 * @uses StoreSettings::getCMSValidator()
	 * @return CMSForm
	 */
	public function getEditForm($id = null, $fields = null) {
		
		/**
		 * Collecting all definitions above, create our form.
		 */
		$form = CMSForm::create( 
			$name = $this,
			$title = 'getEditForm',
			$fields = self::construct_cms_fields(),
			$actions = self::construct_cms_actions(),
			$validator = StoreSettings::getCMSValidator()
		)
		->setHTMLID('Form_EditForm')
		->setResponseNegotiator( $this->getResponseNegotiator() )
		->addExtraClass('cms-edit-form center ss-tabset cms-tabset ' . $this->BaseCSSClasses())
		->setTemplate($this->getTemplatesWithSuffix('_EditForm'))
		->loadDataFrom( StoreSettings::get_settings() );
		
		//If a Root Tabset exists in our fields render them with the defined template.
		( $form->Fields()->hasTabset() ) ? $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet') : null;

		//Permit the modification of this forms fields through a DataExtension.
		$this->extend('updateStoreCMSFields', $fields);

		return $form;
		
	}
	
    /*
	 * Action for saving the Settings within the Setup tab created in constructCMSField()
	 */
    public function savesettings($data, $form) {
		
		$StoreSettings = StoreSettings::get_settings();
		$form->saveInto($StoreSettings);
		
		try {
			$StoreSettings->write();
		} catch(ValidationException $ex) {
			$form->sessionMessage($ex->getResult()->message(), 'bad');
			return $this->getResponseNegotiator()->respond($this->request);
		}
		
		$this->response->addHeader('X-Status', rawurlencode("Store Settings Saved Successfully"));
		return $this->getResponseNegotiator()->respond($this->request);
		
    }
	
    /**
     * Return the edit form
     *
     * @param null $request
     * @return Form
     */
    public function EditForm($request = null) {
        return $this->getEditForm();
    }
	
	/**
	* Constructs a FieldList for use in the CMSForm.
	*
	* @return FieldList
	*/
	public function construct_cms_fields() {
		
		//Create the FieldList and push the Root TabSet on to it.
		$fields = FieldList::create(
			
			$root = TabSet::create(
				'Root',
				
				Tabset::create(
					"Settings",
					
					//General Settings
					Tabset::create(
						"General",
						
						//General Settings - Store Settings
						Tab::create(
							"StoreSettings",
							//Maintenance Mode
							HeaderField::create("Maintenance Mode"),
							CompositeField::create(
								
								DropdownField::create(
									"StoreSettings_StoreAvailable",
									"Store Status",
									array(
										"1" => "Live",
										"2" => "Down for Maintenance (show the below message)",
									)
								),
							
								TextareaField::create(
									"StoreSettings_StoreAvailableMessage", 
									"Maintenance Message"
								)->setRightTitle("The message to display to visitors whilst your store is in maintenance mode.")
							
							),
							
							//Store Details
							HeaderField::create("Store Details"),
							CompositeField::create(
								
								TextField::create(
									"StoreSettings_StoreName",
									"Store Name"
								)->setRightTitle("i.e. Steve's Shoe Shop."),
								
								TextareaField::create(
									"StoreSettings_StoreAddress",
									"Store Address"
								)->setRightTitle("<strong>Example</strong><br />123 ACME Drive<br />Enfield<br />London</br />EN1 1YQ"),
								
								CountryDropdownField::create(
									"StoreSettings_StoreCountry",
									"Store Country"
								)
								
							),
							
							//Product Dimensions
							HeaderField::create("Product Measurements"),
							CompositeField::create(				
								
								DropdownField::create(
									"StoreSettings_ProductWeight", 
									"Product Weight", 
									array(
										"Pounds" => "Pounds",
										"Ounces" => "Ounces",
										"Grams" => "Grams",
										"Kilograms" => "Kilograms",
										"Tonns" => "Tonns"
									)
								)->setRightTitle("Select the weight measurement you wish to use in your store."),
								
								DropdownField::create(
									"StoreSettings_ProductDimensions", 
									"Product Dimensions", 
									array(
										"Inches" => "Inches",
										"Millimetres" => "Millimetres",
										"Centimetres" => "Centimetres"
									)
								)->setRightTitle("Select the width, height and length measurement you wish to use in your store.")
								
							),
							
							//SSL
							HeaderField::create("SSL"),
							CompositeField::create(				
								
								LiteralField::create("SSL_LiteralField",
									"<div class=\"literal-field literal-field-noborder\">
									
										<div class=\"message warning\">
											<strong>IMPORTANT:</strong><br />
											In order to protect your customers personally identifiable information it is
											strongly recommended that you consider installing an SSL certificate for your website.
											<br />If you are unsure on how to do this please reach out to the party responsible
											for this website. 
										</div>
										
										Enabling the option below will ensure all communications between third-party providers
										are transmitted over the https:// protocol.
									</div>"
								),
								
								CheckboxField::create(
									"StoreSettings_SSLToggle",
									"Yes, I have SSL enabled."
								)
								
							)
							
						),
						
						//General Settings - Display Settings
						Tab::create(
							"DisplaySettings",
							//General Display Settings
							HeaderField::create("General Display Settings"),
							CompositeField::create(
								
								NumericField::create(
									"DisplaySettings_FeaturedProducts",
									"Featured Products"
								)->setRightTitle("Enter the number of products you wish to display in the Featured Products panels."),
								
								NumericField::create(
									"DisplaySettings_NewProducts",
									"New Products"
								)->setRightTitle("Enter the number of products you wish to display in the New Products panels."),
													
								DropdownField::create(
									"DisplaySettings_CartQuantity",
									"Cart Quantity Selection", 
									array(
										"1" => "Dropdown",
										"2" => "Textbox",
									)
								)->setRightTitle("Select how you wish the quantity selector to be shown on the product pages.")
								
							),
							
							//Product Display Settings
							HeaderField::create("Product Display Settings"),
							CompositeField::create(
								
								CheckboxField::create(
									"DisplaySettings_ShowPrice",
									"Show product prices im my store."
								),
								
								CheckboxField::create(
									"DisplaySettings_ShowSKU", 
									"Show product SKUs in my store."
								),
								
								CheckboxField::create(
									"DisplaySettings_ShowWeight", 
									"Show product weights in my store."
								),
								
								CheckboxField::create(
									"DisplaySettings_ShowDimensions", 
									"Show product dimensions in my store."
								),
								
								DropdownField::create(
									"DisplaySettings_ProductSort",
									"Default Product Sort",
									array(
										"Title ASC" => "Product Name Ascending",
										"Title DESC" => "Product Name Descending",
										"SalePrice ASC, RegularPrice ASC" => "Price Lowest to Highest",
										"SalePrice DESC, RegularPrice DESC" => "Price Highest to Lowest",
										"Created ASC" => "Oldest First",
										"Created DESC" => "Newest First",
									)
								)
								
							),
							
							//Product Photo Display Settings
							HeaderField::create("Product Photo Display Settings"),
							CompositeField::create(
								
								LiteralField::create("ProductPagePhoto_LiteralField",
									"<div class=\"literal-field\">
										<strong>Product Page Photo Size</strong><br />
										Enter the width and height to use for the main photo on a product page.
									</div>"
								),
								
								FieldGroup::create(
									
									NumericField::create(
										"DisplaySettings_ProductPagePhotoWidth",
										"Width (pixels)"
									),
									
									NumericField::create(
										"DisplaySettings_ProductPagePhotoHeight",
										"Width (pixels)"
									)
									
								),
								
								LiteralField::create("ProductThumbnailPhoto_LiteralField",
									"<div class=\"literal-field\">
										<strong>Product Thumbnail Photo Size</strong><br />
										Enter the width and height to use for product thumbnails throughout the store.
									</div>"
								),
							
								FieldGroup::create(
									
									NumericField::create(
										"DisplaySettings_ProductThumbnailPhotoWidth",
										"Width (pixels)"
									),
									
									NumericField::create(
										"DisplaySettings_ProductThumbnailPhotoHeight",
										"Height (pixels)"
									)
									
								),
								
								LiteralField::create("ProductEnlargedPhoto_LiteralField",
									"<div class=\"literal-field\">
										<strong>Enlarged Photo Size</strong><br />
										Enter the width and height to use for enlarged photos. Users will see this if they click
										to see the larger version of a product photo from a product page.
									</div>"
								),
								
								FieldGroup::create(
									
									NumericField::create(
										"DisplaySettings_ProductEnlargedPhotoWidth",
										"Width (pixels)"
									),
									
									NumericField::create(
										"DisplaySettings_ProductEnlargedPhotoHeight",
										"Height (pixels)"
									)
									
								)
								
							)
							
						)
						
					),
					
					//Orders Settings
					Tabset::create(
						"Orders",
						
						//Orders Settings - Checkout Settings
						Tab::create(
							"CheckoutSettings",
							//Initial Order Status
							HeaderField::create("Initial Order Status"),
							CompositeField::create(
								
								DropdownField::create(
									"CheckoutSettings_InitialStatus",
									"Status",
									DataObject::get("Order_Statuses", "", "Title ASC")->map('ID', 'Title')
								)->setRightTitle("Select the status you wish all new orders to be set to.")
								
							),
							
							//Guest Checkout
							HeaderField::create("Guest Checkout"),
							CompositeField::create(
								
								CheckboxField::create(
									"CheckoutSettings_GuestCheckout", 
									"Allow customers to checkout as a guest without the need for an account."
								),
								
								CheckboxField::create(
									"CheckoutSettings_GuestCheckoutAccount", 
									"Create an account for customers if they checkout as a guest (if they do not already have one)"
								)
								
							),
							
							//Order Comments
							HeaderField::create("Order Comments"),
							CompositeField::create(
								
								CheckboxField::create(
									"CheckoutSettings_OrderComments", 
									"Allow customers to provide a comment with their orders."
								)
								
							),
							
							//Terms & Conditions
							HeaderField::create("Terms &amp; Conditions"),
							CompositeField::create(
								
								CheckboxField::create(
									"CheckoutSettings_TermsAndConditions", 
									"I require my customers to have agreed to terms and conditions before they can place an order."
								),
								
								TreeDropdownField::create(
									"CheckoutSettings_TermsAndConditionsSiteTree", 
									"Terms &amp; Conditions Page",
									"SiteTree"
								)
								
							)
							
						),
						
						//Orders Settings - Order Statuses
						Tab::create(
							"Order Statuses",
							//Custom Order Statuses
							HeaderField::create("Custom Order Statuses"),
							CompositeField::create(
								
								LiteralField::create("CustomOrderStatus_LiteralField",
									"<div class=\"literal-field\">
										This section allows you to specify custom order statuses where those out of the box
										do not otherwise match your businesses requirements.
									</div>"
								),
								
								GridField::create(
									"CustomOrderStatus",
									"",
									DataObject::get("Order_Statuses", "(0=SystemCreated)", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							),
							
							//Default Order Statuses
							HeaderField::create("Default Order Statuses"),
							CompositeField::create(
								
								LiteralField::create("DefaultOrderStatus_LiteralField",
									"<div class=\"literal-field\">
										This section displays the default order statuses that are used throughout the store. Feel free
										to alter their Display Name and Descriptions to suit the needs of your business.
									</div>"
								),
								
								GridField::create(
									"DefaultOrderStatus",
									"",
									DataObject::get("Order_Statuses", "(1=SystemCreated)", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
									->removeComponentsByType("GridFieldAddNewButton")
									->removeComponentsByType("GridFieldDeleteAction")
								)
								
							)
							
						),
						
						//Orders Settings - Email Notifications
						Tab::create(
							"EmailNotifications",
							
							HeaderField::create("Send store emails from"),
							CompositeField::create(
								
								EmailField::create(
									"EmailNotification_SendEmailsFrom", 
									"From Email Address"
								)
								->setRightTitle("
									This is the address that is used in the 'From' section of all automatically generated emails.<br />
									<strong>SMTP Users:</strong> If you are using a SilverStripe SMTP module it is strongly recommended
									you set this value to that of the authenticated SMTP email address.")
								
							),
							
							HeaderField::create("New order notifications"),
							CompositeField::create(
								
								EmailField::create(
									"EmailNotification_AdminNewOrder", 
									"Admin Notification Email"
								)
								->setRightTitle("When the store receives a new order send a notification to the above address.")
								
							),
							
							//General Email Notifications
							HeaderField::create("Send the customer an email when"),
							CompositeField::create(
								
								CheckboxField::create(
									"EmailNotification_AccountCreated", 
									"They create an account."
								),
									
								CheckboxField::create(
									"EmailNotification_OrderPlaced", 
									"They place an order with my store."
								)
								
							),
							
							//Order Status Change
							HeaderField::create("Send the customer an email when their order status changes to"),
							CompositeField::create(
								
								CheckboxSetField::create(
									"EmailNotification_OrderStatuses",
									"Pick your desired statuses",
									DataObject::get("Order_Statuses", "", "Title ASC")->map('ID', 'Title')
								)
								
							)	
													
						)
							
					),
					
					Tab::create(
						"Stock",
						//Stock Management 
					 	HeaderField::create("Stock Management"),
						CompositeField::create(
							
							LiteralField::create("StockManagement_LiteralField",
								"<div class=\"literal-field\">
									<strong>What is Stock Management?</strong><br />
									Stock management is designed to keep tabs on your stock levels. If enabled, stock levels will be
									managed by the store and automatically decremented upon receiving a successful order.<br />
									It will also prevent customers from placing an order for a product if it is deemed to
									be out of stock.
								</div>"
							),
							
							CheckboxField::create(
								"Stock_StockManagement", 
								"Enable stock control in my store." 
							)
							
						),
						
						//Pending Orders
					 	HeaderField::create("Pending Orders"),
						CompositeField::create(		
							
							LiteralField::create(
								"PendingOrders_LiteralField",
								"<div class=\"literal-field\">
								
									Enter the number of minutes that stock allocated to unpaid orders will be held before the 
									associated order is cancelled and held stock is made available to other customers.
									
								</div>"
							),			
							
							FieldGroup::create(
								
								NumericField::create(
									"Stock_PendingOrdersFreezeStock", 
									"" 
								)
								
							)
							
						),
						
						//Stock Levels
					 	HeaderField::create("Stock Levels"),
						CompositeField::create(
							
							FieldGroup::create(		
							
								NumericField::create(						
									"Stock_LowStockThreshold", 
									"Low Stock Threshold"
								)
								->setRightTitle("The stock level considered as low."),
									
								NumericField::create(						
									"Stock_OutOfStockThreshold", 
									"Out of Stock Threshold"
								)
								->setRightTitle("The stock level considered as out of stock."),
								
								TextField::create(
									"Stock_OutofStockMessage",
									"Out of Stock Message"
								)
								->setRightTitle("The message to display when a product is out of stock.")
							
							),
							
							DropdownField::create(
								"Stock_ProductOutOfStock",
								"Product Out of Stock",
								array(
									"1" => "Completely hide the product from my store",
									"2" => "Hide the product but allow the product page to be accessed from its URL",
									"3" => "Do not make any changes",
								)
							)
							->setRightTitle("Select what you wish to happen when a product is out of stock."),
							
							//TODO - Product Options
							/* 
							DropdownField::create(
								"Stock_OptionOutOfStock",
								"Option Out of Stock",
								array(
									"1" => "Completely hide the product option",
									"2" => "Keep the product option visible but shown as out of stock",
									"3" => "Do not make any changes",
								)
							)
							->setRightTitle("Select what you wish to happen when a product option is out of stock."),*/
							
							DropdownField::create(
								"Stock_StockLevelDisplay",
								"Stock Level Display",
								array(
									"1" => "Always show stock levels",
									"2" => "Show stock levels after the product drops below the low stock threshold",
									"3" => "Never show stock levels",
								)
							)
							->setRightTitle("Select how you would like stock levels to appear in your store.")
							
						)
						
					),
					
					Tab::create(
						"Couriers",
						HeaderField::create("Couriers"),
						CompositeField::create(
							
							GridField::create(
								"CourierSettings",
								"",
								DataObject::get("Courier", "", "Title ASC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
								->removeComponentsByType("GridFieldDeleteAction")								
							)
							
						)
					),
					
					Tab::create(
						"Gateways",
						HeaderField::create("Gateways"),
						CompositeField::create(
							
							GridField::create(
								"GatewaySettings",
								"",
								DataObject::get("Gateway", "", "Title ASC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
								->removeComponentsByType("GridFieldDeleteAction")								
							)
							
						)
					),
					
					Tab::create(
						"Currency",
						//Accepted Currencies
						HeaderField::create("Accepted Currencies"),
						CompositeField::create(
							
							LiteralField::create(
								"CurrencySettings_LiteralField",
								"<div class=\"literal-field\">
									You can define additional currencies in this section. Customers will be shown prices in their
									selected currency throughout the store but will transact in your local currency.
								</div>"
							),
							
							GridField::create(
								"CurrencySettings_AcceptedCurrencies",
								"",
								DataObject::get("StoreCurrency", "(0=SystemCreated)", "Title ASC"),
								GridFieldConfig_RecordEditor::create()
							)
							
						),
						
						//Default Currency
						HeaderField::create("Local (Default) Currency"),
						CompositeField::create(
							
							LiteralField::create(
								"CurrencySettings_LiteralField",
								"<div class=\"literal-field\">
									Configure your store's local (default) currency in this section.
								</div>"
							),
							
							GridField::create(
								"CurrencySettings_LocalCurrency",
								"",
								DataObject::get("StoreCurrency", "(1=SystemCreated)", "Title ASC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
								->removeComponentsByType("GridFieldDeleteAction")								
							)
							
						)
						
					),
					
					Tabset::create(
						"Tax",
						
						//Tax - Tax Settings
						Tab::create(
							"TaxSettings",
							HeaderField::create("Tax Settings"),
							CompositeField::create(
							
								DropdownField::create(
									"TaxSettings_InclusiveExclusive",
									"Product Prices Are",
									array(
										"1" => "Inclusive of Tax",
										"2" => "Exclusive of Tax",
									)
								),
								
								DropdownField::create(
									"TaxSettings_ShippingInclusiveExclusive",
									"Shipping Prices Are",
									array(
										"1" => "Inclusive of Tax",
										"2" => "Exclusive of Tax",
									)
								),
								
								DropdownField::create(
									"TaxSettings_CalculateUsing",
									"Calculate Tax Using",
									array(
										"1" => "Billing Address",
										"2" => "Shipping Address",
										"3" => "Store Address",
									)
								)
								
							)
							
						),
						
						//Tax - Tax Classes
						Tab::create(
							"TaxClasses",
							//Other Tax Classes
							HeaderField::create("Other Tax Classes"), 
							CompositeField::create(
								
								LiteralField::create(
									"CurrencySettings_LiteralField",
									"<div class=\"literal-field\">
										A Tax class allows you to define tax categories.
									</div>"
								),
								
								GridField::create(
									"TaxSettings_ClassesOther",
									"",
									DataObject::get("TaxClasses", "(0=SystemCreated)", "ID ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							),
							
							//Default Tax Classes
							HeaderField::create("Default Tax Classes"), 
							CompositeField::create(
								
								LiteralField::create(
									"CurrencySettings_LiteralField",
									"<div class=\"literal-field\">
										These are the default tax classes.
									</div>"
								),
								
								GridField::create(
									"TaxSettings_ClassesDefault",
									"",
									DataObject::get("TaxClasses", "(1=SystemCreated)", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
									->removeComponentsByType("GridFieldAddNewButton")
									->removeComponentsByType("GridFieldDeleteAction")
								)
								
							)
							
						),
						
						//Tax - Tax Zones
						Tab::create(
							"TaxZones",
							//Other Tax Zones
							HeaderField::create("Other Tax Zones"), 
							CompositeField::create(
								
								LiteralField::create(
									"CurrencySettings_LiteralField",
									"<div class=\"literal-field\">
										A Tax Zone allows you to define Tax rules on a country by country basis.
									</div>"
								),
								
								GridField::create(
									"TaxSettings_Zones",
									"",
									DataObject::get("TaxZones", "(0=SystemCreated)", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							),
							
							//Default Tax Zone
							HeaderField::create("Default Tax Zone"), 
							CompositeField::create(
								
								LiteralField::create(
									"CurrencySettings_LiteralField",
									"<div class=\"literal-field\">
										This is the default tax zone applies where no zone exists for a customers' country.
									</div>"
								),
								
								GridField::create(
									"TaxSettings_ZonesDefault",
									"",
									DataObject::get("TaxZones", "(1=SystemCreated)", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
									->removeComponentsByType("GridFieldAddNewButton")
									->removeComponentsByType("GridFieldDeleteAction")
								)
								
							)
							
						)
						
					),
					
					//Product Reviews
					Tab::create(
						"Reviews",
						
						HeaderField::create("Product Reviews"), 
						CompositeField::create(
						
							CheckboxField::create(
								"ProductReviewSettings_EnableReviews", 
								"Enable product reviews in my store."
							),
							
							CheckboxField::create(
								"ProductReviewSettings_ApprovedPurchaserOnly", 
								"Require a customer to have purchased an item before making a review."
							),
							
							CheckboxField::create(
								"ProductReviewSettings_AdminApproval", 
								"Require admin approval before reviews are made visible."
							)
							
						)
						
					)
					
				)
				
			),
			
			// necessary for tree node selection in LeftAndMain.EditForm.js
			HiddenField::create('ID', false, 0)
			
		);
		
		//Tab nav in CMS is rendered through separate template		
		$root->setTemplate('CMSTabSet');
		
		return $fields;
		
	}
	
	/*
	 * Constructs a FieldList of FormActions for use in the CMSForm  
	 *
	 * @return FieldList
	 */
	public function construct_cms_actions() {
		
		//Create the FieldList
		$actions = FieldList::create();
		
		//Save Settings Form Action. Only show this if user has permission to see the settings.
		if( Permission::check("SHOP_ACCESS_Settings") ) {
			
			$actions->push( 
				FormAction::create('savesettings', 'Save Settings')
					->addExtraClass('ss-ui-action-constructive')
					->setAttribute('data-icon', 'accept')
					->setUseButtonTag(true)
			);
			
		}
		
		return $actions;
		
	}
	
	/**
	* Define permission levels.
	*
	* @see http://doc.silverstripe.org/en/developer_guides/security/permissions/
	* @return Array The array of permissions used in this section of the CMS.
	*/
	public function providePermissions() {
		
		return array(
			
			"CMS_ACCESS_StoreSettings" => array(
				'name' => "Access to 'Store Settings' section",
				'category' => 'CMS Access',
				'help' => 'Allow viewing of the Store Settings section of the CMS.'
			),
			
			"SHOP_ACCESS_Orders" => array(
				'name' => "Manage Orders",
				'category' => 'Store Permissions',
				'help' => "Permit the user to manage orders."
			),
			
			"SHOP_ACCESS_Products" => array(
				'name' => "Manage Products",
				'category' => 'Store Permissions',
				'help' => "Permit the user to manage the stores's products"
			),
			
			"SHOP_ACCESS_Customers" => array(
				'name' => "Manage Customers",
				'category' => 'Store Permissions',
				'help' => "Permit the user to manage the stores's customers."
			),
			
			"SHOP_ACCESS_Settings" => array(
				'name' => "Manage Store Settings",
				'category' => 'Store Permissions',
				'help' => "Permit the user to manage the store's settings."
			),
			
		);
		
	}
    
}