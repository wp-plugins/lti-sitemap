<?php namespace Lti\Sitemap\Helpers;

/**
 * Class Query_Manager
 *
 * Builds SQL queries
 *
 * @package Lti\Sitemap\Helpers
 */
class Query_Manager {

	private $columns = array();
	private $tables = array();
	private $where = array();
	private $join = array();
	private $groupBy = array();
	private $orderBy = array();
	public static $RESULTSET_LIMIT = 5000;

	public function select( Array $columns ) {
		$this->columns = array_unique( array_merge( $this->columns, $columns ) );
	}

	public function from( Array $tables ) {
		$this->tables = array_unique( array_merge( $this->tables, $tables ) );
	}

	public function where( $column, $comparator, $value ) {
		if ( is_string( $value ) ) {
			$value = sprintf( "'%s'", esc_sql( $value ) );
		}
		$this->where[] = array( " AND", $column, $comparator, $value );
	}

	public function whereIn( $column, Array $values ) {
		$this->where[] = array(
			" AND",
			$column,
			"IN",
			sprintf( '(\'%s\')', implode( "','", array_map( 'esc_sql', $values ) ) )
		);
	}

	public function whereInSelect( $column, Query_Manager $select ) {

		$subQuery      = $select->build( false );
		$this->where[] = array(
			" AND",
			$column,
			" IN",
			sprintf( '(%s)', $subQuery )
		);
	}

	public function groupBy( Array $columns ) {
		$this->groupBy = array_unique( array_merge( $this->groupBy, $columns ) );
	}

	public function orderBy( Array $columns ) {
		$this->orderBy = array_unique( array_merge( $this->orderBy, $columns ) );
	}

	public function join( $table, $column, $comparator = '', $value = '' ) {
		$this->join[] = array( $table, $column, $comparator, $value );
	}


	public function build( $limit_results = true ) {
		if ( empty( $this->columns ) ) {
			$this->columns = array( "*" );
		}
		$query = "SELECT " . implode( ',', $this->columns );
		$query .= " FROM " . implode( ',', $this->tables );
		if ( ! empty( $this->join ) ) {
			foreach ( $this->join as $join ) {
				if ( is_array( $join[1] ) ) {
					$clauseArray = array();
					$query .= ' JOIN ' . $join[0] . ' ON (';
					foreach ( $join[1] as $clauses ) {
						//Any clauses without dotted notation will be quoted
						if ( strpos( $clauses[2], '.' ) === false && is_string( $clauses[2] ) ) {
							$clauses[2] = sprintf( "'%s'", esc_sql( $clauses[2] ) );
						}
						$clauseArray[] = implode( '', $clauses );
					}
					$query .= implode( ' AND ', $clauseArray ) . ')';
				} else {
					$query .= " JOIN " . $join[0] . " ON (" . $join[1] . $join[2] . $join[3] . ')';
				}
			}
			if ( ! empty( $this->where ) ) {
				foreach ( $this->where as $where ) {
					$query .= implode( ' ', $where );
				}
			}
		} else if ( ! empty( $this->where ) ) {
			$first = array_shift( $this->where );
			$query .= " WHERE " . $first[1] . $first[2] . $first[3];
			if ( ! empty( $this->where ) ) {
				foreach ( $this->where as $where ) {
					$query .= implode( ' ', $where );
				}
			}
		}

		if ( ! empty( $this->groupBy ) ) {
			$query .= " GROUP BY " . implode( ',', $this->groupBy );
		}

		if ( ! empty( $this->orderBy ) ) {
			$query .= " ORDER BY " . implode( ',', $this->orderBy );
		}

		if ( $limit_results ) {
			$query .= " LIMIT " . self::$RESULTSET_LIMIT;
		}

		return $query;

	}

	public function printQuery( $sql_query ) {
		$sql_query = preg_replace_callback( "#( FROM | WHERE | AND | OR | SET | VALUES\s?| (LEFT|RIGHT|OUTER|INNER|FULL) JOIN | JOIN | HAVING | ORDER BY | GROUP BY )#i",
			create_function( '$matches', "return \"<br/>\".\$matches[0];" ), $sql_query );

		return str_repeat( "=", 40 ) . "<br/>" . $sql_query . ";<br/>" . str_repeat( "=", 40 ) . "<br/>";
	}
}

class Plugin_Query {

	/**
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * @var Query_Manager The query;
	 */
	private $q;

	private static $instance;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->q    = self::getQueryManager();
	}

	public static function getQueryManager() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Query_Manager();
		}

		return self::$instance;
	}

	private function flush() {
		self::$instance = $this->q = new Query_Manager();
	}

	public function get_results( $debug = false ) {

		$query = $this->q->build();
		$this->flush();

		if ( $debug === true ) {
			print_r( $this->q->printQuery( $query ) );

			return false;
		}

		return $this->wpdb->get_results( $query );
	}

	public function get_posts_info_month() {
		$this->q->select( array(
			'YEAR(p.post_date_gmt)    AS `year`',
			'MONTH(p.post_date_gmt)   AS `month`',
			'MAX(p.post_modified_gmt) as `lastmod`'
		) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->where( 'p.post_type', '=', 'post' );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->groupBy( array( 'YEAR(p.post_date_gmt)', 'MONTH(p.post_date_gmt)' ) );
		$this->q->orderBy( array( 'p.post_date_gmt DESC' ) );

		return $this->get_results();
	}

	public function get_posts_info_year() {
		$this->q->select( array(
			'YEAR(p.post_date_gmt)    AS `year`',
			'MAX(p.post_modified_gmt) as `lastmod`'
		) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->where( 'p.post_type', '=', 'post' );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->groupBy( array( 'YEAR(p.post_date_gmt)' ) );
		$this->q->orderBy( array( 'p.post_date_gmt DESC' ) );

		return $this->get_results();
	}

	public function get_posts_info() {
		$this->q->select( array(
			'MAX(p.post_modified_gmt) as `lastmod`'
		) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->where( 'p.post_type', '=', 'post' );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->groupBy( array( 'p.post_date_gmt' ) );

		return $this->get_results();
	}

	public function get_posts( $month = null, $year = null ) {
		$this->q->select( array( 'p.ID', 'p.post_modified_gmt as `lastmod`' ) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->where( 'p.post_type', '=', 'post' );
		$this->q->where( 'p.post_status', '=', 'publish' );
		if ( ! is_null( $year ) ) {
			$this->q->where( 'YEAR(p.post_date_gmt)', '=', $year );
		}
		if ( ! is_null( $month ) ) {
			$this->q->where( 'MONTH(p.post_date_gmt)', '=', $month );

		}
		$this->q->orderBy( array( 'p.post_date_gmt DESC' ) );

		return $this->get_results();
	}

	/**
	 * Getting images only through the posts table
	 *
	 * Decided not to use it because we can't get the storage path of the image that way.
	 * (At least, as far as I could see).
	 */
	//	public function get_posts_attachment_images() {
	//		$this->q->select( array(
	//			'p2.id as post_id',
	//			'p1.post_content as license',
	//			'p1.post_title as title',
	//			'p1.post_excerpt as caption',
	//			'p1.id as image_id'
	//		) );
	//		$this->q->from( array( $this->wpdb->posts . ' p1' ) );
	//		$this->q->join( $this->wpdb->posts . ' p2', 'p1.post_parent', '=', 'p2.ID' );
	//		$this->q->where( 'p2.post_type', '=', 'post' );
	//		$this->q->where( 'p2.post_status', '=', 'publish' );
	//		$this->q->where( 'p1.post_type', '=', 'attachment' );
	//		$this->q->orderBy( array( 'p2.post_date_gmt DESC' ) );
	//
	//		return $this->get_results();
	//	}

	public function get_posts_attachment_images() {
		$this->q->select( array(
			'post_parent as post_id',
			'p.ID',
			'p.post_content as license',
			'p.post_title as title',
			'p.post_excerpt as caption',
			'pm.meta_value as rel_path',
			'p.guid'
		) );
		$this->q->from( array( $this->wpdb->postmeta . ' pm' ) );
		$this->q->join( $this->wpdb->posts . ' p', 'pm.post_id', '=', 'p.ID' );
		$this->q->where( 'pm.meta_key', '=', '_wp_attached_file' );
		$this->q->where( 'meta_value', '!=', '' );
		$this->q->where( 'post_parent', '>', 0 );
		$this->q->orderBy( array( 'p.post_date_gmt DESC' ) );

		return $this->get_results();
	}

	/**
	 * NOT USED at the moment.
	 *
	 * @return bool|mixed
	 */
	public function get_posts_thumbnail_images() {
		$this->q->select( array(
			'post_id',
			'p.guid as url',
			'p.post_content as license',
			'p.post_title as title',
			'p.post_excerpt as caption'
		) );
		$this->q->from( array( $this->wpdb->postmeta . ' pm' ) );
		$this->q->join( $this->wpdb->posts . ' p', 'pm.meta_value', '=', 'p.ID' );
		$this->q->where( 'pm.meta_key', '=', '_thumbnail_id' );
		$this->q->where( 'meta_value', '>', 0 );
		$this->q->where( 'post_parent', '>', 0 );
		$this->q->orderBy( array( 'p.post_date_gmt DESC' ) );

		return $this->get_results();
	}


	public function get_pages() {
		$this->q->select( array( 'p.ID', 'p.post_modified_gmt as `lastmod`' ) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->where( 'p.post_type', '=', 'page' );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->orderBy( array( 'p.post_date_gmt DESC' ) );

		return $this->get_results();
	}

	public function get_pages_info() {
		$this->q->select( array( 'COUNT(p.ID)', 'MAX(p.post_modified_gmt) as `lastmod`' ) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->where( 'p.post_type', '=', 'page' );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->groupBy( array( 'p.ID' ) );

		return $this->get_results();
	}

	public function get_authors_info( $supported_post_types ) {

		$this->q->select( array( 'COUNT(u.ID)', 'MAX(p.post_modified_gmt) as `lastmod`' ) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->join( $this->wpdb->users . ' u', 'u.ID', '=', 'post_author' );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->whereIn( 'p.post_type', $supported_post_types );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->groupBy( array( 'u.ID' ) );

		return $this->get_results();
	}

	public function get_authors( $supported_post_types ) {

		$this->q->select( array( 'u.ID', 'MAX(p.post_modified_gmt) as `lastmod`' ) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->join( $this->wpdb->users . ' u', 'u.ID', '=', 'post_author' );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->whereIn( 'p.post_type', $supported_post_types );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->groupBy( array( 'u.ID' ) );

		return $this->get_results();
	}

	public function get_news_info( $time_delay = 2592000 ) {

		$time = apply_filters( 'lti_news_time_delay', $time_delay );

		$some_time_ago = date( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - $time );

		$this->q->select( array( 'MAX(p.post_modified_gmt) as `lastmod`' ) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->join( $this->wpdb->postmeta. ' pm',
			array(
				array( 'p.ID', '=', 'pm.post_id' ),
				array( 'pm.meta_key', '=', 'lti_sitemap' )
			) );
		$this->q->join( $this->wpdb->postmeta.' pm2',
			array(
				array( 'p.ID', '=', 'pm2.post_id' ),
				array( 'pm2.meta_key', '=', 'lti_sitemap_post_is_news' )
			) );
		$this->q->where( 'p.post_modified_gmt', '>', $some_time_ago );
		$this->q->where( 'p.post_type', '=', 'post' );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->orderBy( array( 'p.post_date_gmt DESC' ) );

		return $this->get_results();
	}

	public function get_news( $time_delay = 2592000 ) {
		$time = apply_filters( 'lti_news_time_delay', $time_delay );

		$some_time_ago = date( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - $time );

		$this->q->select( array( 'p.ID','p.post_date_gmt as `creation_date`', 'pm.meta_value', 'p.post_title' ) );
		$this->q->from( array( $this->wpdb->posts . ' p' ) );
		$this->q->join( $this->wpdb->postmeta.' pm',
			array(
				array( 'p.ID', '=', 'pm.post_id' ),
				array( 'pm.meta_key', '=', 'lti_sitemap' )
			) );
		$this->q->join( $this->wpdb->postmeta.' pm2',
			array(
				array( 'p.ID', '=', 'pm2.post_id' ),
				array( 'pm2.meta_key', '=', 'lti_sitemap_post_is_news' )
			) );
		$this->q->where( 'p.post_type', '=', 'post' );
		$this->q->where( 'p.post_status', '=', 'publish' );
		$this->q->where( 'p.post_password', '=', '' );
		$this->q->where( 'p.post_modified_gmt', '>', $some_time_ago );
		$this->q->orderBy( array( 'p.post_date_gmt DESC' ) );

		return $this->get_results();
	}
}