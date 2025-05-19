<?php
/**
 * This file contains the definition of the Disposable_Email_Blocker_Contact_Form_7_Public class, which
 * is used to load the plugin's public-facing functionality.
 *
 * @package       Disposable_Email_Blocker_Contact_Form_7
 * @subpackage    Disposable_Email_Blocker_Contact_Form_7/public
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version and other methods.
 *
 * @since    2.0.0
 */
class Disposable_Email_Blocker_Contact_Form_7_Public {
	/**
	 * The ID of this plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 * @var       string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 * @var       string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     string $plugin_name The name of the plugin.
	 * @param     string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Adds a custom error message for disposable emails in Contact Form 7.
	 *
	 * This function adds a custom error message to the Contact Form 7 message array,
	 * indicating that a disposable or temporary email address was detected.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     array $message The existing Contact Form 7 message array.
	 * @return    array          The modified Contact Form 7 message array.
	 */
	public function messages( $message ) {
		$message['disposable_emails_found'] = array(
			'description' => __( 'Email was disposable/temporary', 'disposable-email-blocker-contact-form-7' ),
			'default'     => __( 'Disposable/Temporary emails are not allowed! Please use a non temporary email', 'disposable-email-blocker-contact-form-7' ),
		);

		return $message;
	}

	/**
	 * Blocks disposable emails submitted via Contact Form 7.
	 *
	 * This function checks if the submitted email address belongs to a disposable
	 * email domain and invalidates the form submission if it does.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     WPCF7_Validation $result The current validation result.
	 * @param     WPCF7_FormTag    $tag    The form tag object.
	 * @return    WPCF7_Validation         The modified validation result.
	 */
	public function validate_email( $result, $tag ) {
		global $wpdb;

		// first check if blokcing disposable emails are enabled.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['_wpcf7'] ) && ! empty( $_POST['_wpcf7'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( get_post_meta( intval( $_POST['_wpcf7'] ), 'debcf7_enabled', true ) !== 'on' ) {
				return $result;
			}
		}

		$name = $tag->name;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST[ $name ] ) && filter_var( sanitize_email( wp_unslash( $_POST[ $name ] ) ), FILTER_VALIDATE_EMAIL ) ) {
			// split on @ and return last value of array (the domain).
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$domain     = explode( '@', sanitize_email( wp_unslash( $_POST[ $name ] ) ) );
			$domain     = array_pop( $domain );
			$found      = false;
			$table_name = $wpdb->prefix . DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_TABLE_NAME;
			$txt_file   = DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_PATH . '/public/data/domains.txt';

			// Check if the table exists.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table_name
				)
			);

			if ( $table_exists ) {
				// Look for the domain in the database.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$found = (bool) $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT COUNT(*) FROM {$table_name} WHERE domain = %s",
						$domain
					)
				);
			} elseif ( file_exists( $txt_file ) ) { // If not found the table and file exists, fall back to txt.
				global $wp_filesystem;

				if ( ! $wp_filesystem ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}

				WP_Filesystem();

				// Get domains list from the txt file.
				$txt_file_content   = $wp_filesystem->get_contents( $txt_file );
				$disposable_domains = explode( "\n", $txt_file_content );

				if ( is_array( $disposable_domains ) && in_array( $domain, $disposable_domains, true ) ) {
					$found = true;
				}
			}

			// If found in DB or txt, invalidate the result.
			if ( $found ) {
				$result->invalidate( $tag, wpcf7_get_message( 'disposable_emails_found' ) );
			}
		}

		return $result;
	}
}
