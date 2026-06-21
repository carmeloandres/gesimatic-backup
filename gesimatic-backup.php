<?php
/** 
 * Gesimatic Backup plugin for WordPress.
 * 
 * @package     gesimatic-backup
 * @since       1.0.0
 * @author      Carmelo Andrés
 * @license     Saas license
 * @copyright   2026 Carmelo Andrés
 * 
 * @wordpress-plugin
 * Plugin Name:         Gesimatic backup
 * Plugin URI:          https://www.gesimatic.com/gesimatic-backup  
 * Description:         This plugin adds backup functionality to gesimatic.
 * Version:             1.0
 * Requires at least:   6.2
 * Requires PHP:      	7.0
 * Author:              Carmelo Andrés
 * Author URI:          https://carmeloandres.com
 * License:             saas license
 * License URI:       	https://www.gesimatic/saas-terms
 * Text Domain:         gesimatic-backup
 * Domain Path:         /languages
 * Requires Plugins:    gesimatic
 */ 

defined( 'ABSPATH' ) || exit; // To prevent direct access

/**
 * Configuration constants.
 *
 * @since 1.0.0
 *
 * @const string GESIMATIC_BACKUP_PATH      Absolute path to the plugin directory.
 * @const string GESIMATIC_BACKUP_URL       Absolute URL to the plugin directory.
 * @const int    GESIMATIC_BACKUP_VERSION   Plugin version.
 */
define ('GESIMATIC_BACKUP_PATH',plugin_dir_path(__FILE__));
if (function_exists('is_multisite') && is_multisite()) {
    define ('GESIMATIC_BACKUP_URL',esc_url( network_site_url()).'wp-content/plugins/gesimatic-backup/');
} else { define ('GESIMATIC_BACKUP_URL',home_url('/wp-content/plugins/gesimatic-backup/')); }
define ('GESIMATIC_BACKUP_VERSION',425);

/**
 * Autoload dependencies via Composer
*/

    require_once GESIMATIC_BACKUP_PATH . 'vendor/autoload.php';

/**
 * Registers the plugin activation hook for the plugin.
 *
 * When the plugin is activated, WordPress will automatically execute
 * the {@see gesimatic_translations_activate()} function to perform the initial
 * setup tasks required by the plugin.
 *
 * @since 1.0.0
 *
 * @param string $file     The path to the main plugin file (`__FILE__`).
 * @param string $callback The name of the function to execute on activation.
 *
 * @see https://developer.wordpress.org/reference/functions/register_activation_hook/
 */
register_activation_hook(__FILE__,'gesimatic_static_forms_activate');

$gesimatic_backup = new GesimaticBackup\Core\Core();




