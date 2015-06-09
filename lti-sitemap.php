<?php namespace Lti\Sitemap;

/**
 * The plugin bootstrap file
 *
 * @wordpress-plugin
 * Plugin Name:       LTI Sitemap
 * Description:       Hassle free XML Sitemaps: pick your featured content, let search engines do the rest!
 * Version:           0.5.1
 * Author:            Linguistic Team International
 * Author URI:        http://dev.linguisticteam.org/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lti-sitemap
 * Domain Path:       /languages/
 */

/**
 * LTI Sitemap
 * Copyright (C) 2015, Bruno De Carvalho - decarvalho.bruno@free.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$plugin_dir_path = plugin_dir_path( __FILE__ );
define( 'LTI_SITEMAP_PLUGIN_DIR', $plugin_dir_path );
define( 'LTI_SITEMAP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'LTI_SITEMAP_VERSION', '0.5.1' );
define( 'LTI_SITEMAP_NAME', 'lti-sitemap' );

require_once $plugin_dir_path. 'vendor/autoload.php';

register_activation_hook( __FILE__, array( 'Lti\Sitemap\LTI_Sitemap', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Lti\Sitemap\LTI_Sitemap', 'deactivate' ) );

add_filter( 'rewrite_rules_array', 'Lti\Sitemap\Activator::rewrite_rules_array', 1, 1 );
$plugin = LTI_Sitemap::get_instance();
$plugin->run();