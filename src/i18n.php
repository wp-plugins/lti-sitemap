<?php namespace Lti\Sitemap;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 */
class i18n {

	/**
	 * The domain specified for this plugin.
	 *
	 * @var      string $domain The domain identifier for this plugin.
	 */
	private $domain;

	private $supportedLanguages = array( "en_US", "fr_FR" );

	/**
	 * Load the plugin text domain for translation.
	 *
	 */
	public function load_plugin_textdomain() {
		add_filter( 'plugin_locale', array( $this, 'choose_language' ), $this->domain );

		load_plugin_textdomain(
			$this->domain,
			false,
			'lti-sitemap/languages/'
		);

	}

	public function choose_language() {
		if ( $dir = @opendir( LTI_SITEMAP_PLUGIN_DIR . 'languages/' ) ) {
			$locale         = get_locale();
			$lang           = '';
			$supportedLangs = array_flip( $this->supportedLanguages );
			while ( ( $file = readdir( $dir ) ) !== false ) {
				if ( $file == '.' || $file == '..' ) {
					continue;
				}
				$m = array();
				preg_match( sprintf( '#(?<=%s-)[a-zA-Z\-_]{1,}(?<!(\.mo))#', LTI_SITEMAP_NAME ), $file, $m );
				if ( isset( $m[0] ) ) {
					if ( isset( $supportedLangs[ $m[0] ] )&&$m[0]==$locale ) {
						$lang = $m[0];
						break;
					}
				}

			}
		}
		@closedir( $dir );
		if ( ! empty( $lang ) ) {
			return $lang;
		}

		return 'en_US';
	}

	/**
	 * Set the domain equal to that of the specified domain.
	 *
	 * @param    string $domain The domain that represents the locale of this plugin.
	 */
	public function set_domain( $domain ) {
		$this->domain = $domain;
	}

}
