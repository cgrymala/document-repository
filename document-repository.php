<?php
/*
Plugin Name: Document Repository
Plugin URI: http://wpmututorials.com/plugins/document-repository/
Description: Turn a WordPress site into a revisioned document repository.
Author: Ron Rennick
Version: 0.2.5
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
if( ! defined( 'RA_DOCUMENT_REPO_VERSION' ) )
	define( 'RA_DOCUMENT_REPO_VERSION', '0.2.5' );

if ( ! class_exists( 'RA_Document_Post_Type' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '/classes/class-ra-document-post-type.php';
}

$ra_document_library = new RA_Document_Post_type();

register_activation_hook( __FILE__, array( $ra_document_library, 'rewrite_flush' ) );

if ( ! class_exists( 'RA_Document_Widget_Search' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '/classes/class-ra-document-widget-search.php';
}

function register_ra_document_search_widget() {
	unregister_widget( 'WP_Widget_Search' );
	register_widget( 'RA_Document_Widget_Search' );
}
add_action( 'widgets_init', 'register_ra_document_search_widget' );
