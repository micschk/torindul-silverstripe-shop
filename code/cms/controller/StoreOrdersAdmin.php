<?php
/**
 * A subclass of LeftMain to construct the UI for the Store Orders.
 * 
 * @package torindul-silverstripe-shop
 * @subpackage controller
 */
class StoreOrdersAdmin extends LeftAndMain implements PermissionProvider { 
	
    private static $url_segment = "store-orders"; 
    private static $menu_title = "Store Orders";  
    private static $menu_icon = "torindul-silverstripe-shop/images/icons/store-orders-cms-icon.png";
    private static $allowed_actions = array(
	    'getEditForm',
	    'neworder',
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
	 * Override Link 
	 */
	public function Link($action = null) {
		return Director::BaseURL() . "admin/store-orders/";
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
			$actions = self::construct_cms_actions()
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
		
		//Create the FieldList and push the Root TabSet on to it.
		$fields = FieldList::create(
			
			$root = TabSet::create(
				'Root',
				
				Tabset::create(
					"Orders",
						
					Tab::create(
						$title="All Orders",
						
						HeaderField::create($title),
						CompositeField::create(

							GridField::create(
								"AllOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`!='1')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
							)
							
						)
						
					),
					
					Tab::create(
						$title="Pending",
						
						LiteralField::create("Pending_LiteralField",
							"<div class=\"literal-field literal-field-noborder\">
								<div class=\"message warning\">
									<strong>THESE ORDERS ARE UNPAID:</strong><br />
									These orders have been placed but their associated payment gateway is yet to respond
									with a payment status. Do not process / dispatch these orders until they are
									moved to another status.
								</div>		
							</div>"
						),
						
						HeaderField::create($title . " Orders"),
						CompositeField::create(

							GridField::create(
								"PendingOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`='1')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
							)
							
						)
						
					),
					
					Tab::create(
						$title="Processing",
						
						HeaderField::create($title . " Orders"),
						CompositeField::create(

							GridField::create(
								"ProcessingOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`='2')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
							)
							
						)
						
					),		
					
					Tab::create(
						$title="Awaiting Stock",
						
						HeaderField::create($title),
						CompositeField::create(

							GridField::create(
								"AwaitingStockOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`='3')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
							)
							
						)
						
					),								
					
					Tab::create(
						$title="Completed",
						
						HeaderField::create($title),
						CompositeField::create(

							GridField::create(
								"CompletedOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`='4')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
							)
							
						)
						
					),	
					
					Tab::create(
						$title="Shipped",
						
						HeaderField::create($title . " Orders"),
						CompositeField::create(

							GridField::create(
								"ShippedOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`='5')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
							)
							
						)
						
					),			
					
					Tab::create(
						$title="Refunded",
						
						HeaderField::create($title . " Orders"),
						CompositeField::create(

							GridField::create(
								"RefundedOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`='6')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
							)
							
						)
						
					),	
					
					Tab::create(
						$title="Part Refunded",
						
						HeaderField::create($title . " Orders"),
						CompositeField::create(

							GridField::create(
								"PartRefundedOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`='7')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
							)
							
						)
						
					),	
					
					Tab::create(
						$title="Cancelled",
						
						HeaderField::create($title . " Orders"),
						CompositeField::create(

							GridField::create(
								"CancelledOrders",
								"",
								DataObject::get("Order", "(`TempBasketID` IS NULL) AND (`Status`='8')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
							)
							
						)
						
					),	
					
					Tab::create(
						$title="Other Statuses",
						
						HeaderField::create("Other Status"),
						CompositeField::create(
							
							LiteralField::create($title="CustomFieldsDescription",
								"<div class=\"literal-field literal-field-noborder\">
									This table displays all orders with a custom order statuses as set in Store Settings.
								</div>"
							),

							GridField::create(
								"CustomStatusOrders",
								"",
								DataObject::get("Order", "(`Status`>'8')", "Created DESC"),
								GridFieldConfig_RecordEditor::create()
								->removeComponentsByType("GridFieldAddNewButton")
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
	
	/* New Order Action */
	public function neworder() {
		return $this->redirect('./admin/store-orders/getEditForm/field/AllOrders/item/new');
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
		if( Permission::check("SHOP_ACCESS_Orders") ) {
			
			$actions->push( 
				FormAction::create('neworder', 'Create New Order')
					->addExtraClass('ss-ui-action-constructive')
					->setAttribute('data-icon', 'add')
					->setUseButtonTag(true)
			);
			
		}
		
		return $actions;
		
	}
    
}