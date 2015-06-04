<?php namespace Lti\Sitemap\Helpers;

/**
 * Class Bing_Helper
 *
 * @package Lti\Sitemap\Helpers
 */
class Bing_Helper {


	private $submission_url = "http://www.bing.com/ping?sitemap=%s";
	private $sitemap_url;

	public function __construct($sitemap_url){
		$this->sitemap_url = $sitemap_url;
	}

	public function get_submission_url(){
		return sprintf($this->submission_url,$this->sitemap_url);
	}

	public static function http_request($url){
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_exec( $ch );
		$result = curl_getinfo( $ch );
		curl_close( $ch );
		return $result;
	}

}