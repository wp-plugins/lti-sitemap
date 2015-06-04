<?php namespace Lti\Sitemap;

/**
 * Class Deactivator
 * @package Lti\Sitemap
 */
class Deactivator {

	public static function deactivate() {
		//Remove rewrite rules and then recreate rewrite rules without the ones the plugin activator created.
		flush_rewrite_rules();
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

}
