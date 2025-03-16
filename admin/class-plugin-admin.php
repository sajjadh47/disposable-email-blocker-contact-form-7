<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version and other methods.
 *
 * @package    Disposable_Email_Blocker_Contact_Form_7
 * @subpackage Disposable_Email_Blocker_Contact_Form_7/admin
 * @author     Sajjad Hossain Sagor <sagorh672@gmail.com>
 */
class Disposable_Email_Blocker_Contact_Form_7_Admin
{
	/**
	 * The ID of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $plugin_name     The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $version    		The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @param    string    $plugin_name     The name of this plugin.
	 * @param    string    $version    		The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
	{
		$this->plugin_name 	= $plugin_name;
		
		$this->version 		= $version;
	}

	/**
	 * Displays admin notices in the admin area.
	 *
	 * This function checks if the required plugin is active.
	 * If not, it displays a warning notice and deactivates the current plugin.
	 *
	 * @since    2.0.0
	 * @access   public
	 */
	public function admin_notices()
	{
		// Check if required plugin is active.
		if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) )
		{
			echo '<div class="notice notice-warning is-dismissible">';
			
				printf(
					wp_kses_post(
					__( '<p>Disposable Email Blocker - Contact Form 7 requires <a href="%s">Contact Form 7</a> plugin to be active!</p>', 'disposable-email-blocker-contact-form-7' )
					),
					esc_url( 'https://wordpress.org/plugins/contact-form-7/' )
				);
			
			echo '</div>';

			// Deactivate the plugin
			deactivate_plugins( DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_BASENAME );
		}
	}

	/**
	 * Adds a checkbox to the Contact Form 7 admin misc pub section to enable/disable disposable email blocking.
	 *
	 * This function adds a checkbox to the Contact Form 7 admin interface, allowing users
	 * to enable or disable the blocking of disposable or temporary email addresses for
	 * a specific contact form.
	 *
	 * @since   2.0.0
	 * @access  public
	 * @param 	int $post_id The ID of the current contact form post.
	 * @return 	void
	 */
	public function admin_misc_pub_section( $post_id )
	{
		$enabled = get_post_meta( $post_id, 'debcf7_enabled', true );
		
		echo '
		<p style="padding: 0 1em;">
			<label for="debcf7_enable">
				<input type="checkbox" id="debcf7_enable" name="debcf7_enable" '. checked( $enabled, 'on', false ) .'>
				'. __( 'Block Disposable/Temporary Emails', "disposable-email-blocker-contact-form-7" ) .'
			</label>
		</p>';
	}

	/**
	 * Saves the disposable email checkup switch setting for a Contact Form 7 form.
	 *
	 * This function is triggered when a Contact Form 7 form is saved. It checks user
	 * permissions and updates the post meta to reflect whether disposable email
	 * blocking is enabled for that specific form.
	 *
	 * @since   2.0.0
	 * @access  public
	 * @param 	WPCF7_ContactForm $contact_form The Contact Form 7 object.
	 * @param 	array             $args         An array of arguments passed to the save action.
	 * @param 	string            $action       The action being performed (e.g., 'save').
	 * @return 	void
	 */
	public function save_contact_form( $contact_form, $args, $action )
	{	
		if ( 'save' == $action && isset( $args['post_ID'] ) )
		{
			$debcf7_enable 	= sanitize_text_field( $args['debcf7_enable'] );
		
			$post_id 		= intval( $args['post_ID'] );

			if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) )
			{	
				if ( isset( $_POST['debcf7_enable'] ) )
				{	
					update_post_meta( $post_id, 'debcf7_enabled', 'on' );
				}
				else
				{
					update_post_meta( $post_id, 'debcf7_enabled', 'off' );
				}
			}
		}
	}

	/**
	 * Handles plugin table creation task.
	 *
	 * This function is called when the plugin is activated using cron. It creates the
	 * necessary database table to store disposable email domains and populates it
	 * with data from a txt file.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @return   void
	 */
	public function create_disposable_email_domains_table()
	{
		global $wpdb;
		
		$table_name 				= $wpdb->prefix . DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_TABLE_NAME;

		$txt_file 					= DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_PATH . '/admin/data/domains.txt';

		if ( ! file_exists( $txt_file ) ) return;
		
		// Create table if it doesn't exist
		$charset_collate 			= $wpdb->get_charset_collate();
		
		$sql 						= 
		"CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			domain VARCHAR(255) NOT NULL UNIQUE
		) $charset_collate;";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		dbDelta( $sql );

		// Get domains list from txt file
		$disposable_domains 		= explode( "\n", file_get_contents( $txt_file ) );

		if ( ! empty( $disposable_domains ) && is_array( $disposable_domains ) )
		{
			foreach ( $disposable_domains as $domain )
			{
				// Insert or update domains
				$wpdb->replace(
					$table_name,
					[ 'domain' => sanitize_text_field( $domain ) ],
					[ '%s' ]
				);
			}
		}
	}
}
