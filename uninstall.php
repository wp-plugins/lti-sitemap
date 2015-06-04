<?php
// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.1 403 Forbidden' );
    exit();
}

//Deleting plugin options
delete_option('lti_sitemap_options');

//Deleting extra post information added by the plugin
delete_post_meta_by_key( 'lti_sitemap' );
delete_post_meta_by_key( 'lti_sitemap_post_is_news' );
