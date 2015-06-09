<?php namespace Lti\Sitemap\Helpers;

use Lti\Sitemap\Plugin\Postbox_Values;

interface ICanHelp {
}

/**
 * Does anything wordpress related on behalf of generators
 *
 * Class Wordpress_Helper
 * @package Lti\Sitemap\Helpers
 */
class Wordpress_Helper implements ICanHelp {

	/**
	 * @var \Lti\Sitemap\Plugin\Plugin_Settings
	 */
	protected $settings;
	/**
	 * @var Postbox_Values
	 */
	private $post_meta;
	private $post_id;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function get( $value ) {
		return $this->settings->get( $value );
	}

	public function get_settings() {
		return $this->settings;
	}

	public function filter_var_array($data, $filter = FILTER_SANITIZE_STRING){
		return filter_var_array($data, $filter);
	}

	public function home_url(){
		return home_url('/');
	}

	public function sitemap_url(){
		return $this->home_url()."sitemap.xml";
	}

	public static function get_supported_post_types() {
		/**
		 * Allow filtering of supported post types
		 *
		 * @api array the list of supported types
		 */
		return apply_filters('lti_supported_post_types',get_post_types( array( 'public' => true, 'show_ui' => true ) ));
	}

	public function get_language(){
		return get_locale();
	}

	/**
	 * Wrapper for the built-in filter_input function
	 *
	 * @param $type
	 * @param $variable_name
	 * @param int $filter
	 *
	 * @return mixed
	 */
	public function filter_input( $type, $variable_name, $filter = FILTER_DEFAULT ) {
		return filter_input( $type, $variable_name, $filter );
	}

	public function plugin_install_url( $search ) {
		$qS = sprintf( 'plugin-install.php?tab=search&s=%s', str_replace( ' ', '+', $search ) );
		if ( is_multisite() && is_network_admin() ) {
			return network_admin_url( $qS );
		}

		return admin_url( $qS );
	}

	public function get_post_meta_key( $key ) {
		return get_post_meta( $this->get_post_info( 'ID' ),$key, true );
	}

	private function get_post_info( $key ) {
		$field = get_post_field( $key, '', 'raw' );
		if ( ! empty( $field ) ) {
			return $field;
		}

		return null;
	}

	public function get_keywords() {
		$keywords = array();

			$keywords = $this->get_post_meta_key( 'keyword_text' );
			if ( empty( $keywords ) || is_null( $keywords ) ) {
				$keywords = array();
				if ( $this->settings->get( 'news_keywords_cat_based' ) === true ) {
					$keywords = array_unique( $this->get_categories() );
				}
				if ( $this->settings->get( 'news_keywords_tag_based' ) === true ) {
					$keywords = array_unique( array_merge( $keywords,
						$this->get_tags() ) );
				}
			} else {
				$keywords = explode( ",", str_replace( ', ', ',', $keywords ) );
			}
		return $keywords;
	}

	public function get_categories() {
		return $this->extract_array_object_value( get_the_category( ),
			'cat_name' );
	}

	public function get_tags() {
		return $this->extract_array_object_value( get_the_tags(),
			'name' );
	}

	public function extract_array_object_value( $values, $field ) {
		$vals = array();
		if ( is_array( $values ) ) {
			foreach ( $values as $value ) {
				$vals[] = $value->{$field};
			}
		}

		return $vals;
	}

}