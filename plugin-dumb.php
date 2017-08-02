<?php
/**
 * @package DumbPlugin
 * @license GPL-2.0+
 * @link    https://github.com/whatadewitt/dumb-plugin
 * @version 0.0.1
 *
 * Plugin Name: Dumb Plugin
 * Description: A Dumb Plugin that does something dumb
 * Version: 0.0.2
 * Author: Luke DeWitt
 * License: GPLv2 or later
 * Text Domain: wad-dumb
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * The following constant is used to define a constant for this plugin to make it
 * easier to provide cache-busting functionality on loading stylesheets
 * and JavaScript.
 *
 * After you've defined these constants, do a find/replace on the constants
 * used throughout the rest of this file.
 */
if ( ! defined( 'DUMB_PLUGIN_VERSION' ) ) {
	define( 'DUMB_PLUGIN_VERSION', '0.0.2' );
}

require_once( plugin_dir_path( __FILE__ ) . 'class-dumb-plugin.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'DumbPlugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DumbPlugin', 'deactivate' ) );

DumbPlugin::get_instance();