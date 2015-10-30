<?php
/**
 * Order Login Form.
 * 
 * Extension of MemberLoginForm created to override $LoginLink on dologin() to
 * point to the correct action on Store_OrderController.
 *
 * @author George Botley - Torindul Business Solutions
 * @package torindul-silverstripe-shop 
 */
class OrderLoginForm extends MemberLoginForm {

	/**
	 * Login form handler method
	 *
	 * This method is called when the user clicks on "Log in"
	 *
	 * @param array $data Submitted data
	 */
	public function dologin($data) {
		if($this->performLogin($data)) {
			$this->logInUserAndRedirect($data);
		} else {
			if(array_key_exists('Email', $data)){
				Session::set('SessionForms.MemberLoginForm.Email', $data['Email']);
				Session::set('SessionForms.MemberLoginForm.Remember', isset($data['Remember']));
			}

			if(isset($_REQUEST['BackURL'])) $backURL = $_REQUEST['BackURL'];
			else $backURL = null;

			if($backURL) Session::set('BackURL', $backURL);

			// Show the right tab on failed login
			$loginLink = Store_OrderController::get_link() . "/place";
			if($backURL) $loginLink .= '?BackURL=' . urlencode($backURL);
			$this->controller->redirect($loginLink . '#' . $this->FormName() .'_tab');
		}
	}
		
}