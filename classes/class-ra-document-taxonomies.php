<?php

class RA_Document_Taxonomies {
	/**
	 * Holds the version number for use with various assets
	 *
	 * @since  0.5
	 * @access public
	 * @var    string
	 */
	public $version = '0.5';
	/**
	 * Holds the class instance.
	 *
	 * @since   0.5
	 * @access    private
	 * @var        \RA_Document_Taxonomies
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   0.5
	 * @return    \RA_Document_Taxonomies
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$className      = __CLASS__;
			self::$instance = new $className;
		}

		return self::$instance;
	}

	/*
	register the taxonomies
	*/
	function init() {
		global $ra_document_library;
		if ( empty( $ra_document_library ) || ! post_type_exists( $ra_document_library->post_type_name ) ) {
			return false;
		}

		$taxes = array(
			'audience' => array(
				'name'              => _x( 'Audience', 'taxonomy general name', 'document-repository' ),
				'singular_name'     => _x( 'Audience', 'taxonomy singular name', 'document-repository' ),
				'search_items'      => __( 'Search Audiences', 'document-repository' ),
				'all_items'         => __( 'All Audiences', 'document-repository' ),
				'parent_item'       => __( 'Parent Audience', 'document-repository' ),
				'parent_item_colon' => __( 'Parent Audience:', 'document-repository' ),
				'edit_item'         => __( 'Edit Audience', 'document-repository' ),
				'update_item'       => __( 'Update Audience', 'document-repository' ),
				'add_new_item'      => __( 'Add New Audience', 'document-repository' ),
				'new_item_name'     => __( 'New Audience Name', 'document-repository' )
			),
			'division' => array(
				'name'              => _x( 'Division', 'taxonomy general name', 'document-repository' ),
				'singular_name'     => _x( 'Division', 'taxonomy singular name', 'document-repository' ),
				'search_items'      => __( 'Search Divisions', 'document-repository' ),
				'all_items'         => __( 'All Divisions', 'document-repository' ),
				'parent_item'       => __( 'Parent Division', 'document-repository' ),
				'parent_item_colon' => __( 'Parent Division:', 'document-repository' ),
				'edit_item'         => __( 'Edit Division', 'document-repository' ),
				'update_item'       => __( 'Update Division', 'document-repository' ),
				'add_new_item'      => __( 'Add New Division', 'document-repository' ),
				'new_item_name'     => __( 'New Division Name', 'document-repository' )
			),
			'process'  => array(
				'name'              => _x( 'Business Process', 'taxonomy general name', 'document-repository' ),
				'singular_name'     => _x( 'Business Process', 'taxonomy singular name', 'document-repository' ),
				'search_items'      => __( 'Search Business Processes', 'document-repository' ),
				'all_items'         => __( 'All Business Processes', 'document-repository' ),
				'parent_item'       => __( 'Parent Business Process', 'document-repository' ),
				'parent_item_colon' => __( 'Parent Business Process:', 'document-repository' ),
				'edit_item'         => __( 'Edit Business Process', 'document-repository' ),
				'update_item'       => __( 'Update Business Process', 'document-repository' ),
				'add_new_item'      => __( 'Add New Business Process', 'document-repository' ),
				'new_item_name'     => __( 'New Business Process Name', 'document-repository' )
			)
		);

		foreach ( $taxes as $tax => $labels ) {
			$args = array(
				'hierarchical' => true,
				'labels'       => $labels,
				'show_ui'      => true,
				'query_var'    => true,
				'rewrite'      => array( 'slug' => $tax )
			);
			$caps = apply_filters( 'document_taxonomy_capabilities', array(), $tax );
			if ( is_array( $caps ) && count( $caps ) > 0 ) {
				$args['capabilities'] = $caps;
			}

			register_taxonomy( $tax, array( $ra_document_library->post_type_name ), $args );
		}
		add_action( 'document_search_widget', array( 'RA_Document_taxonomies', 'document_search_widget' ) );
		add_action( 'save_post', array( 'RA_Document_taxonomies', 'save_post' ), 10, 2 );
		add_filter( 'post_updated_messages', array( 'RA_Document_taxonomies', 'post_updated_messages' ), 12 );
		add_filter( 'the_content', array( 'RA_Document_taxonomies', 'the_content' ), 9 );
		add_filter( 'document_search_query_vars', array( 'RA_Document_taxonomies', 'document_search_query_vars' ) );
		add_filter( 'ra-document-search-taxonomies', array( 'RA_Document_Taxonomies', 'document_search_query_vars' ) );

		return true;
	}

	/*
	require the document to have a term from all the custom taxonomies
	*/
	function save_post( $post_id, $post ) {
		global $wpdb, $ra_document_library;

		if ( $post->post_status != 'publish' || $post->post_type != $ra_document_library->post_type_name ) {
			return;
		}

		$has_terms = true;
		foreach ( array( 'audience', 'division', 'process' ) as $tax ) {
			$terms = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'names' ) );
			if ( empty( $terms ) ) {
				$has_terms = false;
				break;
			}
		}
		if ( ! $has_terms ) {
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				wp_redirect( add_query_arg( array( 'post_type' => $this->post_type_name ), admin_url( 'edit.php' ) ) );
			} else {
				wp_redirect( add_query_arg( array( 'post'    => $post_id,
												   'action'  => 'edit',
												   'message' => 2
				), admin_url( 'post.php' ) ) );
			}
			exit;
		}
	}

	/*
	custom message for the requirement above
	*/
	function post_updated_messages( $messages ) {
		global $ra_document_library;
		if ( ! empty( $messages[ $ra_document_library->post_type_name ] ) ) {
			$messages[ $ra_document_library->post_type_name ][2] = __( 'All published documents require an audience, division and business process.', 'document-repository' );
		}

		return $messages;
	}

	/*
	add custom taxonomies to the search widget
	*/
	function document_search_widget() {
		global $ra_document_library, $wp_query;

		foreach ( array( 'audience', 'division', 'process' ) as $tax ) {
			$terms = get_terms( $tax );
			if ( empty( $terms ) ) {
				continue;
			}

			$current  = ! empty( $wp_query->query_vars[ $tax ] ) ? $wp_query->query_vars[ $tax ] : '';
			$taxonomy = get_taxonomy( $tax );
			$output   = "<select name='$tax' id='$tax' class='doc-lib-taxonomy'>\n";
			$output   .= '\t<option value="" ' . selected( $current == '', true, false ) . ">{$taxonomy->labels->name}</option>\n";
			foreach ( $terms as $term ) {
				$output .= "\t<option value='{$term->slug}' " . selected( $current, $term->slug, false ) . ">{$term->name}</option>\n";
			}

			echo $output . '</select><br />';
		}
	}

	/*
	add custom taxonomies to the post content to eliminate need for custom templates
	*/
	function the_content( $content ) {
		global $post, $ra_document_library;
		if ( $post->post_type != $ra_document_library->post_type_name ) {
			return $content;
		}

		$term_content = '';
		foreach ( array( 'audience', 'division', 'process' ) as $tax ) {
			$taxonomy = get_taxonomy( $tax );
			$terms    = get_the_terms( 0, $tax );
			if ( ! empty( $terms ) ) {
				$term_content .= '<strong>' . $taxonomy->labels->name . '</strong>: ';
				if ( ! $ra_document_library->media_library ) {
					$terms = get_the_term_list( 0, $tax, '', ',', ' ' );
					if ( ! empty( $terms ) ) {
						$term_content .= $terms;
					}

					continue;
				}
				$term_list = array();
				foreach ( $terms as $term ) {
					$term_list[] = '<a href="#" class="' . esc_attr( $ra_document_library->js_class ) . '" id="' . esc_attr( $tax ) . ':' . $post->ID . '" name="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a>';
				}
				$term_content .= '<span class="doc-terms">' . implode( ', ', $term_list ) . '</span>';
			}
		}
		if ( ! empty( $term_content ) ) {
			$content .= '<div class="doc-lib-meta">' . $term_content . '</div>';
		}

		return $content;
	}

	/*
	flush rewrite rules on activation
	*/
	function rewrite_flush() {
		if ( taxonomy_exists( 'audience' ) || self::init() ) {
			flush_rewrite_rules();
		}
	}

	/*
	add query vars for media library search
	*/
	function document_search_query_vars( $vars ) {
		$vars = array_merge( $vars, array( 'audience', 'division', 'process' ) );

		return array_unique( $vars );
	}
}
