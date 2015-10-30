<?php
/**
 * A subclass of LeftMain to construct the UI for the Store Products.
 * 
 * @package torindul-silverstripe-shop
 * @subpackage controller
 */
class StoreProductsAdmin extends LeftAndMain implements PermissionProvider { 
	
    private static $url_segment = "store-products"; 
    private static $menu_title = "Store Products";  
    private static $menu_icon = "torindul-silverstripe-shop/images/icons/store-products-cms-icon.png";
    private static $allowed_actions = array(
	    'getEditForm',
    );

	/**
	 * @uses LeftAndMain::index()
	 */	
    public function index($index) {
	    return parent::index($index);
    }     
    
	
	/* Get the store settings */
	public static function get_settings() {
		return StoreSettings::get_settings();
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
		return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0;
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
			$actions = FieldList::create()
		)
		->setHTMLID('Form_EditForm')
		->setResponseNegotiator( $this->getResponseNegotiator() )
		->addExtraClass('cms-edit-form center ss-tabset cms-tabset ' . $this->BaseCSSClasses())
		->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		
		//If a Root Tabset exists in our fields render them with the defined template.
		( $form->Fields()->hasTabset() ) ? $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet') : null;

		//Permit the modification of this forms fields through a DataExtension.
		$this->extend('updateStoreCMSFields', $fields);

		return $form;
		
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
		
		$settings = self::get_settings();
		
		//Create the FieldList and push the Root TabSet on to it.
		$fields = FieldList::create(
			
			$root = TabSet::create(
				'Root',
				
				TabSet::create(
					"Products",
					
					//Product Views
					TabSet::create(
						"Products",
						
						Tab::create(
							"All",
						
							HeaderField::create("All Products"),
							CompositeField::create(
								
								LiteralField::create($title="CustomFieldsDescription",
									"<div class=\"literal-field literal-field-noborder\">
										The table below represents all products in your store.
									</div>"
								),

								GridField::create(
									"AllProducts",
									"",
									DataObject::get("Product", "", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							)
							
						),
						
						Tab::create(
							"Featured",
							
							HeaderField::create("Featured Products"),
							CompositeField::create(
								
								LiteralField::create($title="CustomFieldsDescription",
									"<div class=\"literal-field literal-field-noborder\">
										The table below represents all products which have had 'Featured Product' set to yes. These
										products will be shown within the 'Featured Products' section of your store front.
									</div>"
								),

								GridField::create(
									"FeaturedProducts",
									"",
									DataObject::get("Product", "(Featured = 1)", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							)
							
						),
						
						Tab::create(
							"LowStock",
							
							HeaderField::create("Low Stock Products"),
							CompositeField::create(
								
								LiteralField::create($title="CustomFieldsDescription",
									"<div class=\"literal-field literal-field-noborder\">
										The table below represents all products with a stock level below their low stock level.
									</div>"
								),

								GridField::create(
									"LowStock",
									"",
									DataObject::get(
										"Product", 
										"(StockLevel<=".$settings->Stock_LowStockThreshold.") ".
										"AND (StockLevel>".$settings->Stock_OutOfStockThreshold.")", 
										"Title ASC"
									),
									GridFieldConfig_RecordEditor::create()
								)
								
							)
							
						),
						
						Tab::create(
							"OutOfStock",
							
							HeaderField::create("Out of Stock Products"),
							CompositeField::create(
								
								LiteralField::create($title="CustomFieldsDescription",
									"<div class=\"literal-field literal-field-noborder\">
										The table below represents any product in your store which has a stock level of zero.
									</div>"
								),

								GridField::create(
									"OutofStock",
									"",
									DataObject::get("Product", "(StockLevel<=".$settings->Stock_OutOfStockThreshold.")", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							)
							
						),
						
						Tab::create(
							"Visible",
							
							HeaderField::create("Visible Products"),
							CompositeField::create(
								
								LiteralField::create($title="CustomFieldsDescription",
									"<div class=\"literal-field literal-field-noborder\">
										The table below represents products that you have chosen to display in your store.
									</div>"
								),

								GridField::create(
									"VisibleProducts",
									"",
									DataObject::get("Product", "(Visible=1)", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							)
							
						),
						
						Tab::create(
							"NotVisible",
							
							HeaderField::create("Not Visible Products"),
							CompositeField::create(
								
								LiteralField::create($title="CustomFieldsDescription",
									"<div class=\"literal-field literal-field-noborder\">
										The table below represents products that you have chosen not to display in your store.
									</div>"
								),

								GridField::create(
									"NotVisibleProducts",
									"",
									DataObject::get("Product", "(Visible=0)", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							)
							
						)
						
					),
					
					Tab::create(
						"Brands",
							
						HeaderField::create("Product Brands"),
						CompositeField::create(
							
							LiteralField::create($title="CustomFieldsDescription",
								"<div class=\"literal-field literal-field-noborder\">
									Brands can be associated to products, therefore allowing your customers to shop by browsing
									their favourite brands. 
								</div>"
							),

							GridField::create(
								"ProductBrands",
								"",
								DataObject::get("Product_Brands", "", "Title ASC"),
								GridFieldConfig_RecordEditor::create()
							)
							
						)
							
					),
					
					Tab::create(
						"Categories",
							
						HeaderField::create("Product Categories"),
						CompositeField::create(
							
								LiteralField::create($title="CustomFieldsDescription",
									"<div class=\"literal-field literal-field-noborder\">
										Categories allow you to group products by similar characteristics or groups. For example, as a
										clothing retailer you may chose to have 'Mens, Womens, Boys and Girls' as some categories.
									</div>"
								),

								GridField::create(
									"ProductCategories",
									"",
									DataObject::get("Product_Categories", "", "Title ASC"),
									GridFieldConfig_RecordEditor::create()
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
    
}