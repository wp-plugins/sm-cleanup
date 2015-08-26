<?php
/**
 * Plugin Name: SM Cleanup
 * Plugin URI: https://wordpress.org/plugins/sm-cleanup
 * Version: 1.2
 * Author: Simon Jan
 * Author URI: https://profiles.wordpress.org/simonjan
 * Description: A simple way optimize your web, clean your from code, convert style attributes to class attributes, use css external css instead of inline style, reuse your style
 * License: GPLv3 or later
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SMCL_V', '1.2' );

define( 'SMCL_RQ_WP_V', '3.4' );

define( 'SMCL_PLUGIN', __FILE__ );

define( 'SMCL_PLUGIN_DIR', plugin_dir_path( ( SMCL_PLUGIN ) ) );

define( 'SMCL_PLUGIN_URL', plugin_dir_url( SMCL_PLUGIN ) );

define( 'SMCL_PLUGIN_ASSETS', SMCL_PLUGIN_URL . 'assets/');

define( 'SMCL_PLUGIN_LIBS', SMCL_PLUGIN_URL . 'libs/');

define( 'SMCL_ADMIN_URL', admin_url('admin.php?page=sm-cleanup') );

/**
 * Called when plugin active/deactive
 */
if( ! class_exists( 'SMCL_Activator ') ){
	require_once( SMCL_PLUGIN_DIR .'class/class.smcl_activator.php' );
	$smcl = new SMCL_Activator();
	// // activator
	register_activation_hook( SMCL_PLUGIN, array( &$smcl, 'smActivate') );

	// deactivator
	register_deactivation_hook( SMCL_PLUGIN, array( &$smcl, 'smDeActive') );
}

/**
 * Define internationalization, dashboard-specific hooks, and public-facing site hooks.
 */
require_once( SMCL_PLUGIN_DIR . 'class/class.smcleanup.php');
