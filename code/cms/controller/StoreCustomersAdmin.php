<?php
/**
 * A subclass of LeftMain to construct the UI for the Store Customers.
 * 
 * @package torindul-silverstripe-shop
 * @subpackage controller
 */
class StoreCustomersAdmin extends LeftAndMain implements PermissionProvider { 
	
    private static $url_segment = "store-customers"; 
    private static $menu_title = "Store Customers";  
    private static $menu_icon = "torindul-silverstripe-shop/images/icons/store-customers-cms-icon.png";
    private static $allowed_actions = array(
	    'getEditForm',
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
		return ( Permission::check("CMS_ACCESS_Customers") ) ? 1 : 0;
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
		
		//Create the FieldList and push the Root TabSet on to it.
		$fields = FieldList::create(
			
			$root = TabSet::create(
				'Root',
				
				Tab::create(
					"Customers",
					
					HeaderField::create("Viewing All Customers"),
					CompositeField::create(

							GridField::create(
								"Customer",
								"",
								DataObject::get("Customer", "", "Surname ASC"),
								GridFieldConfig_RecordEditor::create()
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