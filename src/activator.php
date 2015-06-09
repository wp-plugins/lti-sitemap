<?php namespace Lti\Sitemap;

use Lti\Sitemap\Plugin\Plugin_Settings;

/**
 * Fired during plugin activation
 */
class Activator {

	public static function activate() {
		if ( ! get_option( 'permalink_structure' ) ) {
			echo "LTI Sitemap can't activate; it relies on pretty permalinks to display sitemaps.";
			exit;
		}
		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			echo "LTI Sitemap requires PHP 5.3.";
			exit;
		}

		add_filter( 'rewrite_rules_array', array( __CLASS__, 'rewrite_rules_array' ), 1, 1 );
		/**
		 * @var \WP_Rewrite $wp_rewrite
		 */
		global $wp_rewrite;
		$wp_rewrite->flush_rules( false );
		static::init_options();
	}

	/**
	 * @param array $rewriteRules
	 *
	 * @see \WP_Rewrite::rewrite_rules
	 * @return array
	 */
	public static function rewrite_rules_array( $rewriteRules ) {
		return array_merge( array(
			'sitemap(-+([a-zA-Z0-9_-]+))?\.xml$' => 'index.php?lti_sitemap=params=$matches[2]',
		), $rewriteRules );
	}

	public static function init_options() {
		$stored_options = get_option( "lti_sitemap_options" );
		if ( empty( $stored_options ) || $stored_options === false ) {
			$defaults = Plugin_Settings::get_defaults();
			$defaults->set( 'news_language', substr( get_locale(), 0, 2 ) );
			update_option( "lti_sitemap_options", $defaults );
		}
	}

}
