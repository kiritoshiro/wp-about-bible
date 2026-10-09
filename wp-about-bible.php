<?php
/**
 * Plugin Name: Biblijos pažinimas
 * Plugin URI: https://github.com/kiritoshiro/wp-about-bible
 * Description: Lietuviška Biblijos pažinimo biblioteka: publikacijos, šaltiniai, temos ir tyrinėjimo keliai.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.3
 * Author: Adventistai
 * License: GPL-2.0-or-later
 * Text Domain: wp-about-bible
 * Update URI: https://github.com/kiritoshiro/wp-about-bible
 *
 * @package AboutBible
 */

defined( 'ABSPATH' ) || exit;
define( 'ABIBLE_VERSION', '0.1.0' );
define( 'ABIBLE_FILE', __FILE__ );
define( 'ABIBLE_PATH', plugin_dir_path( __FILE__ ) );

require_once ABIBLE_PATH . 'includes/class-content.php';
require_once ABIBLE_PATH . 'includes/class-admin.php';
require_once ABIBLE_PATH . 'includes/class-frontend.php';
require_once ABIBLE_PATH . 'includes/class-updater.php';

add_action( 'init', array( 'ABible_Content', 'register' ) );
register_activation_hook( __FILE__, array( 'ABible_Content', 'activate' ) );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
new ABible_Admin();
new ABible_Frontend();
new ABible_Updater();
