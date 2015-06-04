<?php namespace Lti\Sitemap\Generators;

use Lti\Sitemap\Helpers\ICanHelp;
use Lti\Sitemap\Helpers\Plugin_Query;
use Lti\Sitemap\Plugin\Plugin_Settings;
use Lti\Sitemap\Plugin\Postbox_Values;
use Lti\Sitemap\Sitemap;
use Lti\Sitemap\SiteMapIndex;
use Lti\Sitemap\SitemapNews;
use Lti\Sitemap\SitemapUrl;
use Lti\Sitemap\SiteMapUrlSet;

interface ICanGenerateSitemaps {
	public function get();
}

/**
 * Class Sitemap_Generator
 * @package Lti\Sitemap\Generators
 */
abstract class Sitemap_Generator implements ICanGenerateSitemaps {

	/**
	 * @var Plugin_Settings
	 */
	protected $settings;
	/**
	 * @var ICanHelp|\Lti\Sitemap\Helpers\Wordpress_Helper
	 */
	protected $helper;

	protected $filenamePrefix = "sitemap-";

	/**
	 * @param Plugin_Settings $settings
	 * @param ICanHelp|\Lti\Sitemap\Helpers\Wordpress_Helper $helper
	 */
	public function __construct( Plugin_Settings $settings, ICanHelp $helper ) {
		$this->settings = $settings;
		$this->helper   = $helper;
		$this->filename = $helper->home_url() . $this->filenamePrefix;
		$this->query    = new Plugin_Query();
	}

	public function get_stylesheet_url() {
		/**
		 * Allow overriding of the XSL stylesheet location
		 *
		 * @api string The XSL stylesheet URL
		 */
		return apply_filters( 'lti_xsl_sitemap_url',
			plugin_dir_url( LTI_SITEMAP_PLUGIN_DIR . 'assets/dist/xsl/sitemap.xsl' ) . 'sitemap.xsl' );
	}

}

/**
 * Class Sitemap_Generator_Authors
 * @package Lti\Sitemap\Generators
 */
class Sitemap_Generator_Authors extends Sitemap_Generator {
	public function get() {
		$sitemap_pages = new SiteMapUrlSet();
		$sitemap_pages->addStylesheet( $this->get_stylesheet_url() );
		$result = $this->query->get_authors( $this->helper->get_supported_post_types() );

		$changeFrequency = $this->settings->get( 'change_frequency_authors' );
		$priority        = $this->settings->get( 'priority_authors' );

		if ( ! empty( $result ) ) {
			foreach ( $result as $entry ) {
				$sitemap_pages->add( new SitemapUrl( get_author_posts_url( $entry->ID ),
					lti_iso8601_date( $entry->lastmod ),
					$changeFrequency, $priority ) );
			}
		}

		return $sitemap_pages->output();
	}
}

/**
 * Class Sitemap_Generator_Pages
 * @package Lti\Sitemap\Generators
 */
class Sitemap_Generator_Pages extends Sitemap_Generator {
	public function get() {
		$sitemap_pages = new SiteMapUrlSet();
		$sitemap_pages->addStylesheet( $this->get_stylesheet_url() );

		$changeFrequency = $this->settings->get( 'change_frequency_pages' );
		$priority        = $this->settings->get( 'priority_pages' );

		$result = $this->query->get_pages();
		if ( ! empty( $result ) ) {
			foreach ( $result as $entry ) {
				$sitemap_pages->add( new SitemapUrl( get_permalink( $entry->ID ), lti_iso8601_date( $entry->lastmod ),
					$changeFrequency, $priority ) );
			}
		}

		return $sitemap_pages->output();
	}
}

/**
 * Class Sitemap_Generator_Main
 * @package Lti\Sitemap\Generators
 */
class Sitemap_Generator_Main extends Sitemap_Generator {
	public function get() {
		$sitemap_main = new SiteMapUrlSet();
		$sitemap_main->addStylesheet( $this->get_stylesheet_url() );

		if ( $this->settings->get( 'content_frontpage' ) == true ) {
			$sitemap_main->add( new SitemapUrl( $this->helper->home_url(),
				lti_iso8601_date( get_lastpostmodified( 'gmt' ) ), $this->settings->get( 'change_frequency_frontpage' ),
				$this->settings->get( 'priority_frontpage' ) ) );
		}

		if ( $this->settings->get( 'content_user_defined' ) == true ) {
			$other_pages = $this->settings->get( 'extra_pages_url' );
			if ( ! is_null( $other_pages ) && ! empty( $other_pages ) ) {
				$other_pages_mod_date = $this->settings->get( 'extra_pages_date' );
				$changeFrequency      = $this->settings->get( 'change_frequency_user_defined' );
				$priority             = $this->settings->get( 'priority_user_defined' );

				foreach ( $other_pages as $key => $page ) {
					$sitemap_main->add( new SitemapUrl( $page, lti_iso8601_date( $other_pages_mod_date[ $key ] ),
						$changeFrequency, $priority ) );
				}
			}
		}

		return $sitemap_main->output();
	}

}

/**
 * Class Sitemap_Generator_Index
 * @package Lti\Sitemap\Generators
 */
class Sitemap_Generator_Index extends Sitemap_Generator {

	public function get() {
		$sitemap_index = new SiteMapIndex();
		$sitemap_index->addStylesheet( $this->get_stylesheet_url() );
		if ( $this->settings->get( 'content_frontpage' ) === true ) {
			$sitemap_index->add( new Sitemap( $this->filename . 'main.xml',
				lti_iso8601_date( get_lastpostmodified( 'gmt' ) ) ) );
		}

		if ( $this->settings->get( 'content_posts' ) === true ) {
			$filterByMonthParam = ( $this->settings->get( 'content_posts_display' ) == 'month' );
			$filterNormalParam  = ( $this->settings->get( 'content_posts_display' ) == 'normal' );
			if ( $filterNormalParam === false ) {
				if ( $filterByMonthParam === true ) {
					$result = $this->query->get_posts_info_month();
					if ( ! empty( $result ) ) {
						foreach ( $result as $entry ) {
							$sitemap_index->add( new Sitemap( sprintf( '%sposts-%s-%s.xml',
								$this->filename, $entry->year, str_pad( $entry->month, 2, '0', STR_PAD_LEFT ) ),
								lti_iso8601_date( $entry->lastmod ) ) );
						}
					}
				} else {
					$result = $this->query->get_posts_info_year();
					if ( ! empty( $result ) ) {
						foreach ( $result as $entry ) {
							$sitemap_index->add( new Sitemap( sprintf( '%sposts-%s.xml',
								$this->filename, $entry->year ),
								lti_iso8601_date( $entry->lastmod ) ) );
						}
					}
				}

			} else {
				$result = $this->query->get_posts_info();
				if ( ! empty( $result ) ) {
					$sitemap_index->add( new Sitemap( sprintf( "%s%s.xml", $this->filename, 'posts' ),
						lti_iso8601_date( $result[0]->lastmod ) ) );
				}
			}
		}

		if ( $this->settings->get( 'content_pages' ) === true ) {
			$result = $this->query->get_pages_info();
			if ( ! empty( $result ) ) {
				$sitemap_index->add( new Sitemap( $this->filename . 'pages.xml',
					lti_iso8601_date( $result[0]->lastmod ) ) );

			}
		}

		if ( $this->settings->get( 'content_authors' ) === true ) {
			$result = $this->query->get_authors_info( $this->helper->get_supported_post_types() );
			if ( ! empty( $result ) ) {
				$sitemap_index->add( new Sitemap( $this->filename . 'authors.xml',
					lti_iso8601_date( $result[0]->lastmod ) ) );

			}
		}

		if ( $this->settings->get( 'content_news_support' ) === true ) {
			$result = $this->query->get_news_info();
			if ( ! empty( $result ) && ( !is_null( $result[0]->lastmod ) ) ) {
				$sitemap_index->add( new Sitemap( $this->filename . 'news.xml',
					lti_iso8601_date( $result[0]->lastmod ) ) );

			}
		}

		return $sitemap_index->output();
	}

}

/**
 * Class Sitemap_Generator_Posts
 * @package Lti\Sitemap\Generators
 */
class Sitemap_Generator_Posts extends Sitemap_Generator {

	public function get() {
		$sitemap_posts = new SiteMapUrlSet();
		$sitemap_posts->addStylesheet( $this->get_stylesheet_url() );

		$month           = $this->settings->get( 'month' );
		$year            = $this->settings->get( 'year' );
		$changeFrequency = $this->settings->get( 'change_frequency_posts' );
		$priority        = $this->settings->get( 'priority_posts' );
		$result          = $this->query->get_posts( $month, $year );

		if ( ! empty( $result ) ) {
			$postsIndex = array();
			foreach ( $result as $entry ) {
				$postsIndex[ $entry->ID ] = $sitemap_posts->add( new SitemapUrl( get_permalink( $entry->ID ),
					lti_iso8601_date( $entry->lastmod ),
					$changeFrequency, $priority ) );
			}

			if ( $this->settings->get( 'content_images_support' ) ) {
				$this->add_images( $this->query->get_posts_attachment_images(), $sitemap_posts, $postsIndex );

				//Created this method because of some confusion on how featured images were attached. Not used anymore
				/*
				if ( $this->settings->get( 'content_images_featured' ) ) {
					$this->add_images( $this->query->get_posts_thumbnail_images(), $sitemap_posts, $postsIndex );
				}
				*/
			}
		}

		return $sitemap_posts->output();

	}

	/**
	 * @param array $image_data
	 * @param \Lti\Sitemap\SiteMapUrlSet $xml
	 * @param $postsIndex
	 */
	private function add_images( Array $image_data, SiteMapUrlSet $xml, Array $postsIndex ) {

		if ( ! empty( $image_data ) ) {
			foreach ( $image_data as $image ) {
				if ( isset( $postsIndex[ $image->post_id ] ) ) {
					$license_url = $image_url = '';
					if ( preg_match( '#^http(s?)\://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(?:/\S*)?$#',
						$image->license,
						$matches ) ) {
						$license_url = $matches[0];
					}
					$sitemap_url_index = $postsIndex[ $image->post_id ];

					$xml->addImage( $sitemap_url_index, $this->wp_get_attachment_url( $image ), $image->caption, '',
						$image->title,
						$license_url );

				}
			}
		}
	}

	/**
	 * We copied the wp_get_attachment_url method without the costly database lookup,
	 * since we've already done it.
	 *
	 *
	 * @param \StdClass $post
	 *
	 * @return bool|mixed|string|void
	 */
	private function wp_get_attachment_url( $post ) {
		$url  = '';
		$file = $post->rel_path;
		// Get upload directory.
		if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) {
			// Check that the upload base exists in the file location.
			if ( 0 === strpos( $file, $uploads['basedir'] ) ) {
				// Replace file location with url location.
				$url = str_replace( $uploads['basedir'], $uploads['baseurl'], $file );
			} elseif ( false !== strpos( $file, 'wp-content/uploads' ) ) {
				$url = $uploads['baseurl'] . substr( $file, strpos( $file, 'wp-content/uploads' ) + 18 );
			} else {
				// It's a newly-uploaded file, therefore $file is relative to the basedir.
				$url = $uploads['baseurl'] . "/$file";
			}
		}

		/*
		 * If any of the above options failed, Fallback on the GUID as used pre-2.7,
		 * not recommended to rely upon this.
		 */
		if ( empty( $url ) ) {
			$url = $post->guid;
		}

		// On SSL front-end, URLs should be HTTPS.
		if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $GLOBALS['pagenow'] ) {
			$url = set_url_scheme( $url );
		}

		/**
		 * Filter the attachment URL.
		 *
		 * @since 2.1.0
		 *
		 * @param string $url URL for the given attachment.
		 * @param int $post_id Attachment ID.
		 */
		$url = apply_filters( 'wp_get_attachment_url', $url, $post->ID );

		if ( empty( $url ) ) {
			return false;
		}

		return $url;
	}

}

/**
 * Class Sitemap_Generator_News
 * @package Lti\Sitemap\Generators
 */
class Sitemap_Generator_News extends Sitemap_Generator {

	public function get_stylesheet_url() {
		/**
		 * Allow overriding of the XSL stylesheet location
		 *
		 * @api string The XSL stylesheet URL
		 */
		return apply_filters( 'lti_xsl_sitemap_news_url',
			plugin_dir_url( LTI_SITEMAP_PLUGIN_DIR . 'assets/dist/xsl/sitemap-news.xsl' ) . 'sitemap-news.xsl' );
	}

	public function get() {
		$sitemap_news = new SiteMapUrlSet();
		$sitemap_news->addStylesheet( $this->get_stylesheet_url() );

		$result = $this->query->get_news();

		if ( ! empty( $result ) ) {
			$postsIndex = array();
			foreach ( $result as $entry ) {
				/**
				 * @var $news \Lti\Sitemap\Plugin\Postbox_Values
				 */
				$news = unserialize( $entry->meta_value );

				if ( ( $news instanceof Postbox_Values ) === false ) {
					continue;
				}
				$postsIndex[ $entry->ID ] = $sitemap_news->add( new SitemapUrl( get_permalink( $entry->ID ) ) );

				$newsNode = new SitemapNews( $this->settings->get( 'news_publication' ),
					$this->settings->get( 'news_language' ) );

				$access = $news->get( 'news_access_type' );
				if ( $access != 'Full' ) {
					$newsNode->set_access( $access );
				}

				$genres = $news->get_genres();
				if ( ! empty( $genres ) ) {
					$newsNode->set_genres( $genres );
				}

				$newsNode->set_publication_date( lti_iso8601_date( $entry->creation_date ) );

				$title = $news->get( 'news_title' );
				if ( ! is_null( $title ) ) {
					$newsNode->set_title( $title );
				} else {
					$newsNode->set_title( $entry->post_title );
				}

				$keywords = $news->get( 'news_keywords' );
				if ( ! is_null( $keywords ) ) {
					$newsNode->set_keywords( $keywords );
				}

				$stock_tickers = $news->get( 'news_stock_tickers' );
				if ( ! is_null( $stock_tickers ) ) {
					$newsNode->set_stock_tickers( $stock_tickers );
				}
				$sitemap_news->addNewsNode( $postsIndex[ $entry->ID ], $newsNode );
			}

		}

		return $sitemap_news->output();
	}
}
