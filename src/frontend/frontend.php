<?php namespace Lti\Sitemap;

use Lti\Sitemap\Generators\Sitemap_Generator_Index;
use Lti\Sitemap\Helpers\ICanHelp;
use Lti\Sitemap\Plugin\Plugin_Settings;


/**
 * Takes care of displaying sitemaps
 *
 * Class Frontend
 * @package Lti\Sitemap
 */
class Frontend {

	private $plugin_name;
	private $version;

	/**
	 * @var \Lti\Sitemap\Plugin\Plugin_Settings
	 */
	private $settings;

	/**
	 * @var ICanHelp|\Lti\Sitemap\Helpers\Wordpress_Helper
	 */
	private $helper;

	/**
	 * @param string $plugin_name
	 * @param string $version
	 * @param Plugin_Settings $settings
	 * @param ICanHelp $helper
	 */
	public function __construct(
		$plugin_name,
		$version,
		Plugin_Settings $settings,
		ICanHelp $helper
	) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->settings    = $settings;
		$this->helper      = $helper;
	}

	/**
	 * Sitemap building class, takes in a type
	 * and returns a string containing the sitemap's XML
	 *
	 * @param null $type
	 *
	 * @return string
	 */
	public function build_sitemap( $type = null ) {
		switch ( $type ) {
			case 'main':
			case 'posts':
			case 'pages':
			case 'authors':
			case 'news':
				$class   = sprintf( 'Lti\Sitemap\Generators\Sitemap_Generator_%s', ucfirst( $type ) );
				$sitemap = new $class( $this->settings, $this->helper );
				break;
			default:
				$sitemap = new Sitemap_Generator_Index( $this->settings, $this->helper );
		}

		return $sitemap->get();

	}

	public function remove_setting( $setting ) {
		$this->settings->remove( $setting );
	}

	public function set_setting( $setting, $value, $type = 'Text' ) {
		$this->settings->set( $setting, $value, $type );
	}

}
