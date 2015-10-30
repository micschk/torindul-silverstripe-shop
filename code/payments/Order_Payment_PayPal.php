<?php
/**
 * GATEWAY NAME: PayPal Payments Standard
 *
 * DESCRIPTION: This DataObject stores payment information related to PayPal transactions. It doesn't handle transactions themselves.
 * 
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop
 */
class Order_Payment_PayPal extends Order_Payment {

	/** 
	 * You can define database fields for your Gateway's Payment Information here, useful for storing transaction data.
	 * Your Gateway will inherit the fields applicable to all Gateways, refer to Order_Payment.php.
	 *
	 * The fields defined here are specific to the PayPal IPN Variables. Refer to
	 * https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/
	 * for more information on what each of them mean and how they're typically implemented.
	 */
	private static $db = array(
		"test_ipn" => "Int",
		"txn_id" => "Text",
		"parent_txn_id" => "Text",
		"txn_type" => "Text",
		"payer_id" => "Text",
		"payer_status" => "Text",
		"custom" => "Text",
		"payment_date" => "Text",
		"mc_fee" => "Text",
		"mc_gross" => "Text",
		"payment_status" => "Text",
		"pending_reason" => "Text",
		"payment_type" => "Text",
		"reason_code" => "Text",
		"protection_eligibility" => "Text",		
	);	
	
    /**
	 * Add fields to the CMS for this gateway.
     */
	public function getCMSFields() {
		
		//Fetch the fields from the Courier DataObject
		$fields = parent::getCMSFields();
		
		//Add new fields
		$fields->addFieldsToTab("Root.Main", array(
			
			HeaderField::create("PayPal Transaction Information"),
			CompositeField::create(
				
				ReadonlyField::create("custom", "Order Number"),
				ReadonlyField::create("payment_date", "Payment Date"),
				
				ReadonlyField::create("payment_status", "Payment Status")
				->setRightTitle("
					If this status is pending, see Pending Reason below. 
				"),
				
				ReadonlyField::create("payment_type", "Payment Type")
				->setRightTitle("
					For a explanation of the different payment types please see 'payment_type' at 
					<a href=\"https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/\">paypal.com</a>
				"),
				
				ReadonlyField::create("pending_reason", "Pending Reason")
				->setRightTitle("
					For a explanation of pending reasons please see 'pending_reason' at 
					<a href=\"https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/\">paypal.com</a>
				"),				
				
				ReadonlyField::create("reason_code", "Reason Code")
				->setRightTitle("
					This is set if Payment Status is Reversed, Refunded, Canceled_Reversal, or Denied. For an explanation of its
					meaning see 'reason_code' at 
					<a href=\"https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/\">paypal.com</a>
				"),				
				
				FieldGroup::create("Transaction Identifiers",
					ReadonlyField::create("txn_id", "<strong>Transaction ID</strong>"),
					ReadonlyField::create("parent_txn_id", "<strong>Parent Transaction ID</strong>")
				)->setRightTitle("
					<strong>Transaction ID</strong><br />
					The identifier for this specific transaction entry.
					
					<br /><br />
					
					<strong>Parent Transaction ID</strong><br />
					If provided, this value is the transaction identifier of the
					parent transaction. i.e. If this transaction is a refund, the
					value will be the transaction ID of the original purchase.
					<br />
				"),
				
				FieldGroup::create("Funds",
					ReadonlyField::create("mc_gross", "<strong>Gross Payment</strong>"),				
					ReadonlyField::create("mc_fee", "<strong>PayPal Fee</strong>")
				)->setRightTitle("
					<strong>Gross Payment</strong><br />
					Full amount of the customer's payment, before transaction fee is subtracted.
					
					<br /><br />
					
					<strong>PayPal Fee</strong><br />
					Transaction fee associated with the payment.
				")
				
				
			
			)
			
		));		
		
		return $fields;
		
	}
	
	/**
	 * Set the required form fields for this payment record, taking those
	 * defined in Payment in to account.
	 */
	public static function getCMSValidator() {
		
		//Get required fields from Courier DataObject.
		$parent_required = (is_array(parent::getCMSValidator())) ? parent::getCMSValidator() : array();
		
		//Specify our own required fields.
		$required = array("txn_id");
		
		//Return the required fields.
		return RequiredFields::create( array_merge( $parent_required, $required ) );
		
	}

}