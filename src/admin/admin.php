<?php namespace Lti\Sitemap;

use Lti\Google\Google_Helper;
use Lti\Sitemap\Helpers\Bing_Helper;
use Lti\Sitemap\Helpers\ICanHelp;
use Lti\Sitemap\Plugin\Fields;
use Lti\Sitemap\Plugin\Plugin_Settings;
use Lti\Sitemap\Plugin\Postbox_Values;
use Lti\Wordpress\LTI_Menu;

/**
 * Deals with everything that happens in the admin screen
 *
 *
 * Class Admin
 * @package Lti\Sitemap
 */
class Admin {

	/**
	 * @var string Tracks page type so we can display error/warning messages
	 */
	private $page_type = 'edit';
	/**
	 * @var string Contains messages to be displayed after saves/resets
	 */
	private $message = '';
	/**
	 * @var string In case we forget our own name in the heat of the battle
	 */
	private $plugin_name;
	/**
	 * @var string Plugin version
	 */
	private $version;
	/**
	 * @var \Lti\Sitemap\Plugin\Plugin_Settings
	 */
	private $settings;
	/**
	 * @var string Helps defining what kind of settings to use (settings or postbox values)
	 */
	private $current_page = "admin";
	/**
	 * @var \Lti\Sitemap\Helpers\Wordpress_Helper
	 */
	private $helper;

	/**
	 * @var Bing_Helper
	 */
	private $bing;

	/**
	 * @var Admin_Google
	 */
	private $google;

	private $site_url;

	/**
	 * @var Html_Elements
	 */
	private $html;
	/**
	 * @var \Lti\Sitemap\Plugin\Postbox_Values
	 */
	private $box_values;


	/**
	 * @param $plugin_name
	 * @param $plugin_basename
	 * @param $version
	 * @param Plugin_Settings $settings
	 * @param $plugin_path
	 * @param ICanHelp $helper
	 */
	public function __construct(
		$plugin_name,
		$plugin_basename,
		$version,
		Plugin_Settings $settings,
		$plugin_path,
		ICanHelp $helper
	) {

		$this->plugin_name     = $plugin_name;
		$this->plugin_basename = $plugin_basename;
		$this->version         = $version;
		$this->admin_dir_url   = plugin_dir_url( __FILE__ );
		$this->admin_dir       = dirname( __FILE__ );
		$this->plugin_dir      = $plugin_path;
		$this->plugin_dir_url  = plugin_dir_url( $plugin_path . '/index.php' );
		$this->settings        = $settings;
		$this->helper          = $helper;

		if ( ! LTI_Sitemap::$is_plugin_page ) {
			return;
		}
		$this->google      = new Admin_Google( $this, $this->helper );
		$this->bing        = new Bing_Helper( $this->helper->sitemap_url() );
		$this->site_url    = $this->helper->home_url();
		$this->sitemap_url = $this->helper->sitemap_url();
	}

	/**
	 * Adding our CSS stylesheet
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			$this->plugin_dir_url . 'assets/dist/css/lti_sitemap_admin.css',
			array(),
			$this->version,
			'all' );
	}

	/**
	 * Adding our JS
	 * Defining translated values for javascript to use
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			$this->plugin_dir_url . 'assets/dist/js/lti_sitemap_admin.js',
			array( 'jquery' ),
			$this->version,
			false );
	}

	/**
	 * Adding "Help" button to the admin screen
	 */
	public function admin_menu() {
		if ( is_null( LTI_Menu::$main_menuitem ) ) {
			add_menu_page( 'LTI', 'LTI', 'manage_options', 'lti-sitemap-options',
				array( $this, 'options_page' ),
				LTI_Menu::$image_base64_url );
			LTI_Menu::$main_menuitem = 'lti-sitemap-options';
		}
		$page = add_submenu_page( LTI_Menu::$main_menuitem, lsmint( 'admin.menu_title' ), lsmint( 'admin.menu_item' ),
			'manage_options', 'lti-sitemap-options', array( $this, 'options_page' ) );
		add_action( 'load-' . $page, array( $this, 'wp_help_menu' ) );

	}

	/**
	 * Defining tabs for the help menu
	 *
	 * @see Admin::admin_menu
	 */
	public function wp_help_menu() {
		include $this->admin_dir . '/partials/help_menu.php';
		$screen = get_current_screen();
		$menu   = new \lti_sitemap_Help_Menu();

		$screen->add_help_tab( array(
			'id'      => 'general_hlp_welcome',
			'title'   => lsmint( 'general_hlp_welcome' ),
			'content' => $menu->welcome_tab()
		) );
		$screen->add_help_tab( array(
			'id'      => 'general_hlp_general',
			'title'   => lsmint( 'general_hlp_general' ),
			'content' => $menu->general_tab()
		) );
		$screen->add_help_tab( array(
			'id'      => 'general_hlp_google',
			'title'   => lsmint( 'general_hlp_google' ),
			'content' => $menu->google_tab()
		) );
		$screen->add_help_tab( array(
			'id'      => 'general_hlp_bing',
			'title'   => lsmint( 'general_hlp_bing' ),
			'content' => $menu->bing_tab()
		) );
		$screen->add_help_tab( array(
			'id'      => 'general_hlp_news',
			'title'   => lsmint( 'general_hlp_news' ),
			'content' => $menu->news_tab()
		) );
		$screen->set_help_sidebar(
			$menu->sidebar()
		);
	}

	/**
	 * Adds a LTI Sitemap button to the admin sidebar
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return mixed
	 */
	public function plugin_action_links( $links, $file ) {
		if ( $file == 'lti-sitemap/lti-sitemap.php' && function_exists( "admin_url" ) ) {
			array_unshift( $links,
				'<a href="' . admin_url( 'admin.php?page=lti-sitemap-options' ) . '">' . lsmint( 'general.settings' ) . '</a>' );
		}

		return $links;
	}

	/**
	 * User input validation
	 * Compares old values with new because some fields have a global impact,
	 * including values that users set in postboxes
	 *
	 * @param array $post_variables
	 * @param string $update_type the method to execute so the purpose of the submit button is fulfilled
	 */
	public function validate_input( $post_variables, $update_type ) {
		if ( wp_verify_nonce( $post_variables['lti_sitemap_token'], 'lti_sitemap_options' ) !== false ) {
			unset( $post_variables['_wpnonce'], $post_variables['option_page'], $post_variables['_wp_http_referer'] );
			$oldSettings         = $this->settings;
			$google_access_token = $this->settings->get( 'google_access_token' );

			if ( isset( $post_variables['extra_pages_url'] ) ) {
				$post_variables = $this->validate_extra_urls( $post_variables );
			}

			$this->settings = $this->settings->save( $post_variables );

			/**
			 * We save values into a new settings object, and our google access token, when set, isn't a part of the form
			 * so we make sure it's saved if it existed before this form submission.
			 */
			if ( ! is_null( $google_access_token ) ) {
				$this->settings->set( 'google_access_token', $google_access_token );
			}

			$this->page_type = "lti_update";

			if ( $this->settings != $oldSettings ) {
				$changed = $this->settings->compare( $oldSettings );

				if ( ! empty( $changed ) ) {
					$this->update_global_post_fields( $changed );
				}
			}

			if ( method_exists( $this->google, $update_type ) ) {
				$this->google->helper->init_sitemap_service( $this->helper->home_url(),
					$this->helper->sitemap_url() );
				call_user_func( array( $this->google, $update_type ), $post_variables );
			} else {
				$this->message = lsmint( "opt.msg.update_ok" );
			}

			update_option( 'lti_sitemap_options', $this->settings );
		} else {
			$this->page_type = "lti_error";
			$this->message   = lsmint( "opt.msg.error_token" );
		}
	}

	/**
	 * Adds postboxes to posts
	 *
	 */
	public function add_meta_boxes() {
		$supported_post_types = $this->get_supported_post_types();
		foreach ( $supported_post_types as $supported_post_type ) {
			add_meta_box(
				'lti-sitemap-metadata-box',
				lsmint( 'admin.meta_box' ),
				array( $this, 'metadata_box' ),
				$supported_post_type,
				'advanced',
				'high'
			);
		}
	}

	/**
	 * Displays postbox values
	 *
	 * @param \WP_Post $post
	 */
	public function metadata_box( \WP_Post $post ) {
		$this->box_values = get_post_meta( $post->ID, "lti_sitemap", true );

		/**
		 * When a post is created, checkboxes have to be initialized
		 */
		if ( empty( $this->box_values ) ) {
			$this->box_values = new Postbox_Values( array() );
			$this->box_values->set_postbox_params( $this->settings );
		}

		$keywords = $this->box_values->get( 'news_keywords' );
		if ( is_null( $keywords ) || empty( $keywords ) ) {

			$keywords = $this->helper->get_keywords();
			if ( ! empty( $keywords ) ) {
				$this->box_values->set( 'news_keywords_suggestion',
					implode( ', ', $keywords ) );
			}
		}

		$this->set_current_page( 'post-edit' );
		include $this->admin_dir . '/partials/postbox.php';
	}

	/**
	 * We need to update every post if the plugin setting affects post settings.
	 *
	 * @see \Lti\Sitemap\Plugin\Def::$impacts_user_settings
	 *
	 * @param array $changed
	 * @param bool $reset
	 */
	public function update_global_post_fields( $changed = array(), $reset = false ) {
		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;
		//@TODO: check whether this can be covered by some wp method
		$sql = 'SELECT ' . $wpdb->posts . '.ID,' . $wpdb->postmeta . '.meta_value  FROM ' . $wpdb->posts . '
				LEFT JOIN ' . $wpdb->postmeta . ' ON (' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id AND ' . $wpdb->postmeta . '.meta_key = "lti_sitemap")
				WHERE ' . $wpdb->posts . '.post_type = "post" AND ' . $wpdb->posts . '.post_status!="auto-draft"';

		$results = $wpdb->get_results( $sql );

		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$postbox_values = $result->meta_value;
				if ( ! is_null( $postbox_values ) && ! $reset ) {
					$postbox_values = unserialize( $postbox_values );
				} else {
					$postbox_values = new Postbox_Values( new \stdClass() );
				}

				foreach ( $changed as $changedKey => $changedValue ) {
					if ( isset( $postbox_values->{$changedKey} ) && $postbox_values->{$changedKey} instanceof Fields ) {
						$postbox_values->{$changedKey}->value = $changedValue;
					}
				}
				update_post_meta( $result->ID, 'lti_sitemap', $postbox_values );
			}
		}
	}

	/**
	 * Simplistic way of dealing with supported post types, but works for our purposes
	 *
	 * @return array
	 */
	public function get_supported_post_types() {
		return array( 'post' );
	}

	/**
	 * Renders the admin view and handles form posting
	 *
	 */
	public function options_page() {

		/**
		 * General > Bing > Send sitemap button
		 */
		$bing_url = filter_input( INPUT_GET, 'bing_url' );
		if ( ! is_null( $bing_url ) && wp_verify_nonce( filter_input( INPUT_GET, 'lti-sitemap-options' ),
				'bing_url_submission' )
		) {
			include $this->admin_dir . '/partials/bing_sitemap_submission.php';

			return;
		}

		$post_variables = $this->helper->filter_var_array( $_POST );
		$update_type    = '';

		/**
		 * Each submit button in the form has a particular name
		 * helping us figure out what kind of processing to do (if any)
		 * on top of saving settings
		 */
		switch ( true ) {
			case isset( $post_variables['lti_sitemap_update'] ):
				$update_type = "normal";
				break;
			case isset( $post_variables['lti_sitemap_google_auth'] ):
				$update_type = "google_auth";
				break;
			case isset( $post_variables['lti_sitemap_google_submit'] ):
				$update_type = "google_submit";
				break;
			case isset( $post_variables['lti_sitemap_google_resubmit'] ):
				$update_type = "google_resubmit";
				break;
			case isset( $post_variables['lti_sitemap_google_delete'] ):
				$update_type = "google_delete";
				break;
			case isset( $post_variables['lti_sitemap_google_logout'] ):
				$update_type = "google_logout";
				break;
			/**
			 * Settings reset handler
			 */
			case isset( $post_variables['lti_sitemap_reset'] ):
				$this->settings = new Plugin_Settings();

				$this->settings->set( 'news_language', substr( get_locale(), 0, 2 ) );
				update_option( 'lti_sitemap_options', $this->settings );
				$this->update_global_post_fields( array(), true );

				$this->page_type = "lti_reset";
				$this->message   = lsmint( 'opt.msg.reset' );
				break;
			default:
				$this->page_type = "lti_edit";
		}

		if ( isset( $post_variables['lti_sitemap_token'] ) && ! empty( $update_type ) ) {
			$this->validate_input( $post_variables, $update_type );
		}

		$this->html = new Html_Elements( $this->settings );

		include $this->admin_dir . '/partials/options-page.php';
	}

	/**
	 * Saves posts
	 *
	 * @param int $post_ID
	 * @param \WP_Post $post
	 * @param int $update
	 */
	public function save_post( $post_ID, $post, $update ) {
		if ( isset( $_POST['lti_sitemap'] ) ) {
			$post_variables = $this->helper->filter_var_array( $_POST['lti_sitemap'] );

			if ( isset( $post_variables['news_stock_tickers'] ) ) {
				$stock_tickers = array_slice(
					explode( ",", $post_variables['news_stock_tickers'] ), 0, 5 );

				//Stock tickers have a format "STOCK_EXCHANGE:VALUE", so we need to filter them
				$post_variables['news_stock_tickers'] = null;
				if ( ! empty( $stock_tickers ) ) {
					$tickers = array();
					foreach ( $stock_tickers as $ticker ) {
						if ( preg_match( '#(\w+:\w+)#', $ticker ) ) {
							$tickers[] = trim( $ticker );
						}
					}
					if ( ! empty( $tickers ) ) {
						$post_variables['news_stock_tickers'] = implode( ',', $tickers );
					}
				}
			}

			if ( ! is_null( $post_variables ) && ! empty( $post_variables ) ) {
				update_post_meta( $post_ID, 'lti_sitemap', new Postbox_Values( (object) $post_variables ) );
			}

			if ( isset( $_POST['lti_sitemap_news'] ) ) {
				//If the post is a news item, we set a special post meta to make sitemap queries easier.
				update_post_meta( $post_ID, 'lti_sitemap_post_is_news', true );
			} else {
				delete_post_meta( $post_ID, 'lti_sitemap_post_is_news' );
			}
		}
	}

	/**
	 * Adding extra links to the LTI Sitemap entry in plugins.php
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file == $this->plugin_basename ) {
			$links[] = '<a href="https://github.com/DeCarvalhoBruno/lti-wp-sitemap" target="_blank">' . lsmint( 'admin.contribute' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Returns the proper settings to apply depending on whether we're in the settings screen
	 * or editing a post/page.
	 *
	 * @return \Lti\Seo\Plugin\Plugin_Settings
	 */
	public function get_form_values() {
		switch ( $this->current_page ) {
			case "post-edit":
				return $this->box_values;
		}

		return $this->settings;
	}

	public function set_current_page( $page ) {
		$this->current_page = $page;
	}

	public function get_settings() {
		return $this->settings;
	}

	public function get_setting( $setting ) {
		return $this->settings->get( $setting );
	}

	public function get_page_type() {
		return $this->page_type;
	}

	public function get_message() {
		return $this->message;
	}

	public function set_message( $message ) {
		$this->message = $message;
	}

	public static function get_admin_slug() {
		return admin_url( 'admin.php?page=lti-sitemap-options' );
	}

	public function remove_setting( $setting ) {
		$this->settings->remove( $setting );
	}

	public function set_setting( $setting, $value, $type = 'Text' ) {
		$this->settings->set( $setting, $value, $type );
	}

	public function get_lti_seo_url() {
		return LTI_Sitemap::$lti_seo_url;
	}

	public function get_site_url() {
		return $this->site_url;
	}

	public function get_sitemap_url() {
		return $this->sitemap_url;
	}

	/**
	 * Validation for URLs entered in General > User defined pages
	 *
	 * @param $post_variables
	 *
	 * @return mixed
	 */
	private function validate_extra_urls( $post_variables ) {
		$urls = $post_variables['extra_pages_url'];

		if ( ! empty( $urls ) ) {
			//If the URL filter doesn't pass, we remove the whole entry
			foreach ( $urls as $key => $url ) {
				if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
					unset( $post_variables['extra_pages_url'][ $key ] );
					if ( isset( $post_variables['extra_pages_date'] ) && isset( $post_variables['extra_pages_date'][ $key ] ) ) {
						unset( $post_variables['extra_pages_date'][ $key ] );
					}
				}
			}

			if ( isset( $post_variables['extra_pages_date'] ) ) {
				$dates = $post_variables['extra_pages_date'];

				if ( ! empty( $dates ) ) {
					foreach ( $dates as $key => $date ) {
						if ( empty( $date ) ) {
							continue;
						}
						if ( preg_match( '#^(\d{4})-(\d{2})-(\d{2})$#', $date, $matches ) === 0 ) {
							$post_variables['extra_pages_date'][ $key ] = null;
						} else if ( checkdate( intval( $matches[2] ), intval( $matches[3] ),
								intval( $matches[1] ) ) === false
						) {
							$post_variables['extra_pages_date'][ $key ] = null;
						}
					}
				}

			}
		}

		return $post_variables;
	}

}
