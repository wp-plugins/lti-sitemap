<?php namespace Lti\Sitemap;

use Lti\Sitemap\Helpers\Wordpress_Helper;
use Lti\Sitemap\Plugin\Plugin_Settings;

/**
 * Main plugin class, loads all the goods
 *
 * A static instance is kept for testing purposes.
 *
 * Class LTI_Sitemap
 * @package Lti\Sitemap
 */
class LTI_Sitemap {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      \Lti\Sitemap\Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The current version of the plugin.
	 *
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	public static $instance;

	/**
	 * @var \Lti\Sitemap\Plugin\Plugin_Settings
	 */
	private $settings;

	private $file_path;
	public $plugin_path;
	private $basename;
	/**
	 * @var \Lti\Sitemap\Admin
	 */
	public $admin;
	/**
	 * @var \Lti\Sitemap\Frontend
	 */
	public $frontend;
	private $helper;

	private $sitemap_types = array( 'main', 'posts', 'pages', 'authors', "news" );

	public static $is_plugin_page = false;
	public static $review_url = "http://wordpress.org/support/view/plugin-reviews/%s#postform";
	public static $changelog_url = "https://wordpress.org/plugins/%s/changelog/";
	public static $lti_seo_url;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 */
	public function __construct() {
		$this->file_path   = plugin_dir_path( __FILE__ );
		$this->name        = LTI_SITEMAP_NAME;
		$this->plugin_path = LTI_SITEMAP_PLUGIN_DIR;
		$this->basename    = LTI_SITEMAP_PLUGIN_BASENAME;
		$this->settings    = get_option( "lti_sitemap_options" );

		if ( $this->settings === false || empty( $this->settings ) ) {
			$this->settings = new Plugin_Settings();
		}

		$this->load_dependencies();
		$this->set_locale();
	}

	public static function get_instance() {
		static::$is_plugin_page = ( filter_input( INPUT_GET, 'page' ) == 'lti-sitemap-options' );

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get_settings() {
		return $this->settings;
	}

	public function get_helper() {
		return $this->helper;
	}

	private function load_dependencies() {
		require_once $this->plugin_path . 'src/helper.php';

		$this->loader        = new Loader();
		$this->helper        = new Wordpress_Helper( $this->settings );
		static::$lti_seo_url = $this->helper->plugin_install_url( 'LTI SEO' );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new i18n( $this->name );
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @access   private
	 */
	private function define_admin_hooks() {
		$this->admin = new Admin( $this->name, $this->basename, $this->version, $this->settings, $this->plugin_path,
			$this->helper );

		$this->loader->add_action( 'admin_init', $this, 'admin_init' );
		$this->loader->add_action( 'admin_menu', $this->admin, 'admin_menu' );
		$this->loader->add_filter( 'plugin_action_links', $this->admin, 'plugin_action_links', 10, 2 );
		$this->loader->add_filter( 'plugin_row_meta', $this->admin, 'plugin_row_meta', 10, 2 );
		if ( $this->settings->get( 'content_news_support' ) == true ) {
			$this->loader->add_action( 'add_meta_boxes', $this->admin, 'add_meta_boxes' );
			$this->loader->add_action( 'save_post', $this->admin, 'save_post', 10, 3 );
		}

		if ( isset( $GLOBALS['pagenow'] ) ) {
			if ( $GLOBALS['pagenow'] === 'post.php' || LTI_Sitemap::$is_plugin_page || $GLOBALS['pagenow'] === 'post-new.php' ) {
				$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_styles' );
				$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_scripts' );
			}
		}

		if ( LTI_Sitemap::$is_plugin_page ) {
			$this->loader->add_filter( 'admin_footer_text', $this, 'admin_footer_text' );
			$this->loader->add_filter( 'update_footer', $this, 'update_footer', 15 );
		}
	}

	/**
	 * @return \Lti\Sitemap\Admin
	 */
	public function get_admin() {
		return $this->admin;
	}

	public function get_frontend() {
		return $this->frontend;
	}

	public function http_request_query_string( $vars ) {
		array_push( $vars, 'lti_sitemap' );

		return $vars;
	}

	/**
	 * Handles sitemap.xml related http queries
	 *
	 * The url sitemap.xml generates a sitemap index
	 *
	 * Anything else generates a regular sitemap. For example:
	 * sitemap-main.xml, sitemap-posts-yyyy-mm.xml, sitemap-pages.xml, sitemap-authors.xml
	 *
	 */
	public function http_request_handler() {
		/**
		 * @var \WP_Query $wp_query
		 */
		global $wp_query;
		if ( ! empty( $wp_query->query_vars["lti_sitemap"] ) ) {
			$parsedOptions = array();
			$options       = explode( ";", $wp_query->query_vars["lti_sitemap"] );
			foreach ( $options AS $option ) {
				$keyValue                      = explode( "=", $option );
				$parsedOptions[ $keyValue[0] ] = @$keyValue[1];
			}
			$type = "index";
			if ( isset( $parsedOptions["params"] ) ) {
				$sitemapFileNameSuffix = $parsedOptions["params"];

				//We grab the month and year out of the filename so we can build date based sitemaps if needed
				if ( preg_match( '#([\w-_]+)\-([0-9]{4})\-?([0-9]{2})?$#', $sitemapFileNameSuffix, $matches ) ) {
					$type = $matches[1];
					$this->settings->set( 'year', $matches[2] );
					if ( isset( $matches[3] ) ) {
						$this->settings->set( 'month', $matches[3] );
					}
				} else {
					$type = $sitemapFileNameSuffix;
				}
			}
			if ( ! headers_sent() ) {
				header( 'Content-Type: text/xml; charset=utf-8' );
				//Robots can't index sitemaps, but have to be able to follow links (that's kind of the point of sitemaps)
				header( 'X-Robots-Tag: noindex,follow' );
				//No need for pingbacks here
				header_remove( 'X-Pingback' );
			}

			//If we have a type matching the filename, we echo it and bail, otherwise we go 404
			if ( empty( $type ) || in_array( $type, $this->sitemap_types ) ) {
				echo $this->frontend->build_sitemap( $type );
				exit;
			} else {
				$wp_query->is_404 = true;
			}
		}
	}

	/**
	 * Displays a text asking for people's feedback
	 *
	 * @param $text
	 *
	 * @return string
	 */
	public function admin_footer_text( $text ) {
		return sprintf( '<em>%s <a target="_blank" href="%s">%s</a></em>',
			lsmint( 'admin.footer.feedback' ), sprintf( static::$review_url, LTI_SITEMAP_NAME ),
			lsmint( 'admin.footer.review' ) );
	}

	/**
	 * Displays plugin versions
	 *
	 * @param $text
	 *
	 * @return string
	 */
	public function update_footer( $text ) {
		return sprintf( '<a target="_blank" title="%s" href="%s">%s %s</a>, %s',
			lsmint( 'general.changelog' ), sprintf( static::$changelog_url, LTI_SITEMAP_NAME ),
			lsmint( 'general.version' ), LTI_SITEMAP_VERSION, $text );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @access   private
	 */
	private function define_frontend_hooks() {
		$this->frontend = new Frontend( $this->name, $this->version, $this->settings, $this->helper );
		$this->loader->add_filter( 'query_vars', $this, 'http_request_query_string', 1, 1 );
		$this->loader->add_filter( 'template_redirect', $this, 'http_request_handler', 1 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 */
	public function run() {
		$this->define_admin_hooks();
		$this->define_frontend_hooks();
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    \Lti\Sitemap\Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public static function activate() {
		Activator::activate();
	}

	public static function deactivate() {
		Deactivator::deactivate();
	}

	public function admin_init() {
		Activator::init_options();
	}

}
