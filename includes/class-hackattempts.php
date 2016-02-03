<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://hackattempts.zengo.eu
 * @since      1.1
 *
 * @package    Hackattempts
 * @subpackage Hackattempts/includes
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
 * @since      1.1
 * @package    Hackattempts
 * @subpackage Hackattempts/includes
 * @author     David Perlusz <perlusz.david@zengo.eu>
 */
class Hackattempts {

    public static $attempts_dir = 'hackattempts';

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.1
     * @access   protected
     * @var      Hackattempts_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.1
     * @access   protected
     * @var      string    $Hackattempts    The string used to uniquely identify this plugin.
     */
    protected $Hackattempts;

    /**
     * The current version of the plugin.
     *
     * @since    1.1
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.1
     */
    public function __construct() {

        $this->Hackattempts = 'hackattempts';
        $this->version = '1.4';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Hackattempts_Loader. Orchestrates the hooks of the plugin.
     * - Hackattempts_i18n. Defines internationalization functionality.
     * - Hackattempts_Admin. Defines all hooks for the admin area.
     * - Hackattempts_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.1
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-hackattempts-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-hackattempts-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-hackattempts-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-hackattempts-public.php';

        $this->loader = new Hackattempts_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Hackattempts_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.1
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Hackattempts_i18n();
        $plugin_i18n->set_domain($this->get_Hackattempts());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.1
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Hackattempts_Admin($this->get_Hackattempts(), $this->get_version());

        //Enqueue the necessery css and js files
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        //Register the admin menu element
        $this->loader->add_action('admin_menu', $plugin_admin, 'admin_menu_link');

        //Hook to wp-login for make a cookie to check the user is logged in or not
        $this->loader->add_action('wp_login', $plugin_admin, 'create_login_coockie', 10, 2);

        //Reinit our plugin if it is needed
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'hackattempts_inline_check');

        //Check database update is needed?
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'hackattempts_db_check');
        
        //Change wp-lgin to custome url
        $this->loader->add_action('init', $plugin_admin, 'hackattempts_login');

        //Clear the redirect urls
        $this->loader->add_filter('site_url', $plugin_admin, 'hackattempts_change_login_url', 9999, 4);
        $this->loader->add_filter('network_site_url', $plugin_admin, 'hackattempts_change_login_url', 9999, 3);
        $this->loader->add_filter('wp_redirect', $plugin_admin, 'hackattempts_redirect', 9999, 2);

        //Disable access to wp-login
        $this->loader->add_action('init', $plugin_admin, 'disable_login_url');
        $this->loader->add_filter('wp_redirect', $plugin_admin, 'hackattempts_no_redirect', 10, 2);

        //Admin toolbar
        //$this->loader->add_action('admin_bar_menu', $plugin_admin, 'toolbar_element', 999);

        //Handle AJAX Requests
        $this->loader->add_action('wp_ajax_deleteFile', $plugin_admin, 'delete_file');
        $this->loader->add_action('wp_ajax_nopriv_deleteFile', $plugin_admin, 'delete_file');

        $this->loader->add_action('wp_ajax_removeSecurity', $plugin_admin, 'remove_security_from_file');
        $this->loader->add_action('wp_ajax_nopriv_removeSecurity', $plugin_admin, 'remove_security_from_file');

        $this->loader->add_action('wp_ajax_addSecurity', $plugin_admin, 'add_security_to_file');
        $this->loader->add_action('wp_ajax_nopriv_addSecurity', $plugin_admin, 'add_security_to_file');

        $this->loader->add_action('wp_ajax_saveSettings', $plugin_admin, 'save_settings');
        $this->loader->add_action('wp_ajax_nopriv_saveSettings', $plugin_admin, 'save_settings');

        $this->loader->add_action('wp_ajax_addBlock', $plugin_admin, 'block_ip');
        $this->loader->add_action('wp_ajax_nopriv_addBlock', $plugin_admin, 'block_ip');
        

        $this->loader->add_action('wp_ajax_addWatch', $plugin_admin, 'add_watch_to_file');
        $this->loader->add_action('wp_ajax_nopriv_addWatch', $plugin_admin, 'add_watch_to_file');        
        
        $this->loader->add_action('wp_ajax_removeWatch', $plugin_admin, 'remove_watch_from_file');
        $this->loader->add_action('wp_ajax_nopriv_removeWatch', $plugin_admin, 'remove_watch_from_file');        
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.1
     * @modified 1.4
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Hackattempts_Public($this->get_Hackattempts(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        //Register the scheduled tasks
        $this->loader->add_action('hackattempts_cleanup', $plugin_public, 'delete_old_files');
        $this->loader->add_action('hackattempts_email', $plugin_public, 'send_emails_to_admin');
        
        //Register the check file mod task
        $this->loader->add_action('hackattempts_check_file_mod', $plugin_public, 'check_files');      
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.1
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.1
     * @return    string    The name of the plugin.
     */
    public function get_Hackattempts() {
        return $this->Hackattempts;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.1
     * @return    Hackattempts_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.1
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
