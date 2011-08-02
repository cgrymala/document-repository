<?php
/*
Plugin Name: Document Repository Custom Taxonomies
Plugin URI: http://wpmututorials.com/plugins/document-repository/
Description: Optional Custom Taxonomies for the document repository
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

add_action( 'init', array( 'RA_Document_taxonomies', 'init' ), 12 );

class RA_Document_Taxonomies {
	/*
	register the taxonomies
	*/
	function init() {
		global $ra_document_library;
		if( empty( $ra_document_library ) || !post_type_exists( $ra_document_library->post_type_name ) )
			return false;
			
		$taxes = array(
			'audience' => array(
				'name' => _x( 'Audience', 'taxonomy general name', 'document-repository' ),
				'singular_name' => _x( 'Audience', 'taxonomy singular name', 'document-repository' ),
				'search_items' =>  __( 'Search Audiences', 'document-repository' ),
				'all_items' => __( 'All Audiences', 'document-repository' ),
				'parent_item' => __( 'Parent Audience', 'document-repository' ),
				'parent_item_colon' => __( 'Parent Audience:', 'document-repository' ),
				'edit_item' => __( 'Edit Audience', 'document-repository' ),
				'update_item' => __( 'Update Audience', 'document-repository' ),
				'add_new_item' => __( 'Add New Audience', 'document-repository' ),
				'new_item_name' => __( 'New Audience Name', 'document-repository' )
			),
			'division' => array(
				'name' => _x( 'Division', 'taxonomy general name', 'document-repository' ),
				'singular_name' => _x( 'Division', 'taxonomy singular name', 'document-repository' ),
				'search_items' =>  __( 'Search Divisions', 'document-repository' ),
				'all_items' => __( 'All Divisions', 'document-repository' ),
				'parent_item' => __( 'Parent Division', 'document-repository' ),
				'parent_item_colon' => __( 'Parent Division:', 'document-repository' ),
				'edit_item' => __( 'Edit Division', 'document-repository' ),
				'update_item' => __( 'Update Division', 'document-repository' ),
				'add_new_item' => __( 'Add New Division', 'document-repository' ),
				'new_item_name' => __( 'New Division Name', 'document-repository' )
			),
			'process' => array(
				'name' => _x( 'Business Process', 'taxonomy general name', 'document-repository' ),
				'singular_name' => _x( 'Business Process', 'taxonomy singular name', 'document-repository' ),
				'search_items' =>  __( 'Search Business Processes', 'document-repository' ),
				'all_items' => __( 'All Business Processes', 'document-repository' ),
				'parent_item' => __( 'Parent Business Process', 'document-repository' ),
				'parent_item_colon' => __( 'Parent Business Process:', 'document-repository' ),
				'edit_item' => __( 'Edit Business Process', 'document-repository' ),
				'update_item' => __( 'Update Business Process', 'document-repository' ),
				'add_new_item' => __( 'Add New Business Process', 'document-repository' ),
				'new_item_name' => __( 'New Business Process Name', 'document-repository' )
			)
		); 	
	
		foreach( $taxes as $tax => $labels ) { 
			register_taxonomy( $tax, array( $ra_document_library->post_type_name ), array(
				'hierarchical' => true,
				'labels' => $labels,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => $tax )
			));
		}
		add_action( 'document_search_widget', array( 'RA_Document_taxonomies', 'document_search_widget' ) );
		add_action( 'save_post', array( 'RA_Document_taxonomies', 'save_post' ), 10, 2 );
		add_filter( 'post_updated_messages', array( 'RA_Document_taxonomies', 'post_updated_messages' ), 12 );
		add_filter( 'the_content', array( 'RA_Document_taxonomies', 'the_content' ), 9 );
		add_filter( 'document_search_query_vars', array( 'RA_Document_taxonomies', 'document_search_query_vars' ) );
		return true;
	}
	/*
	require the document to have a term from all the custom taxonomies
	*/
	function save_post( $post_id, $post ) {
		global $wpdb, $ra_document_library;
		
		if( $post->post_status != 'publish' || $post->post_type != $ra_document_library->post_type_name )
			return;
		
		$has_terms = true;
		foreach( array(	'audience', 'division', 'process' ) as $tax ) {
			$terms = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'names' ) );
			if( empty( $terms ) ) {
				$has_terms = false;
				break;
			}
		}
		if( !$has_terms ) {
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
			if( defined( 'DOING_AJAX' ) && DOING_AJAX )
				wp_redirect( add_query_arg( array( 'post_type' => $this->post_type_name ), admin_url( 'edit.php' ) ) );
			else
				wp_redirect( add_query_arg( array( 'post' => $post_id, 'action' => 'edit', 'message' => 2 ), admin_url( 'post.php' ) ) );
			exit;
		}
	}
	/*
	custom message for the requirement above
	*/
	function post_updated_messages( $messages ) {
		global $ra_document_library;
		if( !empty( $messages[$ra_document_library->post_type_name] ) )
			$messages[$ra_document_library->post_type_name][2] = __( 'All published documents require an audience, division and business process.', 'document-repository' );

		return $messages;
	}
	/*
	add custom taxonomies to the search widget
	*/
	function document_search_widget() {
		global $ra_document_library, $wp_query;

		foreach( array( 'audience', 'division', 'process' ) as $tax ) {
			$terms = get_terms( $tax );
			if( empty( $terms ) )
				continue;

			$current = !empty( $wp_query->query_vars[$tax] ) ? $wp_query->query_vars[$tax] : '';				
			$taxonomy = get_taxonomy( $tax );
			$output = "<select name='$tax' id='$tax' class='doc-lib-taxonomy'>\n";
			$output .= '\t<option value="" ' . selected( $current == '', true, false ) . ">{$taxonomy->labels->name}</option>\n";
			foreach( $terms as $term ) 
				$output .= "\t<option value='{$term->slug}' " . selected( $current, $term->slug, false ) . ">{$term->name}</option>\n";
							
			echo $output . '</select><br />';
		}
	}
	/*
	add custom taxonomies to the post content to eliminate need for custom templates
	*/
	function the_content( $content, $js_class = '' ) {
		global $post, $ra_document_library;
		if( $post->post_type != $ra_document_library->post_type_name )
			return $content;
			
		$term_content = '';
		foreach( array( 'audience', 'division', 'process' ) as $tax ) {
			$taxonomy = get_taxonomy( $tax );
			$terms = get_the_terms( 0, $tax );
			if( !empty( $terms ) ) {
				$term_content .= '<strong>' . $taxonomy->labels->name . '</strong>: ';
				if( !$ra_document_library->media_library ) {
					$terms = get_the_term_list( 0, $tax, '', ',', ' ' );
					if( !empty( $terms ) )
						$term_content .= $terms;
						
					continue;
				} 
				$term_list = array();
				foreach( $terms as $term )
					$term_list[] = '<a href="#" class="' . esc_attr( $ra_document_library->js_class ) . '" id="' . esc_attr( $tax ) . ':' . $post->ID . '" name="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a>';
				$term_content .= '<span class="doc-terms">' . implode( ', ', $term_list ) . '</span>';
			}
		}
		if( !empty( $term_content ) )
			$content .= '<div class="doc-lib-meta">' . $term_content . '</div>';
		return $content;
	}
	/*
	flush rewrite rules on activation
	*/
	function rewrite_flush() {
		if( taxonomy_exists( 'audience' ) || self::init() )
			flush_rewrite_rules();
	}
	/*
	add query vars for media library search
	*/
	function document_search_query_vars( $vars ) {
		$vars = array_merge( $vars, array( 'audience', 'division', 'process' ) );
		return array_unique( $vars );
	}
}

register_activation_hook( __FILE__, array( 'RA_Document_Taxonomies', 'rewrite_flush' ) );
