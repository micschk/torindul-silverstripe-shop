<?php
/**
 * Extension of Image to enable $belongs_many_many
 *
 */
class Product_Image extends Image {

	/**
	 * Set belongs many many relationships 
	 */
	public static $belongs_many_many = array(
		"Products" => "Product"
	);
	
	/**
	 * getImage
	 * If a photo has been uploaded, return the main photo
	 * for this product at the dimensions specified in the
	 * StoreSettings. Otherwise, display No Image. 
	 *
	 * @param Int $custom_width A custom width in pixels. Used as an override to StoreSettings.
	 * @param Int $custom_height A custom height in pixels. Used as an overide to StoreSettings.
	 *
	 * @return Image|String
	 */
	public function getImage($custom_width=null,$custom_height=null) {
		if($this->ID) {
			return $this->SetSize(
				($custom_width) ? $custom_width : StoreSettings::get_settings()->DisplaySettings_ProductPagePhotoWidth,
				($custom_height) ? $custom_height : StoreSettings::get_settings()->DisplaySettings_ProductPagePhotoHeight
			);
		} else {
			return "No Image";
		}
	}
	
	/**
	 * getImageThumbnail
	 * If a photo has been uploaded, return the main photo
	 * for this product at the thumbnail dimensions specified in
	 * the StoreSettings. Otherwise, return null. 
	 *
	 * @param Int $custom_width A custom width in pixels. Used as an override to StoreSettings.
	 * @param Int $custom_height A custom height in pixels. Used as an overide to StoreSettings.
	 *
	 * @return Image|String
	 */
	public function getImageThumbnail($custom_width=null,$custom_height=null) {
		if($this->ID) {
			return $this->SetSize(
				($custom_width) ? $custom_width : StoreSettings::get_settings()->DisplaySettings_ProductThumbnailPhotoWidth,
				($custom_height) ? $custom_height : StoreSettings::get_settings()->DisplaySettings_ProductThumbnailPhotoHeight
			);
		} else {
			return null;
		}
	}
	
	/**
	 * getImageEnlarged
	 * If a photo has been uploaded, return the main photo
	 * for this product at the enlarged dimensions specified in
	 * the StoreSettings. Otherwise, return null. 
	 *
	 * @param Int $custom_width A custom width in pixels. Used as an override to StoreSettings.
	 * @param Int $custom_height A custom height in pixels. Used as an overide to StoreSettings.
	 *
	 * @return Image|String
	 */
	public function getImageEnlarged($custom_width=null,$custom_height=null) {
		if($this->ID) {
			return $this->SetSize(
				($custom_width) ? $custom_width : StoreSettings::get_settings()->DisplaySettings_ProductEnlargedPhotoWidth,
				($custom_height) ? $custom_height : StoreSettings::get_settings()->DisplaySettings_ProductEnlargedPhotoHeight
			);
		} else {
			return null;
		}
	}
	
}