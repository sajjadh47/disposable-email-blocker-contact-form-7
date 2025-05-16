<?php
/**
 * This file contains the definition of the Disposable_Email_Blocker_Contact_Form_7_I18n class, which
 * is used to load the plugin's internationalization.
 *
 * @package       Disposable_Email_Blocker_Contact_Form_7
 * @subpackage    Disposable_Email_Blocker_Contact_Form_7/includes
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since    2.0.0
 */
class Disposable_Email_Blocker_Contact_Form_7_I18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'disposable-email-blocker-contact-form-7',
			false,
			dirname( DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
