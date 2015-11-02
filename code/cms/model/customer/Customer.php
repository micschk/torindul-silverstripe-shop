<?php
/**
 * Model to store customers
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Customer extends Member {
	
	private static $singular_name = "Customer";
	private static $plural_name = "Customers";

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"CompanyName" => "Varchar",
		"LandlineNumber" => "Varchar",
		"MobileNumber" => "Varchar",
		"StoreCredit" => "Decimal(10,2)"
	);	
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array();
	
	/**
	 * Specifiy Has Many Relationships 
	 */
	private static $has_many = array(
		"Addresses" => "Customer_AddressBook",
		"Orders" => "Order"
	);
	
	/**
	 * Specify fields to display in GridFields 
	 */	
	public static $summary_fields = array(
		"ID" => "Account Number",
		"FirstName" => "First Name",
		"Surname" => "Last Name",
		"Email" => "Email",
		"LandlineNumber" => "Landline Number",
		"MobileNumber" => "Mobile Number",
		"StoreCredit" => "Store Credit"
	);
	
	/**
	 * Specify searchable fields 
	 */
	public static $searchable_fields = array(
		"FirstName",
		"Surname",
		"ID",
		"LandlineNumber",
		"MobileNumber",
		"Email"	
	);
	
    /**
     * getCMSFields
	 * Alter the FieldList provided by the Member class to 
	 * the requirements of an online store.
     * 
     * @return FieldList
     */
	public function getCMSFields() {
		
		Requirements::css('torindul-silverstripe-shop/css/LeftAndMain.css');
		
		$fields = parent::getCMSFields();
		
		/** REMOVE SCAFFOLDED FIELDS TO ADD LATER
		 * -- Locale is removed. As these users will not use the CMS itself so the default values will suffice for now.
		 * -- DirectGroups is removed. Customer group membership is handled by onAfterWrite().
		 * -- DateFormat and TimeFormat are removed. The default will be fine for nearly all installations.
		 * -- Those fields not mentioned are re-added later.
		 */
		$fields->removeFieldsFromTab("Root.Main", array(
			"FirstName",
			"Surname",
			"CompanyName", 
			"Email",
			"Password",
			"StoreCredit",
			"LandlineNumber", 
			"MobileNumber",
			"DirectGroups",
			"Locale",
			"FailedLoginCount",
			"LastVisited",
			"TimeFormat",
			"DateFormat"
		));	
		
		//If the customer record has been created, display the ID of the record as the account number.
		if($this->exists()) {
			
			$fields->addFieldsToTab("Root.Main", array(

				HeaderField::create("Account Details"),
				CompositeField::create( 
				
					ReadonlyField::create("AccountNo", "Account Number", $this->ID),
					ReadonlyField::create("LastVisited", "Last Signed In", $this->LastVisited),
					NumericField::create("StoreCredit", "StoreCredit")->setRightTitle(
						"The amount of credit this customer can spend on orders in your store."
					)
					
				)
				
			));
			
		}
		
		//Add Customer Details Fields
		$fields->addFieldsToTab("Root.Main", array(
			
			HeaderField::create("Customer Details"),
			CompositeField::create(
				TextField::create("FirstName", "First Name"),
				TextField::create("Surname", "Surname"),
				TextField::create("CompanyName", "Company Name"),
				TextField::create("LandlineNumber", "Landline Number"),
				TextField::create("MobileNumber", "Mobile Number")
			),
			
			HeaderField::create("Login Details"),
			CompositeField::create(
				EmailField::create("Email", "Email Address"),
				NumericField::create("FailedLoginCount", "Failed Login Count")
				->setRightTitle("The total number of failed login attempts this customer has made. Set to 0 to unblock their account.")
			)
			
		));
		
		//Add Confirmed Password Field befoe the FailedLoginCount field
		$password = ConfirmedPasswordField::create('Password', null, null, null, true /*showOnClick*/)->setCanBeEmpty(true);
		if(!$this->ID) { $password->showOnClick = false; } 
		$fields->addFieldToTab("Root.Main", $password, "FailedLoginCount");
		
		//Customer Address Book Tab
		$fields->addFieldsToTab("Root.AddressBook", array(
		
			HeaderField::create( ($this->exists()) ?
				$this->FirstName . " " . $this->Surname . "'s Address Book" : 
				"Customer Address Book"
			),
			CompositeField::create(
				
				LiteralField::create($title="CustomFieldsDescription",
					"<div class=\"literal-field literal-field-noborder\">
						The address book is used to store multiple customer addresses for shipping and billing purposes.
					</div>"
				),
				
				//Show addresses if the customer record exists, otherwise show information prompting record creation.
				($this->exists()) ?
				
				GridField::create(
					"Customer_AddressBook",
					"",
					$this->Addresses(),
					GridFieldConfig_RecordEditor::create()
				)
					
				:
				
				LiteralField::create($title = "AddressBookNotice",
					"<div class=\"literal-field field\">
						
						<div class=\"message notice\">
							<i class=\"fa fa-info-circle\"></i>
							This customer doesn't exist in the system yet. To be able to see addresses for this customer you must first 
							click create.
						</div>
						
					</div>"
				)
								
			)
		
		));
		
		//Customer Orders Tab
		$fields->addFieldsToTab("Root.Orders", array(
		
			HeaderField::create( ($this->exists()) ?
				$this->FirstName . " " . $this->Surname . "'s Orders" : 
				"Customer Orders"
			),
			CompositeField::create(
				
				LiteralField::create($title="CustomFieldsDescription",
					"<div class=\"literal-field literal-field-noborder\">
						If this customer has placed orders with your store you can see them below.
					</div>"
				),
				
				//Show orders if the customer record exists, otherwise show information prompting record creation.
				($this->exists()) ?
				
				GridField::create(
					"Customers_Orders",
					"",
					$this->Orders(),
					GridFieldConfig_RecordEditor::create()
				)
					
				:
				
				LiteralField::create($title = "OrdersNotice",
					"<div class=\"literal-field field\">
						
						<div class=\"message notice\">
							<i class=\"fa fa-info-circle\"></i>
							This customer doesn't exist in the system yet. To be able to see orders for this customer you must first 
							click create.
						</div>
						
					</div>"
				)
								
			)
		
		));
		
		//Update Store Credit Field
		$storecredit = NumericField::create("StoreCredit", "Store Credit (" . Product::getDefaultCurrency() . ")");
		$fields->replaceField("StoreCredit", $storecredit);
		
		//Remove Automatically Generated Address GridField
		$fields->removeFieldFromTab("Root", "Addresses");
		$fields->removeFieldFromTab("Root", "Permissions");
		$fields->removeFieldFromTab("Root.Orders", "Orders");
				
		return $fields;
		
	}
	
	/**
	 * Specifiy which form fields are required 
	 */
	public static function getCMSValidator() {
		return RequiredFields::create(array(
			"FirstName",
			"Surname",
			"Email"
		));
	}
	
	/**
	 * As $this->Title does not exist on this
	 * object, lets use the Customers FirstName and
	 * Surname instead. If they are from a company, 
	 * show that in brackets.
	 *
	 * @return String
	 */
	public function getTitle() {
		$customer = $this->FirstName . " " . $this->Surname;
		$company = $this->CompanyName;
		return ($company) ? $customer . " (". $company . ")" : $customer;
	}
	
	/**
	 * onBeforeDelete
	 * Remove other object records with a relationship to this record. 
	 * Includes removing the customer from the customers group, 
	 * removing all customer addresses, all orders etc.
	 */
	public function onBeforeDelete() { 
		
		parent::onBeforeDelete(); 
		
		/* 1 - Delete Order records if they exist */
		if($this->Orders()->exists()) { $this->Orders()->removeAll(); }
		
		/* 2 - Delete Address records if they exist */
		if($this->Addresses()->exists()) { $this->Addresses()->removeAll(); }
		
		/* 3 - Remove the customer from all membership groups. */
		DataObject::get("Group_Members", "(`MemberID`='" . $this->ID . "')")->removeAll();
		
	}
	
	/**
	 * Does this customer exist in the customers group? If not, add them.
	 */
	public function onAfterWrite() {

		parent::onAfterWrite();
		
		//Customer ID 
		$customer_id = $this->ID;
		
		//Get the ID of the customers group
		$group_id = new SQLQuery("id");
		$group_id = $group_id->setFrom("`group`")->addWhere("`Title`='Customers'")->execute()->value();
		
		//If the customer is not in the group, add them.
		$count = new SQLQuery("COUNT(*)");
		$count = $count->setFrom("`group_members`")->addWhere("(`GroupID`='$group_id') AND (`MemberID`='$customer_id')");
		$count = $count->execute()->value();
		if($count>0) { 
			return true; 
		} else {
			return (DB::query("INSERT INTO `group_members` (`GroupID`, `MemberID`) VALUES ($group_id, $customer_id)")) ? true : false;
		}
				
	}
	
	public function canView( $member = null ) { return ( Permission::check("SHOP_ACCESS_Customers") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("SHOP_ACCESS_Customers") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("SHOP_ACCESS_Customers") ) ? 1 : 0; }
	public function canDelete( $member = null ) { return ( Permission::check("SHOP_ACCESS_Customers") ) ? 1 : 0; }
	
	/**
	 * Add default records to database. This function is called whenever the
	 * database is built, after the database tables have all been created.
	 * 
	 * @uses DataExtension->requireDefaultRecords()
	 */
	public function requireDefaultRecords() {
		 
		/* Inherit Default Record Creation */
		parent::requireDefaultRecords();
		
		/**
		 * On /dev/build, create the Customers group - if it doesn't exist yet.
		 */
		$exists = Group::get()->filter(array(
			'Title' => 'Customers',
		))->exists();
		if( $exists ) {
			
			$n = Group::create();
			
			$n->Title = "Customers";
			$n->Description = "Security group for store customers";
			$n->Code = "customers";
			
			$n->write();
			
			unset($n);
			
			DB::alteration_message('Created default security group for customers', 'created');
			 		 
		}
		
	}
	
}