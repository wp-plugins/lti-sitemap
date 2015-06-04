<?php namespace Lti\Sitemap\Plugin;

/**
 * Loads all the default plugin values
 *
 * An object of this type is inserted in the options database table
 * whenever plugin settings are saved.
 *
 * Class Defaults
 * @package Lti\Sitemap\Plugin
 * @see Lti\Sitemap\Plugin\Fields
 */
class Defaults {
	public $values;

	public function __construct() {
		$this->values = array(
			new def( 'content_frontpage', 'Checkbox', true ),
			new def( 'content_posts', 'Checkbox', false ),
			new def( 'content_posts_display', 'Radio',
				array( 'default' => 'normal', 'choice' => array( 'normal', 'year', 'month' ) ) ),
			new def( 'content_pages', 'Checkbox', false ),
			new def( 'content_authors', 'Checkbox', false ),
			new def( 'content_user_defined', 'Checkbox', false ),
			new def( 'content_images_support', 'Checkbox', false ),
			new def( 'change_frequency_frontpage', 'Text', 'daily' ),
			new def( 'change_frequency_posts', 'Text', 'weekly' ),
			new def( 'change_frequency_pages', 'Text', 'monthly' ),
			new def( 'change_frequency_authors', 'Text', 'monthly' ),
			new def( 'change_frequency_user_defined', 'Text', 'monthly' ),
			new def( 'priority_frontpage', 'Text', 1 ),
			new def( 'priority_posts', 'Text', 0.9 ),
			new def( 'priority_pages', 'Text', 0.7 ),
			new def( 'priority_authors', 'Text', 0.3 ),
			new def( 'priority_user_defined', 'Text', 0.3 ),
			new def( 'extra_pages_url', 'Array' ),
			new def( 'extra_pages_date', 'Array' ),
			new def( 'google_access_token', 'Text' ),
			new def( 'content_news_support', 'Checkbox', false, true ),
			new def( 'news_publication', 'Text' ),
			new def( 'news_language', 'Text', 'en' ),
			new def( 'news_access_type', 'Radio',
				array( 'default' => 'Full', 'choice' => array( 'Full', 'Subscription', 'Registration' ) ), true ),
			new def( 'news_genre_press_release', 'Checkbox', false, true ),
			new def( 'news_genre_satire', 'Checkbox', false, true ),
			new def( 'news_genre_blog', 'Checkbox', false, true ),
			new def( 'news_genre_oped', 'Checkbox', false, true ),
			new def( 'news_genre_opinion', 'Checkbox', false, true ),
			new def( 'news_genre_user_generated', 'Checkbox', false, true ),
			new def( 'news_keywords_cat_based', 'Checkbox', false),
			new def( 'news_keywords_tag_based', 'Checkbox', false ),
		);
	}
}

/**
 * Defines default values for each field
 *
 * Class def
 * @package Lti\Sitemap\Plugin
 */
class def {

	/**
	 * @var string Name of the setting, which will be used throughout the app
	 */
	public $name;
	/**
	 * @var string Type of value (text, radio...)
	 */
	public $type;
	/**
	 * @var mixed Value when initialized
	 */
	public $default_value;
	/**
	 * @var bool Whether the setting has knock on effects on postbox values.
	 */
	public $impacts_user_settings;

	/**
	 * @param $name
	 * @param $type
	 * @param null $default_value
	 * @param bool $impacts_user_settings
	 */
	public function __construct( $name, $type, $default_value = null, $impacts_user_settings = false ) {
		$this->name                  = $name;
		$this->type                  = __NAMESPACE__ . "\\Field_" . $type;
		$this->default_value         = $default_value;
		$this->impacts_user_settings = $impacts_user_settings;
	}
}

/**
 * Puts all settings together
 *
 * Class Plugin_Settings
 * @package Lti\Sitemap\Plugin
 */
class Plugin_Settings {
	/**
	 * @param \stdClass $settings
	 */
	public function __construct( \stdClass $settings = null ) {

		$defaults = new Defaults();

		/**
		 * @var def $value
		 */
		foreach ( $defaults->values as $value ) {
			$storedValue = false;
			if ( isset( $settings->{$value->name} ) ) {
				$storedValue = $settings->{$value->name};
			}
			$className = $value->type;

			//Settings is null when we reset to defaults
			//In that case, we need to set the value to null so that checkboxes pick up their default values instead
			//of being initialized to false
			if ( $settings == null ) {
				$this->{$value->name} = new $className( null, $value->default_value,
					$value->impacts_user_settings );
			} else {
				$this->{$value->name} = new $className( $storedValue, $value->default_value,
					$value->impacts_user_settings );

			}
		}
	}

	public static function get_defaults() {
		return new self();
	}

	public function save( Array $values = array() ) {
		return new Plugin_Settings( (object) $values );
	}

	public function get( $value ) {
		if ( isset( $this->{$value} ) && ! empty( $this->{$value}->value ) && ! is_null( $this->{$value}->value ) ) {
			return $this->{$value}->value;
		}

		return null;
	}

	/**
	 * Adding new values to the settings class (like temporary ones) or setting existing ones.
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $type Text, Checkbox, Radio, etc.
	 */
	public function set( $key, $value, $type = "Text" ) {
		//We make sure the field, if it exists in the settings class,
		//has the same type as originally defined because that impacts how the value is sanitized.
		if ( isset( $this->{$key} ) ) {
			$rC   = new \ReflectionClass( $this->{$key} );
			$type = substr( $rC->getShortName(), 6 );
			//Radio buttons are supposed to be initialized with an array of default values but when we set values
			//like this we don't set defaults so we pass Radio types as Text types. Values set this way are temporary anyway.
			if ($type == 'Radio') {
				$type = 'Text';
			}
		}
		$className    = __NAMESPACE__ . "\\Field_" . $type;
		$this->{$key} = new $className( $value );
	}

	public function remove( $key ) {
		if ( isset( $this->{$key} ) ) {
			unset( $this->{$key} );
		}
	}

	/**
	 * Comparing two Plugin_Settings objects
	 *
	 * @param Plugin_Settings $values
	 *
	 * @return array $changed key-value array of the properties that changed
	 */
	public function compare( $values ) {
		$changed       = array();
		$currentValues = get_object_vars( $this );
		$oldValues     = get_object_vars( $values );

		foreach ( $currentValues as $key => $value ) {
			if ( $value->isTracked ) {
				if ( isset( $oldValues[ $key ] ) && $oldValues[ $key ]->value != $value->value ) {
					$changed[ $key ] = $value->value;
				}
			}
		}

		return $changed;
	}
}

