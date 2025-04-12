<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Smart_Bulk_Password_Reset
 * @subpackage Smart_Bulk_Password_Reset/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Smart_Bulk_Password_Reset
 * @subpackage Smart_Bulk_Password_Reset/includes
 * @author     Cline <your-email@example.com>
 */
class Smart_Bulk_Password_Reset {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Smart_Bulk_Password_Reset_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area
	 * and the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SBPR_VERSION' ) ) {
			$this->version = SBPR_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'smart-bulk-password-reset';

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Smart_Bulk_Password_Reset_Loader. Orchestrates the hooks of the plugin.
	 * - Smart_Bulk_Password_Reset_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smart-bulk-password-reset-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-smart-bulk-password-reset-admin.php';

		$this->loader = new Smart_Bulk_Password_Reset_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Smart_Bulk_Password_Reset_Admin( $this->get_plugin_name(), $this->get_version() );

		// Add menu item
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

		// Add Settings link to plugin page
        $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
        $this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );

		// Enqueue admin scripts and styles
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Handle AJAX request for fetching users
		$this->loader->add_action( 'wp_ajax_sbpr_get_users_by_role', $plugin_admin, 'ajax_get_users_by_role' );

		// Handle form submission for password reset
		$this->loader->add_action( 'admin_post_sbpr_send_reset_emails', $plugin_admin, 'handle_send_reset_emails' );

        // Handle AJAX request for sending test email
        $this->loader->add_action( 'wp_ajax_sbpr_send_test_email', $plugin_admin, 'ajax_send_test_email' );

        // Handle AJAX requests for template management
        $this->loader->add_action( 'wp_ajax_sbpr_load_template', $plugin_admin, 'ajax_load_template' );
        $this->loader->add_action( 'wp_ajax_sbpr_save_template', $plugin_admin, 'ajax_save_template' );
        $this->loader->add_action( 'wp_ajax_sbpr_update_template', $plugin_admin, 'ajax_update_template' );
        $this->loader->add_action( 'wp_ajax_sbpr_delete_template', $plugin_admin, 'ajax_delete_template' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Smart_Bulk_Password_Reset_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
