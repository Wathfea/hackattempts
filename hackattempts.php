<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://hackattempts.zengo.eu
 * @since             1.1.0
 * @package           Hackattempts
 *
 * @wordpress-plugin
 * Plugin Name:       Hackattempts
 * Plugin URI:        http://hackattempts.zengo.eu
 * Description:       The plugin is used for bann not wanted brute force requests via the xmlrpc file
 * Version:           1.4
 * Author:            David Perlusz @ Zengo Ltd.
 * Author URI:        http://www.zengo.eu
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hackattempts
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_hackattempts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hackattempts-activator.php';
	$activator = new Hackattempts_Activator;
	$activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_hackattempts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hackattempts-deactivator.php';
	$deactivator = new Hackattempts_Deactivator;
	$deactivator->deactivate();
}

register_activation_hook( __FILE__, 'activate_hackattempts' );
register_deactivation_hook( __FILE__, 'deactivate_hackattempts' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-hackattempts.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.1
 */
function run_hackattempts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-zpm-client.php';
	$client = new ZPM_Client('http://hackadmin.getonline.ie/api/zpm/v1' , 'hackattempts' , 'hackattempts' , 'Hackattempts');
	$client->build_structure();

	$plugin = new Hackattempts();
	$plugin->run();

}
run_hackattempts();
