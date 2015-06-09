<?php

/**
 * Appears when we click on the help button. (Top-right of the admin screen)
 *
 * Class Lti_Sitemap_Help_Menu
 *
 * @see \Lti\Sitemap\Admin::wp_help_menu
 */
class Lti_Sitemap_Help_Menu {
	public function __construct() {

	}

	public function welcome_tab() {
		return sprintf( '<p>%s</p><p>%s</p><p><strong>%s</strong></p>', lsmint( 'general_hlp_welcome_1' ),
			lsmint( 'general_hlp_welcome_2' ), lsmint( 'general_hlp_welcome_3' ) );
	}

	public function general_tab() {
		return sprintf( '<p>%s</p><p>%s</p>', lsmint( 'general_hlp_general1' ), lsmint( 'general_hlp_general2' ) );
	}

	public function google_tab() {
		return sprintf( '<p>%s</p><p>%s</p>', lsmint( 'general_hlp_google1' ), lsmint( 'general_hlp_google2' ) );
	}

	public function bing_tab() {
		return sprintf( '<p>%s</p><p>%s</p>', lsmint( 'general_hlp_bing1' ), lsmint( 'general_hlp_bing2' ) );
	}

	public function news_tab() {
		return sprintf( '<p>%s</p><p>%s</p>', lsmint( 'general_hlp_news1' ), lsmint( 'general_hlp_news2' ) );
	}


	public function sidebar() {
		return '<p><strong>' . lsmint( 'general_hlp_contribute' ) . '</strong></p>' .
		       '<p><a href="https://github.com/DeCarvalhoBruno/lti-wp-sitemap" target="_blank">' . lsmint( 'general_hlp_github' ) . '</a></p>';
	}
}