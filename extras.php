<?php
/*
Plugin Name: Document Repository Network Extras
Plugin URI: http://wpmututorials.com/plugins/document-repository/
Description: Adds a Document Admin link menu to the admin bar & media handling hooks for the edit posts area. In WP networks, define RA_DOCUMENT_REPO_URL constant (repository site URL) in your wp-config to add the repository to the media library across the network. 
Author: Ron Rennick
Version: 0.2.3
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
if( !defined( 'RA_DOCUMENT_REPO_URL' ) )
	define( 'RA_DOCUMENT_REPO_URL', '' );
if( !defined( 'RA_DOCUMENT_REPO_VERSION' ) )
	define( 'RA_DOCUMENT_REPO_VERSION', '0.2.3' );

add_action( 'plugins_loaded', array( 'RA_Document_Extras', 'plugins_loaded' ) );
add_action( 'admin_init', array( 'RA_Document_Extras', 'admin_init' ) );
add_action( 'admin_bar_menu', array( 'RA_Document_Extras', 'admin_bar_menu' ), 100 );
add_action( 'admin_head_ra_media_document_callback', array( 'RA_Document_Extras', 'admin_head_document' ), 99 );
add_action( 'media_upload_document', array( 'RA_Document_Extras', 'media_upload_document' ) );
add_filter( 'media_buttons_context', array( 'RA_Document_Extras', 'media_buttons_context' ) );

class RA_Document_Extras {
	/*
	load text domain
	*/
	function plugins_loaded() {
		if( !class_exists( 'RA_Document_Post_Type' ) )
			load_plugin_textdomain( 'document-repository', false, '/languages/' );
	}
	/*
	enqueue script for the edit post area
	*/
	function admin_init() {
		if( !isset( $_GET['post'] ) && !isset( $_GET['post_type'] ) )
			return;

		$pagename = basename( $_SERVER['REQUEST_URI'] );
		if( ( $index = strpos( $pagename, '?' ) ) !== false )
			$pagename = substr( $pagename, 0, $index );
			
		if( $pagename == 'post-new.php' || $pagename == 'post.php' )
			wp_enqueue_script( 'ra-document', plugin_dir_url( __FILE__ ) . 'js/media.js', array( 'jquery' ), RA_DOCUMENT_REPO_VERSION, true );
	}
	/*
	add the admin bar menu item
	*/
	function admin_bar_menu() {
		global $wp_admin_bar;
	
		if( !is_admin() || !is_admin_bar_showing() )
			return;
	
		$user = get_current_user();
		if( !$user->doc_role && ( !defined( 'RA_DOCUMENT_REPO_BLOG_ID' ) || !current_user_can_for_blog( 'manage_options', RA_DOCUMENT_REPO_BLOG_ID ) ) && !current_user_can( 'manage_options' ) )
			return;

		$wp_admin_bar->add_menu( array( 'id' => 'umw', 'title' => __( 'Document Admin', 'document-repository' ), 'href' => RA_DOCUMENT_REPO_URL . '/wp-admin/edit.php?post_type=umw_document' ) );
	}
	/*
	load the front end of the document repository in the edit post media popup to allow inserting links to documents into posts
	*/ 
	function media_upload_document() {
		wp_iframe( 'ra_media_document_callback' );
		exit;
	}
	/*
	add the document media button to the media button row in the post editor
	*/
	function media_buttons_context( $context ) {
		global $post_type;
		if( $post_type == 'umw_document' )
			return $context;
		
		$media_button = preg_replace( '|^(.*src=[\'"])' . admin_url( '/') . '(.*)$|', ' $1$2', _media_button( __( 'Insert Document', 'document-repository' ), plugin_dir_url( __FILE__ ) . 'images/doc.jpg', 'document' ) );
		return $context . $media_button;
	}
	function admin_head_document() { ?>
<style type="text/css">
#document-media-library, #document-media-library .widget {
	padding: 5px;
}
#document-media-library .widget .media-library, #document-media-library .widget .media-library-extras {
	float: left;
	width: 48%;
}
#document-media-library .widget .media-library-extras {
	float: right;
}
#document-media-library .widget input[type=submit] {
	margin: 10px 0 10px 50px;
}
#document-media-library .ml-post {
	border-bottom: 1px solid #dfdfdf;
	padding: 8px;
	margin: 0;
}
#document-media-library span.doc-terms {
	margin: 0 6px 6px 0;
}
#document-media-library .tablenav form {
	display: inline;
}
</style>
<script type="text/javascript" src="<?php echo plugin_dir_url( __FILE__ ) . 'js/media.js?ver=' . RA_DOCUMENT_REPO_VERSION; ?>"></script>
<?php	}
}
/*
separate function so admin_head_document is called in the iframe
*/
function ra_media_document_callback() {
	$domain = preg_replace( '|https?://([^/]+)(/.*)?$|', '$1', get_option( 'siteurl' ) );
	$domain_qs = '';
	if( is_multisite() )
		$domain_qs = '&domain=' . $domain;

	$url = RA_DOCUMENT_REPO_URL . '/' . '?media-library=1' . $domain_qs;
?>
<div id="document-media-library"></div>
<script type="text/javascript"> 
//<!--
var media_library_url='<?php echo $url; ?>';
jQuery(document).ready(function() {
	jQuery.getJSON(media_library_url, function(result) {
		if(result.length)
			jQuery('#document-media-library').html(result);
	});
});
jQuery(document).ready(function() {
	jQuery('#document-media-library').click(function(e){
		var el=e.target;
		if(!jQuery(el).hasClass('media-library-search'))
			return;
			
		var qs='';
		if(el.type == 'submit') { 
			jQuery(el).parents('form:first').find(':input').not('#searchsubmit, .pagesubmit').each(function(){
				if(this.value.length)
					qs = qs + '&' + this.name + '=' + this.value; 
			});
		} else { // anchor
			var tax = el.id.split(':');
			qs = '&' + tax[0] + '=' + el.name;
		}
		if(qs.length) {
			url=media_library_url + '&mls=1' + qs;
			jQuery.getJSON(url, function(result) {
				if(result.length)
					jQuery('#document-media-library').html(result);
			});
		}
		e.preventDefault();
	});
});
//-->
</script>
<?php
}
