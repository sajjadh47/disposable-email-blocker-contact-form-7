<?php
/**
 * This file contains the definition of the Disposable_Email_Blocker_Contact_Form_7 class, which
 * is used to begin the plugin's functionality.
 *
 * @package       Disposable_Email_Blocker_Contact_Form_7
 * @subpackage    Disposable_Email_Blocker_Contact_Form_7/includes
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * The core plugin class.
 *
 * This is used to define admin-specific hooks and public-facing hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since    2.0.0
 */
class Disposable_Email_Blocker_Contact_Form_7 {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since     2.0.0
	 * @access    protected
	 * @var       Disposable_Email_Blocker_Contact_Form_7_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since     2.0.0
	 * @access    protected
	 * @var       string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since     2.0.0
	 * @access    protected
	 * @var       string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function __construct() {
		$this->version     = defined( 'DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_VERSION' ) ? DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_VERSION : '1.0.0';
		$this->plugin_name = 'disposable-email-blocker-contact-form-7';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Disposable_Email_Blocker_Contact_Form_7_Loader. Orchestrates the hooks of the plugin.
	 * - Disposable_Email_Blocker_Contact_Form_7_Admin.  Defines all hooks for the admin area.
	 * - Disposable_Email_Blocker_Contact_Form_7_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since     2.0.0
	 * @access    private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_PATH . 'includes/class-disposable-email-blocker-contact-form-7-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_PATH . 'admin/class-disposable-email-blocker-contact-form-7-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_PATH . 'public/class-disposable-email-blocker-contact-form-7-public.php';

		$this->loader = new Disposable_Email_Blocker_Contact_Form_7_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Disposable_Email_Blocker_Contact_Form_7_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'plugin_action_links_' . DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_BASENAME, $plugin_admin, 'add_plugin_action_links' );

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );

		$this->loader->add_action( 'wpcf7_admin_misc_pub_section', $plugin_admin, 'admin_misc_pub_section' );
		$this->loader->add_action( 'wpcf7_save_contact_form', $plugin_admin, 'save_contact_form', 10, 3 );

		$this->loader->add_action( 'cf7_create_disposable_email_domains_table', $plugin_admin, 'create_disposable_email_domains_table' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 */
	private function define_public_hooks() {
		$plugin_public = new Disposable_Email_Blocker_Contact_Form_7_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'wpcf7_messages', $plugin_public, 'messages' );
		$this->loader->add_filter( 'wpcf7_validate_email', $plugin_public, 'validate_email', 99, 2 );
		$this->loader->add_filter( 'wpcf7_validate_email*', $plugin_public, 'validate_email', 99, 2 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    Disposable_Email_Blocker_Contact_Form_7_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
