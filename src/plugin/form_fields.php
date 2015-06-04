<?php namespace Lti\Sitemap\Plugin;

/**
 * Used to spot field types on forms and display values accordingly
 *
 * Class Fields
 * @package Lti\Sitemap\Plugin
 */
abstract class Fields {
	public $value;
	public $isTracked;

	public function __construct( $value, $default = "", $isTracked = false ) {
		$this->isTracked = $isTracked;
		if ( $value ) {
			$this->value = sanitize_text_field( stripslashes( $value ) );
		} else {
			$this->value = $default;
		}
	}
}

class Field_Checkbox extends Fields {
	public function __construct( $value, $default = false, $isTracked = false ) {
		$this->isTracked = $isTracked;
		if ( $value === true || (int) $value === 1 || $value === "true" || $value === 'on' ) {
			$this->value = true;
		} else if ( $value === false ) {
			$this->value = false;
		} else {
			$this->value = $default;
		}
	}
}

class Field_Radio extends Fields {

	public function __construct( $value, $default = "", $isTracked = false ) {
		$this->isTracked = $isTracked;
		if ( is_array( $default ) ) {
			if ( $value ) {
				if ( in_array( $value, $default['choice'] ) ) {
					$this->value = $value;
				} else {
					$this->value = $default['default'];
				}
			} else {
				$this->value = $default['default'];
			}
		} else {
			$this->value = null;
		}
	}
}

class Field_Text extends Fields {
}

class Field_String extends Fields {

}

class Field_Url extends Fields {
	public function __construct( $value, $default = "", $isTracked = false ) {
		$this->isTracked = $isTracked;
		if ( $value && ! filter_var( $value, FILTER_VALIDATE_URL ) === false ) {
			$this->value = $value;
		} else {
			$this->value = $default;
		}
	}

}

class Field_Array extends Fields {
	public function __construct( $value, $default = array(), $isTracked = false ) {
		$this->isTracked = $isTracked;
		if ( ! empty( $value ) ) {
			$this->value = $value;
		} else {
			$this->value = $default;
		}
	}
}
