<?php

/**
 * Shopbox woocommerce integration
 *
 * @link              https://shopbox.com
 * @since             1.15.0
 * @package           Shopbox
 *
 * @wordpress-plugin
 * Plugin Name:       Shopbox
 * Description:       Shopbox woocommerce integration
 * Version:           1.17.97
 * Author:            Shopbox
 * Author URI:        https://shopbox.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       shopbox
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require __DIR__.'/vendor/autoload.php';

function activate_shopbox() {
	\ShopBox\Activator::activate();
}

function deactivate_shopbox() {
	// ShopBox\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_shopbox' );
register_deactivation_hook( __FILE__, 'deactivate_shopbox' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.12.0
 */
function run_shopbox() {

	$plugin = new \ShopBox\ShopBox();
	$plugin->run();

}
run_shopbox();
