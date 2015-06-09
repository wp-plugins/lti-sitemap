<?php namespace Lti\Sitemap\Plugin;

/**
 * Settings for postbox fields
 * An object of this type is inserted into the postmeta database table
 * when posts are saved
 *
 *
 * Class Postbox_Fields
 * @package Lti\Sitemap\Plugin
 */
class Postbox_Fields {
	public $values = array(
		array( 'post_is_news', 'Checkbox' ),
		array(
			'news_access_type',
			'Radio',
			array( 'default' => 'Full', 'choice' => array( 'Full', 'Subscription', 'Registration' ) )
		),
		array( 'news_genre_press_release', 'Checkbox' ),
		array( 'news_genre_satire', 'Checkbox' ),
		array( 'news_genre_blog', 'Checkbox' ),
		array( 'news_genre_oped', 'Checkbox' ),
		array( 'news_genre_opinion', 'Checkbox' ),
		array( 'news_genre_user_generated', 'Checkbox' ),
		array( 'news_title', 'Text' ),
		array( 'news_keywords', 'Text' ),
		array( 'news_stock_tickers', 'Text' ),
	);
}

class Postbox_Values {
	public function __construct( $form = null ) {

		if ( is_object( $form ) ) {
			$postbox = new Postbox_Fields();

			foreach ( $postbox->values as $value ) {
				$storedValue = null;
				if ( isset( $form->{$value[0]} ) ) {
					$storedValue = $form->{$value[0]};
				}
				$default   = null;
				$className = __NAMESPACE__ . "\\Field_" . $value[1];
				if ( isset( $value[2] ) ) {
					$default = $value[2];
				}
				$this->{$value[0]} = new $className( $storedValue, $default );
			}
		}
	}

	public function get( $value ) {
		if ( isset( $this->{$value} ) && ! empty( $this->{$value}->value ) && ! is_null( $this->{$value}->value ) ) {
			return $this->{$value}->value;
		}

		return null;
	}

	public function set( $key, $value, $type = "Text" ) {
		if ( isset( $this->{$key} ) ) {
			$this->{$key}->value = $value;
		} else {
			$className    = __NAMESPACE__ . "\\Field_" . $type;
			$this->{$key} = new $className( $value );

		}
	}

	public function get_genres() {
		$type=array();
		if ( $this->get( 'news_genre_press_release' ) == true ) {
			$type[] = 'PressRelease';
		}
		if ( $this->get( 'news_genre_satire' ) == true ) {
			$type[] = 'Satire';
		}
		if ( $this->get( 'news_genre_blog' ) == true ) {
			$type[] = 'Blog';
		}
		if ( $this->get( 'news_genre_oped' ) == true ) {
			$type[] = 'OpEd';
		}
		if ( $this->get( 'news_genre_opinion' ) == true ) {
			$type[] = 'Opinion';
		}
		if ( $this->get( 'news_genre_user_generated' ) == true ) {
			$type[] = 'UserGenerated';
		}
		return implode(',',$type);
	}

	/**
	 * @param Plugin_Settings $settings
	 */
	public function set_postbox_params($settings){
		$this->set('news_genre_press_release',$settings->get('news_genre_press_release'));
		$this->set('news_genre_satire',$settings->get('news_genre_satire'));
		$this->set('news_genre_blog',$settings->get('news_genre_blog'));
		$this->set('news_genre_oped',$settings->get('news_genre_oped'));
		$this->set('news_genre_opinion',$settings->get('news_genre_opinion'));
		$this->set('news_genre_user_generated',$settings->get('news_genre_user_generated'));
		$this->set('news_access_type',$settings->get('news_access_type'));
	}

}