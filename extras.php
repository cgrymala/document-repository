<?php
/*
Plugin Name: Document Repository Network Extras
Plugin URI: http://wpmututorials.com/plugins/document-repository/
Description: Adds a Document Admin link menu to the admin bar & media handling hooks for the edit posts area. In WP networks, define RA_DOCUMENT_REPO_URL constant (repository site URL) in your wp-config to add the repository to the media library across the network. 
Author: Ron Rennick
Version: 0.5
Author URI: http://ronandandrea.com/

This plugin is a collaboration project with contributions from University of Mary Washington (http://umw.edu/)
*/
/* Copyright:   (C) 2011 Ron Rennick, All rights reserved.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
if ( ! defined( 'RA_DOCUMENT_REPO_URL' ) ) {
    if ( defined( 'RA_DOCUMENT_REPO' ) ) {
	    if ( is_numeric( RA_DOCUMENT_REPO ) ) {
	        if ( ! defined( 'RA_DOCUMENT_REPO_BLOG_ID' ) ) {
	            define( 'RA_DOCUMENT_REPO_BLOG_ID', RA_DOCUMENT_REPO );
            }
		    define( 'RA_DOCUMENT_REPO_URL', get_home_url( RA_DCOUMENT_REPO, '' ) );
	    } else {
		    define( 'RA_DOCUMENT_REPO_URL', esc_url( RA_DOCUMENT_REPO ) );
	    }
    } else if ( defined( 'RA_DOCUMENT_REPO_BLOG_ID' ) ) {
	    if ( is_numeric( RA_DOCUMENT_REPO_BLOG_ID ) ) {
		    define( 'RA_DOCUMENT_REPO_URL', get_home_url( RA_DOCUMENT_REPO_BLOG_ID, '' ) );
	    } else {
	        define( 'RA_DOCUMENT_REPO_URL', '' );
        }
    } else {
	    define( 'RA_DOCUMENT_REPO_URL', '' );
    }
}

if ( ! defined( 'RA_DOCUMENT_REPO_VERSION' ) ) {
	define( 'RA_DOCUMENT_REPO_VERSION', '0.5' );
}

add_action( 'plugins_loaded', array( 'RA_Document_Extras', 'plugins_loaded' ) );
add_action( 'admin_init', array( 'RA_Document_Extras', 'admin_init' ) );
add_action( 'admin_bar_menu', array( 'RA_Document_Extras', 'admin_bar_menu' ), 100 );
add_action( 'admin_head_ra_media_document_callback', array( 'RA_Document_Extras', 'admin_head_document' ), 99 );
add_action( 'media_upload_document', array( 'RA_Document_Extras', 'media_upload_document' ) );

if ( ! class_exists( 'RA_Document_Extras' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/classes/class-ra-document-extras.php' );
}

/*
separate function so admin_head_document is called in the iframe
*/
function ra_media_document_callback() {
	$domain    = preg_replace( '|https?://([^/]+)(/.*)?$|', '$1', get_option( 'siteurl' ) );
	$domain_qs = '';
	if ( is_multisite() ) {
		$domain_qs = '&domain=' . $domain;
	}

	$url = RA_DOCUMENT_REPO_URL . '/?media-library=1' . $domain_qs;
	?>
	<div id="document-media-library"></div>
	<script type="text/javascript">
		//<!--
		var media_library_url = '<?php echo $url; ?>';
		jQuery(document).ready(function () {
			setTimeout(function () {
				ra_query_media_library(media_library_url);
			}, 100);

			jQuery('#document-media-library').click(function (e) {
				var el = e.target;
				if (!jQuery(el).hasClass('media-library-search'))
					return;

				var qs = '';
				if (el.type == 'submit') {
					jQuery(el).parents('form:first').find(':input').not('#searchsubmit, .pagesubmit').each(function () {
						if (this.value.length)
							qs = qs + '&' + this.name + '=' + this.value;
					});
				} else { // anchor
					var tax = el.id.split(':');
					qs = '&' + tax[0] + '=' + el.name;
				}
				if (qs.length)
					ra_query_media_library(media_library_url + '&mls=1' + qs);

				e.preventDefault();
			});
		});
		//-->
	</script>
	<?php
}