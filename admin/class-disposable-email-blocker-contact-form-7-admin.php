<?php
/**
 * This file contains the definition of the Disposable_Email_Blocker_Contact_Form_7_Admin class, which
 * is used to load the plugin's admin-specific functionality.
 *
 * @package       Disposable_Email_Blocker_Contact_Form_7
 * @subpackage    Disposable_Email_Blocker_Contact_Form_7/admin
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version and other methods.
 *
 * @since    2.0.0
 */
class Disposable_Email_Blocker_Contact_Form_7_Admin {
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
	 * @param     string $plugin_name The name of this plugin.
	 * @param     string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Adds a settings link to the plugin's action links on the plugin list table.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     array $links The existing array of plugin action links.
	 * @return    array $links The updated array of plugin action links, including the settings link.
	 */
	public function add_plugin_action_links( $links ) {
		$links[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( '/admin.php?page=wpcf7' ) ), __( 'Settings', 'disposable-email-blocker-contact-form-7' ) );

		return $links;
	}

	/**
	 * Displays admin notices in the admin area.
	 *
	 * This function checks if the required plugin is active.
	 * If not, it displays a warning notice and deactivates the current plugin.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function admin_notices() {
		// Check if required plugin is active.
		if ( ! class_exists( 'WPCF7', false ) ) {
			sprintf(
				'<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a> %s</p></div>',
				__( 'Disposable Email Blocker - Contact Form 7 requires', 'disposable-email-blocker-contact-form-7' ),
				esc_url( 'https://wordpress.org/plugins/contact-form-7/' ),
				__( 'Contact Form 7', 'disposable-email-blocker-contact-form-7' ),
				__( 'plugin to be active!', 'disposable-email-blocker-contact-form-7' ),
			);

			// Deactivate the plugin.
			deactivate_plugins( DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_BASENAME );
		}

		// Get current db version and if needed update domains list.
		$current_db_version = get_option( 'debcf7_db_version', false );

		if ( DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_DB_VERSION !== $current_db_version ) {
			if ( ! wp_next_scheduled( 'cf7_create_disposable_email_domains_table' ) ) {
				wp_schedule_single_event( time() + 10, 'cf7_create_disposable_email_domains_table' );
			}

			update_option( 'debcf7_db_version', DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_DB_VERSION );
		}
	}

	/**
	 * Adds a checkbox to the Contact Form 7 admin misc pub section to enable/disable disposable email blocking.
	 *
	 * This function adds a checkbox to the Contact Form 7 admin interface, allowing users
	 * to enable or disable the blocking of disposable or temporary email addresses for
	 * a specific contact form.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     int $post_id The ID of the current contact form post.
	 * @return    void
	 */
	public function admin_misc_pub_section( $post_id ) {
		$enabled = get_post_meta( $post_id, 'debcf7_enabled', true );

		echo '
		<p style="padding: 0 1em;">
			<label for="debcf7_enable">
				<input type="checkbox" id="debcf7_enable" name="debcf7_enable" ' . checked( $enabled, 'on', false ) . '>
				' . esc_html__( 'Block Disposable/Temporary Emails', 'disposable-email-blocker-contact-form-7' ) . '
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
	 * @since     2.0.0
	 * @access    public
	 * @param     WPCF7_ContactForm $contact_form The Contact Form 7 object.
	 * @param     array             $args         An array of arguments passed to the save action.
	 * @param     string            $action       The action being performed (e.g., 'save').
	 * @return    void
	 */
	public function save_contact_form( $contact_form, $args, $action ) {
		if ( 'save' === $action && isset( $args['post_ID'] ) ) {
			$debcf7_enable = sanitize_text_field( $args['debcf7_enable'] );
			$post_id       = intval( $args['post_ID'] );

			// phpcs:ignore WordPress.WP.Capabilities.Unknown
			if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['debcf7_enable'] ) ) {
					update_post_meta( $post_id, 'debcf7_enabled', 'on' );
				} else {
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
	 * @since     2.0.0
	 * @access    public
	 * @return    void
	 */
	public function create_disposable_email_domains_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_TABLE_NAME;
		$txt_file   = DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_PATH . '/public/data/domains.txt';

		if ( ! file_exists( $txt_file ) ) {
			return;
		}

		// Create table if it doesn't exist.
		$charset_collate = $wpdb->get_charset_collate();

		$sql =
		"CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			domain VARCHAR(255) NOT NULL UNIQUE
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		// Get domains list from txt file.
		$txt_file_content   = $wp_filesystem->get_contents( $txt_file );
		$disposable_domains = explode( "\n", $txt_file_content );

		if ( ! empty( $disposable_domains ) && is_array( $disposable_domains ) ) {
			foreach ( $disposable_domains as $domain ) {
				// Insert or update domains.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->replace(
					$table_name,
					array( 'domain' => sanitize_text_field( $domain ) ),
					array( '%s' )
				);
			}
		}
	}
}
