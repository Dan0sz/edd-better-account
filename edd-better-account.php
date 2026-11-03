<?php
/**
 * Plugin Name: Easy Digital Downloads - Better Account
 * Description: Adds tabs to the account pages (Orders, License Keys, Subscriptions, etc.), which you manage as a menu under Appearance > Menus.
 * Version: 1.1.1
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev
 * GitHub Plugin URI: Dan0sz/edd-better-account
 * Primary Branch: master
 * License: MIT
 * Text Domain: edd-better-account
 */

require_once __DIR__ . '/vendor/autoload.php';

define( 'EDD_BETTER_ACCOUNT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDD_BETTER_ACCOUNT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * @return \Daan\EDD\BetterAccount\Plugin
 */
function daan_load_edd_better_account() {
	static $better_account;

	if ( $better_account === null ) {
		$better_account = new \Daan\EDD\BetterAccount\Plugin();
	}

	return $better_account;
}

add_action( 'plugins_loaded', 'daan_load_edd_better_account' );
