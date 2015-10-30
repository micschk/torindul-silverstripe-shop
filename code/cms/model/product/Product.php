<?php
/**
 * Model to store products.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Product extends DataObject {
	
	private static $singular_name = "Product";
	private static $plural_name = "Products";
	
	/** 
	 * Get the store settings 		
	*/
	public static function get_settings() {
		return StoreSettings::get_settings();
	}
	
	/** 
	 * Get the default store currency 
	 */
	public static function getDefaultCurrency() {
		$object = DataObject::get_one('StoreCurrency', '(ID = 1)');
		return $object->Symbol;
	}
	
	/**
	 * get_out_of_stock_filter
	 * This function returns part of a SQL WHERE statement to be used
	 * when using SELECT / get / get_one on the Product table. It ensures that products
	 * are only selected inline with the StoreSettings->Stock_ProductOutOfStock option. 
	 * i.e. if the admin has selected "Completely hide the product from my store"
	 * they are asking that the product is not shown anywhere should it be out
	 * of stock. 
	 * 
	 * This functionality is also entirely depenedent on StoreSettings->Stock_StockManagement
	 * being enabled. 
	 */
	public static function get_out_of_stock_filter() {
		
		/* Store Settings */
		$conf = StoreSettings::get_settings();
		
		/* 
		 * If Stock Management is disabled, there is no need to continue
		 * so let's return a statement which would always be true in order
		 * to ensure appending AND / OR statements continue to work.			
		*/ 
		if(!$conf->Stock_StockManagement) { 
			return "(1=1)"; 
		} else {
			
			/**
			 * StoreSettings->Stock_ProductOutOfStock returns data of type Int.
			 * Integer values 1 and 2 both represent hiding the product from view
			 * so in such a case return part of the SQL WHERE statement that would achieve this.
			 */
			if($conf->Stock_ProductOutOfStock==1 || $conf->Stock_ProductOutOfStock==2) {
				return "(StockLevel>'".$conf->Stock_OutOfStockThreshold."')";
			} else {
				return "(1=1)"; 
			}
			
		}
		
	}

	/**
	 * Database Fields 
	 */
	private static $db = array(
		"Title" => "Varchar",
		"URLSegment" => "Text",
		"RegularPrice" => "Decimal(10,2)",
		"SalePrice" => "Decimal(10,2)",
		"RetailPrice" => "Decimal(10,2)",
		"CostPrice" => "Decimal(10,2)",
		"Description" => "HTMLText",
		"TaxClass" => "Int",
		"Brand" => "Int",
		"Categories" => "Varchar",
		"SKU" => "Text",
		"Weight" => "Decimal(10,2)",
		"Length" => "Decimal(10,2)",
		"Width" => "Decimal(10,2)",
		"Height" => "Decimal(10,2)",
		"Content" => "HTMLText",
		"EnablePurchases" => "Boolean",
		"Visible" => "Boolean",
		"Featured" => "Boolean",
		"StockLevel" => "Int",
	);	
	
	/**
	 * Set defaults on record creation in the database 
	 */
	private static $defaults = array(
		"RegularPrice" => 0,
		"SalePrice" => 0,
		"RetailPrice" => 0,
		"CostPrice" => 0,
		"EnablePurchases" => 1,
		"Visible" => 1,
		"Featured" => 0,
		"StockLevel" => "0",
		"LowStockLevel" => 5,
		"TaxClass" => 1,
		"SKU" => "N/A",
	);
	
	/**
	 * Set has one relationships 
	 */
	public static $has_one = array();
	
	/**
	 * Set has many relationships 
	 */
	public static $has_many = array(
		"ProductCustomFields" => "Product_CustomFields",
		"ProductReviews" => "Product_Reviews"
	);
	
	/**
	 * Set many many relationships 
	 */
	public static $many_many = array(
		"Images" => "Product_Image"
	);
	
	/* Specify fields to display in GridFields */	
	public static $summary_fields = array(
		"getCMSThumbnail" => "Main Product Photo",
		"SKU" => "Product Code/SKU",
		"Title" => "Product Name",
		"getProductPrice" => "Price",
		"StockLevel" => "Current Stock Level"
	);
	
    /**
     * getCMSFields
	 * Construct the FieldList used in the CMS. To provide a
	 * smarter UI we don't use the scaffolding provided by
	 * parent::getCMSFields.
     * 
     * @return FieldList
     */
	public function getCMSFields() {
		
	    Requirements::css('torindul-silverstripe-shop/css/LeftAndMain.css');
		
		//Create the FieldList and push the Root TabSet on to it.
		$fields = FieldList::create(
			
			$root = TabSet::create(
				'Root',
				
				Tabset::create(
					"Product",
					
					//Details Tab
					Tab::create(
						"Details",
					
						//General
						HeaderField::create("General"),
						CompositeField::create(
							
							TextField::create(
								"Title",
								"Product Name"
							),
							
							NumericField::create(
								"RegularPrice",
								"Regular Price (" . $this->getDefaultCurrency() . ")"
							)
							->setRightTitle("The standard price for this product in your store."),
							
							FieldGroup::create(
								
								"Other Prices (" . $this->getDefaultCurrency() . ")",
								
								NumericField::create(
									"SalePrice",
									"Sale Price (optional)"
								)
								->setRightTitle("If entered, this price will replace the regular price."),
								
								NumericField::create(
									"RetailPrice",
									"Recommended Retail Price (optional)"
								)
								->setRightTitle("If entered, the retail price will be indicated on your site."),
								
								NumericField::create(
									"CostPrice",
									"Cost Price (optional)"
								)
								->setRightTitle("Not displayed on your site. For your reference only.")
								
							)
													
						),
						
						//Photos
						HeaderField::create("Photos (optional)"),
						CompositeField::create(
						
							($this->exists()) ?
							
								UploadField::create(
									"Images",
									"Product Photos"
								)
								->setAllowedFileCategories('image')
								->setAllowedMaxFileNumber(4)
								->setFolderName('product-photos')
							
							: 
							
								LiteralField::create($title="Product Photos",
									"<div class=\"literal-field field\">
									
										<label class=\"left\">".$title."</label>
										
										<div class=\"middleColumn\">
										
										<div class=\"message notice\">
											<i class=\"fa fa-info-circle\"></i>
											You will be able to add photos to this product after clicking create.
										</div>
		
										</div>
										
									</div>"
								)
							
						),
						
						//Product Description
						HeaderField::create("Product Description (optional)"),
						CompositeField::create(
							
							HtmlEditorField::create(
								"Description",
								""
							)
							
						)->addExtraClass("TorindulCompositeField TorindulCompositeField_16Margin"),
						
						//Stock Levels
						HeaderField::create("Stock Levels")
							->addExtraClass("TorindulHeaderField TorindulCompositeField_16Margin"),
						CompositeField::create(
							
							NumericField::create(
								"StockLevel",
								"Current Stock Level"
							)
							->setRightTitle("Total quantity currently in stock.")
							
						),
						
						//Availability
						HeaderField::create("Availability"),
						CompositeField::create(
							
							DropdownField::create(
								"EnablePurchases",
								"Enable Purchase?",
								array(
									"1" => "Yes, allow customers to purchase this product.",
									"0" => "No, do not allow customers to purchase this product."
								)
							),
							
							DropdownField::create(
								"Visible",
								"Visible to Customers?",
								array(
									"1" => "Yes, allow my customers to see this product in my store.",
									"0" => "No, hide this product from customers."
								)
							)
							
						),
						
						//Miscellaneous
						HeaderField::create("Miscellaneous"),
						CompositeField::create(
							
							//Only show product brands box if brands exist
							(DB::query("SELECT COUNT(*) FROM Product_Brands")->value() < 1) ? 
							
							LiteralField::create($title = "Brand (optional)",
								"<div class=\"literal-field field\">
								
									<label class=\"left\">".$title."</label>
									
									<div class=\"middleColumn\">
									
										<div class=\"message notice\">
											<i class=\"fa fa-info-circle\"></i>
											Before you can select a product brand you must first create one. 
											Please return to the previous screen and review the brands tab.
										</div>
	
									</div>
									
								</div>"
							)
							->setRightTitle($title)
								
							: 
							
							DropdownField::create(
								"Brand",
								"Brand (optional)",
								DataObject::get("Product_Brands", "", "Title ASC")->map("ID", "Title")
							)
							->setEmptyString('(Select one)')
							->setRightTitle("Specify this products brand, if applicable."),
							//END - Product Brands						
							
							//Only show product categories box if categories exist
							(DB::query("SELECT COUNT(*) FROM Product_Categories")->value()<1) ? 
							
							LiteralField::create($title = "Categories (optional)",
								"<div class=\"literal-field field\">
								
									<label class=\"left\">".$title."</label>
									
									<div class=\"middleColumn\">
									
										<div class=\"message notice\">
											<i class=\"fa fa-info-circle\"></i>
											Before you can select one or more product categories you must first create them. 
											Please return to the previous screen and review the categories tab.
										</div>
	
									</div>
									
								</div>"
							)
							->setRightTitle($title)
								
							: 
							
							CheckboxSetField::create(
								"Categories",
								"Categories (optional)",
							    DataObject::get("Product_Categories", "", "Title ASC")->map("ID", "Title")
							),
							//END - Product Categories
							
							DropdownField::create(
								"TaxClass",
								"Tax Class",
								DataObject::get("TaxClasses", "", "Title ASC")->map("ID", "Title")
							),
							
							TextField::create(
								"SKU",
								"SKU/Product Code (optional)"
							),
							
							OptionsetField::create(
								"Featured",
								"Featued Product",
								array(
									"0" => "No",
									"1" => "Yes"
								)
							)
							->setRightTitle("When set to yes this product will appear as a 'Featured Product' in your storefront."),
							
							FieldGroup::create($title = "Measurements",
							
								TextField::create(
									"Width",
									"Width (optional)"
								)
								->setRightTitle("in " . self::get_settings()->StoreSettings_ProductDimensions),
								
								TextField::create(
									"Height",
									"Height (optional)"
								)
								->setRightTitle("in " . self::get_settings()->StoreSettings_ProductDimensions),
								
								TextField::create(
									"Length",
									"Length (optional)"
								)
								->setRightTitle("in " . self::get_settings()->StoreSettings_ProductDimensions),
								
								TextField::create(
									"Weight",
									"Weight (optional)"
								)
								->setRightTitle("in " . self::get_settings()->StoreSettings_ProductWeight)
							
							)
													
						)
					
					),
					
					//Custom Fields Tab
					Tab::create(
						"CustomFields",
						
						//Custom Tabs
						HeaderField::create("Custom Fields"),
						CompositeField::create(
						
							LiteralField::create($title="CustomFieldsDescription",
								"<div class=\"literal-field literal-field-noborder\">
									Custom fields allow you to specify additional information that will appear on the product's page,
									such as a book's ISBN or a DVD's release date.
								</div>"
							),
						
							($this->exists()) ?
								
								GridField::create(
									"CustomFields",
									"",
									$this->ProductCustomFields()->sort("Title ASC"),
									GridFieldConfig_RecordEditor::create()
								)
								
							: 
							
							LiteralField::create($title = "CustomFieldsNotice",
								"<div class=\"literal-field field\">
									
									<div class=\"message notice\">
										<i class=\"fa fa-info-circle\"></i>
										You will be able to add custom fields to this product after clicking create.
									</div>
									
								</div>"
							)
							->setRightTitle($title)
								
						)
						
					),
					
					//Product Reviews
					Tab::create(
						"Reviews",
						
						//Approved Reviews
						HeaderField::create("Product Reviews"),
						CompositeField::create(
							
								LiteralField::create($title="CustomFieldsDescription",
									"<div class=\"literal-field literal-field-noborder\">
										According to Reevoo, reviews produce an average 18% uplift in sales and 50 or more reviews per 
										product can mean a 4.6% increase in conversion rates.
									</div>"
								),
							
							($this->checkReviewsEnabled()) ?
								
								($this->exists()) ?
									
								GridField::create(
									"ProductReviews",
									"",
									$this->ProductReviews()->sort("Date DESC")
								)
									
								: 
									
								LiteralField::create($title = "CustomFieldsNotice",
									"<div class=\"literal-field field\">
										
										<div class=\"message notice\">
											<i class=\"fa fa-info-circle\"></i>
											You will be able to manage reviews for this product after clicking create.
										</div>
										
									</div>"
								)
								->setRightTitle($title)
								
							:
							
								LiteralField::create($title = "ReviewsNotice",
									"<div class=\"literal-field field\">
										
										<div class=\"message warning\">
											<i class=\"fa fa-exclamation-circle\"></i>
											Product reviews are currently disabled for your store. You can enable them in store settings.
										</div>
										
									</div>"
								)
								->setRightTitle($title)
														
						)
						
					)
					
				)
				
			)
			
		);
		
		return $fields;
		
	}
	
	/**
	 * Set the required fields 
	 */
	public static function getCMSValidator() {
		return RequiredFields::create( 
			array(
				"Title",
				"RegularPrice",
				"TaxClass",			
			)
		);
	}
	
	/**
	 * COPIED FROM SITETREE
	 *
	 * Generate a URL segment based on the title provided.
	 * 
	 * @param string $title Product title
	 * @return string Generated url segment
	 */
	public function generateURLSegment($title){
		$filter = URLSegmentFilter::create();
		$t = $filter->filter($title);
		
		// Fallback to generic page name if path is empty (= no valid, convertable characters)
		if(!$t || $t == '-' || $t == '-1') $t = "page-$this->ID";
		
		// Hook for extensions
		$this->extend('updateURLSegment', $t, $title);
		
		// Check to see if URLSegment exists already, if it does, append -* where * is COUNT()+1
		$seg = new SQLQuery('COUNT(*)');
		$seg->setFrom( get_class($this) )->addWhere("`URLSegment` LIKE '%$t%'");
		$count = $seg->execute()->value();
		if($count > 0) { 
			$count++; 
			return $t . "-" . $count;
		} else {
			return $t;
		}
		
	}
		
	/**
	 * getCMSThumbnail
	 * If a photo has been uploaded, create a thumbnail
	 * for the GridField. Otherwise, display No Image. 
	 *
	 * @return Image|String
	 */
	public function getCMSThumbnail() {
		return ($this->Images()->First()->ID) ? $this->Images()->First()->setWidth('75') : "No Image";
	}
	
	/**
	 * getSKUProductCode 
	 * Product SKU to display in the GridField 
	 *
	 * @return String
	 */
	public function getSKUProductCode() {
		return ($this->SKU) ? $this->SKU : "Not Provided";
	}
	
	/**
	 * getProductURL
	 * 
	 * @return String The URL to the product page
	 */
	 public function getProductURL() {
		 return $this->link() . "/view/" . $this->URLSegment;		 
	 }
	 
	/**
	* conf
	* Use the passed parameter and return a value from StoreSettings.
	*
	* @param String $setting The item in StoreSettings to retreive the value for.
	* @return Int|Boolean|Varchar|Text
	*/
	public function conf($setting) {		  
		$conf = StoreSettings::get_settings();
		return $conf->$setting;
	}
	  
	/**
	 * getBrandName
	 * Get a Product Brand name from its ID.
	 *
	 * @return String
	 */
	public function getBrandName() {
		$brand = new SQLQuery("Title");
		$brand->setFrom("Product_Brands")->addWhere("`ID`='".$this->ID."'");
		return $brand->execute()->value();
	}
	
	/**
	 * getBrandURL
	 * Get the URL of a given brands page.
	 *
	 * @return String
	 */
	public function getBrandURL() {
		$brand = new SQLQuery("URLSegment");
		$brand->setFrom("Product_Brands")->addWhere("`ID`='".$this->ID."'");
		return $this->link() . "/brand/" . $brand->execute()->value();
	}
	
	/**
	 * getCategoryList
	 * Used the delimited Categories in the Category field and 
	 * return a loopable DataList of Category information for use
	 * in template files.
	 *
	 * @return DataList
	 */
	public function getCategoryList() {
		
		/* Delimited Categories */
		$cat = $this->Categories;
		
		/* Prepare SQLQuery */
		$query = new SQLQuery();
		$query->setFrom("Product_Categories")->setWhere("`ID` IN ($cat)");
		$result = $query->execute();
		
		/* Loop SQLQuery and produce DataList */
		$catlist = ArrayList::create();
		foreach($result as $row) {
			
			$catlist->push(array(
				"Title" => $row["Title"],
				"URL" => $this->link() . "/category/" . $row["URLSegment"]
			));
			
		}
		
		/* Return ArrayList */
		return $catlist;
		
	}
	
	/**
	 * stockLevelViewable
	 * Are we allowed to display the stock level?
	 *
	 * @return String
	 */
	public function stockLevelViewable() {
		
		$conf = StoreSettings::get_settings();
		switch($conf->Stock_StockLevelDisplay) {
			
			/* Always permitted to display stock levels */
			case 1:
				return true;
				break;
			
			/* Only display stock levels when low in stock */
			case 2:
				return ($this->StockLevel <= $conf->Stock_LowStockThreshold) ? true : false;
				break;
			
			/* Never display stock levels */
			case 3:
				return false;
				break;
			
		}

	}
	
	/**
	 * getProductPrice
	 * Price to display in the GridField 
	 * 
	 * @return float
	 */
	public function getProductPrice() {
		
		if($this->SalePrice!=0) {
			$text = LiteralField::create($title="Price","");
			$text->setValue(
				"<span class=\"GridFieldSaleOldPrice\">" . $this->getDefaultCurrency() . $this->RegularPrice . "</span> 
				<span class=\"GridFieldSaleNewPrice\">" . $this->getDefaultCurrency() . $this->SalePrice . "</span>"
			);	
			return $text;
		} else {
			$text = LiteralField::create($title="Price","");
			$text->setValue(
				$this->getDefaultCurrency() . $this->RegularPrice
			);
			return $text;
		}
		
	}
	
	/**
	 * getProductRetailPrice
	 * If entered, return the retail price.
	 *
	 * return Float|Boolean
	 */
	public function getProductRetailPrice() {
		return ($this->RetailPrice!=0) ? $this->getDefaultCurrency() . $this->RetailPrice : false;
	}
	
	/**
	 * getProductBrandLogo 
	 * If a brand has been selected, return the brands
	 * logo. So long as it has been uploaded.
	 *
	 * @param Int $custom_width The custom width to use for the brand logo.
	 * @param Int $custom_height The custom height to use for the brand logo. 
	 *
	 * @return Image|Boolean
	 */
	public function getProductBrandLogo($custom_width=null, $custom_height=null) {
		
		/* If product brand has been selected */
		if($this->Brand) {
			
			/* Select the Product_Brand in question */
			$brand = DataObject::get_by_id("Product_Brands", $this->Brand);
			
			/* If brand photo exists, return it. Otherwise return false. */
			if($brand->Logo()->ID) {
				return $brand->Logo()->setSize(
					($custom_width) ? $custom_width : 75,
					($custom_height) ? $custom_height : 75
				);
			} else {
				return false;
			}
		
		}
		
	}
	
	/**
	 * checkReviewsEnabled
	 * Check if reviews are enabled 
	 *
	 * @return Boolean
	 */
	public function checkReviewsEnabled() {
		
		$settings = self::get_settings();
		return ($settings->ProductReviewSettings_EnableReviews) ? true : false;
		
	}
	
	/**
	 * addToBasketForm
	 * Create the Form to add the current product to the basket 
	 * 
	 * @param Int $product_id The ID of the product to add.
	 * @return Form
	 */
	public function addToBasketForm( $product_id ) {
		/* Initiate our form. */
		$form = addToBasketForm::create($this, 'addtobasket', $product_id);
		
		/* Force POST and Strict Method Check */
		$form->setFormMethod('POST');
		$form->setStrictFormMethodCheck(true);
		
		/* Return the form */
		return $form;
	}
	
	/**
	 * Return the URL to the specific product handled by the Store_ProductController.
	 */
	public function link() {
		return Director::BaseURL() . DataObject::get_one("SiteTree", "ClassName='Store'")->URLSegment . "/product";
	}
	
	/** 
	 * onBeforeWrite
	 * Create SEO Friendly URLSegment
	 */
	protected function onBeforeWrite() {
		
		parent::onBeforeWrite();
		
		/* If no URLSegment is set, set one */
		if(!$this->URLSegment && $this->Title) {
			$this->URLSegment = $this->generateURLSegment( $this->Title );
		}
		
		/* If there is a URLSegment already and the Product Title has changed, update it. */
		else if( $this->isChanged('Title') ) {
			$this->URLSegment = $this->generateURLSegment( $this->Title );
		}
		
	}
	
	/**
	 * TODO - Dependency checks before deleting
	 */
	protected function onBeforeDelete() {
		parent::onBeforeDelete();
	}
	
	public function canView( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canEdit( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canCreate( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }
	public function canDelete( $member = null ) { return ( Permission::check("SHOP_ACCESS_Products") ) ? 1 : 0; }

}