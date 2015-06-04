<?php namespace Lti\Sitemap;

use Lti\Sitemap\Plugin\Plugin_Settings;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Intl as Languages;

/**
 * Class Html_Elements
 * @package Lti\Sitemap
 *
 * Adds some common html elements to options-page.php
 *
 */
class Html_Elements {

	private $settings;
	private $priorities;
	private $changeFrequencies;
	public $extraPages;

	/**
	 * @param \Lti\Sitemap\Plugin\Plugin_Settings $settings
	 */
	public function __construct( Plugin_Settings $settings ) {
		$this->settings = $settings;

		$this->changeFrequencies = array(
			"always"  => lsmint( 'opt.change_frequency.always' ),
			"hourly"  => lsmint( 'opt.change_frequency.hourly' ),
			"daily"   => lsmint( 'opt.change_frequency.daily' ),
			"weekly"  => lsmint( 'opt.change_frequency.weekly' ),
			"monthly" => lsmint( 'opt.change_frequency.monthly' ),
			"yearly"  => lsmint( 'opt.change_frequency.yearly' ),
			"never"   => lsmint( 'opt.change_frequency.never' )
		);

		$this->priorities = array(
			"1"   => "100%",
			"0.9" => "90%",
			"0.8" => "80%",
			"0.7" => "70%",
			"0.6" => "60%",
			"0.5" => "50%",
			"0.4" => "40%",
			"0.3" => "30%",
			"0.2" => "20%",
			"0.1" => "10%",
		);

		//Getting a list of language names in the user's locale
		//Defaulting to a list of language names in English if the user's locale can't be found.
		try {
			$this->languages = Languages::getLanguageBundle()->getLanguageNames( $settings->get( 'news_language' ) );
		} catch ( MissingResourceException $e ) {
			$this->languages = Languages::getLanguageBundle()->getLanguageNames( 'en' );
		}

		$this->extraPages = "";

		//Filling in the table in General > User defined pages
		$extra_urls = $this->settings->get( "extra_pages_url" );
		if ( ! empty( $extra_urls ) ) {
			$extra_dates = $this->settings->get( "extra_pages_date" );
			foreach ( $extra_urls as $key => $page ) {
				$this->extraPages .= sprintf( '
			<tr>
				<td>
					<input type="text" name="extra_pages_url[]" value="%s"/>
				</td>
				<td>
					<input type="text" name="extra_pages_date[]" value="%s"/>
				</td>
				<td>
					<button type="button" class="btn-del-row dashicons dashicons-no"></button>
				</td>
			</tr>',
					$page, $extra_dates[ $key ], $key
				);
			}
		}
	}

	/**
	 * Generating html lists
	 *
	 * @param string $type
	 * @param string $name attribute "name" of the html element
	 * @param string $selectSelected ID of selected element
	 * @param string $selectID ID of the html select
	 * @param string $selectClass Class of the html select
	 *
	 * @return null
	 */
	public function select( $type, $name, $selectSelected = '', $selectID = '', $selectClass = '' ) {
		if ( empty( $selectSelected ) ) {
			$selectID       = $name;
			$selectSelected = lsmopt( $name );
		}
		switch ( $type ) {
			case 'priority':
				$type = $this->priorities;
				break;
			case 'changeFrequency':
				$type = $this->changeFrequencies;
				break;
			case 'language':
				$type = $this->languages;
				break;
			default:
				return null;

		}
		$select = new htmlSelect( $type, $name, $selectSelected, $selectID, $selectClass );

		echo $select->html;
	}

}

/**
 * Class htmlSelect
 * @package Lti\Sitemap
 *
 * Little class to generate html lists
 *
 */
class htmlSelect {

	public $html;
	public $id;

	public function __construct( Array $elements, $name, $selectSelected = '', $selectID = '', $selectClass = '' ) {
		if ( ! empty( $selectID ) ) {
			$selectID = sprintf( 'id="%s"', $selectID );
		}
		if ( ! empty( $selectClass ) ) {
			$selectClass = sprintf( 'class="%s"', $selectClass );
		}
		$this->html = sprintf( '<select name="%s" %s %s>', $name, $selectID, $selectClass );
		foreach ( $elements as $value => $displayValue ) {
			$selected = '';
			if ( $value == $selectSelected ) {
				$selected = 'selected="selected"';
			}
			$this->html .= sprintf( '<option value="%s" %s>%s</option>', $value, $selected, $displayValue );
		}
		$this->html .= "</select>";
	}
}
