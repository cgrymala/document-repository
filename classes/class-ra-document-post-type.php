<?php

/**
 * Implements the main class for the Document Repository plugin
 *
 * @package    WordPress
 * @subpackage Document Repository
 * @version    0.5
 */
class RA_Document_Post_Type {
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
	 * @var        \RA_Document_Post_Type
	 */
	private static $instance;
	/**
	 * The slug to be used for the post type registration
	 *
	 * @since  0.1
	 * @access public
	 * @var    string
	 */
	public $post_type_name = 'umw_document';
	var $attachments = null;
	var $handle = 'umw-attachments';
	var $message = null;
	var $media_library = false;
	var $js_class = '';
	/**
	 * The default arguments for the new post type
	 *
	 * @since  0.1
	 * @access public
	 * @var    array
	 */
	var $post_type = array(
		'public'            => true,
		'hierarchical'      => false,
		'rewrite'           => array( 'slug' => 'document' ),
		'show_in_nav_menus' => false,
		'taxonomies'        => array( 'post_tag' ),
		'supports'          => array( 'title', 'editor', 'author', 'revisions' ),
		'show_in_rest'      => true,
	);

	/*
	 * Old-style constructor method, just in case
	 */
	function RA_Document_Post_Type() {
		return $this->__construct();
	}

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return    \RA_Document_Post_Type
	 * @since   0.5
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$className      = __CLASS__;
			self::$instance = new $className;
		}

		return self::$instance;
	}

	/**
	 * RA_Document_Post_Type constructor.
	 *
	 * @access private
	 * @since  0.1
	 */
	private function __construct() {
	    if ( current_user_can( 'delete_users' ) ) {
	        wp_die( 'Got this far' );
        }

		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'the_content', array( &$this, 'the_content' ) );
		if ( ( isset( $_GET['media-library'] ) && $_GET['media-library'] == 1 ) || ( isset( $_GET['mls'] ) && $_GET['mls'] == 1 ) ) {
			$this->media_library = true;
			add_action( 'init', array( &$this, 'media_library' ), 14 );

			return;
		}
		add_action( 'wp', array( $this, 'wp' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		add_action( 'admin_head_media_upload_type_form', array( $this, 'media_upload_type_form' ) );
		add_action( 'add_attachment', array( $this, 'add_attachment' ) );
		add_filter( 'pre_site_option_mu_media_buttons', array( $this, 'media_buttons_filter' ) );
		add_filter( 'media_upload_tabs', array( $this, 'media_upload_tabs' ), 99 );
		add_action( 'media_buttons', array( $this, 'media_buttons' ), 20 );
		add_filter( 'umw_document_rewrite_rules', array( $this, 'umw_document_rewrite_rules' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'wp_handle_upload_prefilter' ) );
		add_action( 'delete_post', array( $this, 'delete_post' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

		load_plugin_textdomain( 'document-repository', false, '/languages/' );

		$this->_fix_document_search();
	}

	/**
	 * Attempt to fix the empty search issues
	 *
	 * @access private
	 * @return void
	 * @since  0.5
	 */
	private function _fix_document_search() {
		if ( ! isset( $_GET['s'] ) || ! empty( $_GET['s'] ) ) {
			return;
		}

		$taxes = apply_filters( 'ra-document-search-taxonomies', array( 'tag' ) );

		$continue = false;

		foreach ( $taxes as $t ) {
			if ( isset( $_GET[ $t ] ) ) {
				$continue = true;
			}
		}

		if ( false === $continue ) {
			return;
		}

		if ( isset( $_GET['s'] ) && empty( $_GET['s'] ) ) {
			unset( $_GET['s'] );
		}

		foreach ( $taxes as $t ) {
			if ( isset( $_GET[ $t ] ) && empty( $_GET[ $t ] ) ) {
				unset( $_GET[ $t ] );
			}
		}
	}

	/*
	 * Handle requests from the media library on the client via JSON
	 *
	 * @access public
	 * @since  0.1
	 * @return void
	*/
	function media_library() {
		global $wp_query;
		$domain = '';
		if ( isset( $_GET['domain'] ) && ( $d = stripslashes( $_GET['domain'] ) ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $d ) ) {
			$domain = $d;
		}

		if ( $domain ) {
			header( 'Access-Control-Allow-Origin: https://' . $domain );
		}

		$vars = array( 'post_type' => $this->post_type_name );
		if ( isset( $_GET['mls'] ) && $_GET['mls'] == '1' ) {
			$query_vars = apply_filters( 'document_search_query_vars', array( 's', 'tag', 'paged' ) );
			foreach ( (array) $query_vars as $var ) {
				if ( isset( $_GET[ $var ] ) ) {
					$vars[ $var ] = $_GET[ $var ];
				}
			}
		}
		$wp_query = new WP_Query( $vars );

		ob_start();
		$this->js_class = 'media-library-search';
		$args           = array(
			'label_class'   => '',
			'primary_class' => 'media-library',
			'extras_class'  => 'media-library-extras'
		);
		$instance       = array( 'title' => __( 'Search Documents', 'document-repository' ) );
		the_widget( 'RA_Document_Widget_Search', $instance, $args );
		$content = ob_get_contents();
		ob_clean();
		$index = 0;
		if ( have_posts() ) {
			$paging = '';
			if ( $wp_query->post_count < $wp_query->found_posts ) {
				$page = empty( $vars['paged'] ) ? 1 : (int) $vars['paged'];
				unset( $vars['paged'] );
				unset( $vars['post_type'] );
				$form   = '<form><input type="hidden" name="paged" value="%d" /><input type="submit" class="media-library-search pagesubmit" value="%s" />%s</form>';
				$inputs = '';
				foreach ( $vars as $k => $v ) {
					$inputs .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '" />';
				}

				if ( $page > 1 ) {
					$paging .= sprintf( $form, $page - 1, esc_attr( __( 'Previous Page', 'document-repository' ) ), $inputs );
				}
				if ( $page < $wp_query->max_num_pages ) {
					$paging .= sprintf( $form, $page + 1, esc_attr( __( 'Next Page', 'document-repository' ) ), $inputs );
				}
				if ( $paging ) {
					$paging = '<div class="tablenav">' . $paging . '</div>';
				}

				$content .= $paging;
			}
			$content .= '<ul class="ml-posts">';
			global $post;
			while ( have_posts() ) {
				the_post();
				$content .= '<li class="ml-post ' . ( 1 == ( $index ++ % 2 ) ? 'alt' : '' ) . '"><h3>' . get_the_title() . ' <a href="' . get_permalink() . '" class="button" title="' . get_the_title() . '" onclick="ra_insert_document(this); return false;">' . __( 'Insert into Post', 'document-repository' ) . '</a></h3>';
				$content .= '<div class="ml-content">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
				$tags    = get_the_terms( 0, 'post_tag' );
				if ( ! empty( $tags ) ) {
					$content  .= '<strong>' . __( 'Tagged', 'document-repository' ) . '</strong>: ';
					$tag_list = array();
					foreach ( $tags as $tag ) {
						$tag_list[] = '<a href="#" class="' . esc_attr( $this->js_class ) . '" id="tag:' . $post->ID . '" name="' . esc_attr( $tag->slug ) . '">' . esc_html( $tag->name ) . '</a>';
					}
					$content .= implode( ', ', $tag_list );
				}
				$content .= '</li>';
			}
			$content .= '</ul>' . $paging;
		} else {
			$content .= '<h3>' . __( 'No Documents matched the search criteria', 'document-repository' ) . '</h3>';
		}
		echo json_encode( $content );
		exit;
	}

	/*
	 * Now that WordPress is fired up, fire up the custom post type
	 *
	 * @access public
	 * @since  0.1
	 * @return void
	 */
	function init() {
		if ( class_exists( 'RA_Document_User_Roles' ) ) {
			$this->post_type['capability_type'] = $this->post_type_name;
			$this->post_type['map_meta_cap']    = true;
		}

		$this->post_type['menu_icon'] = plugin_dir_url( dirname( __FILE__ ) ) . 'images/doc-16.jpg';
		$this->post_type['labels']    = array(
			'name'               => __( 'Documents', 'document-repository' ),
			'singular_name'      => __( 'Document', 'document-repository' ),
			'add_new'            => __( 'Add New', 'document-repository' ),
			'add_new_item'       => __( 'Add New Document', 'document-repository' ),
			'edit'               => __( 'Edit', 'document-repository' ),
			'edit_item'          => __( 'Edit Document', 'document-repository' ),
			'new_item'           => __( 'New Document', 'document-repository' ),
			'view'               => __( 'View Document', 'document-repository' ),
			'view_item'          => __( 'View Document', 'document-repository' ),
			'search_items'       => __( 'Search Documents', 'document-repository' ),
			'not_found'          => __( 'No documents found', 'document-repository' ),
			'not_found_in_trash' => __( 'No documents found in Trash', 'document-repository' ),
			'parent'             => __( 'Parent Document', 'document-repository' )
		);
		register_post_type( $this->post_type_name, $this->post_type );

		if ( isset( $_GET['ra-make-current'] ) && $_GET['ra-make-current'] == 1 && wp_verify_nonce( $_GET['_wpnonce'], 'doc-make-current' ) ) {
			$this->make_current();
		}
	}

	/*
	enqueue script for the edit post area
	*/
	function admin_enqueue_scripts( $context ) {
		global $typenow;
		if ( ( isset( $typenow ) && $typenow == 'umw_document' ) || 'media-upload-popup' == $context ) {
			wp_enqueue_script( 'ra-document', plugin_dir_url( dirname( __FILE__ ) ) . 'js/media.js', array( 'jquery' ), RA_DOCUMENT_REPO_VERSION, true );
		}
	}

	/*
	hide the save all changes button
	*/
	function media_upload_type_form() {
		$post_id = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;
		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );
		if ( $post->post_type == $this->post_type_name ) { ?>
            <style type="text/css">p.savebutton input#save {
                    display: none;
                }</style>
		<?php }
	}

	/*
	remove all media buttons except the file one in the edit Document screen
	*/
	function media_buttons_filter( $setting ) {
		global $post_type;
		if ( $post_type == $this->post_type_name ) {
			return array();
		}

		return $setting;
	}

	function media_upload_tabs( $tabs ) {
		if ( ! isset( $_GET['post_id'] ) ) {
			return $tabs;
		}

		$post = get_post( $_GET['post_id'] );
		if ( ! empty( $post->post_type ) && $post->post_type == $this->post_type_name ) {
			return array( 'type' => $tabs['type'] );
		}

		return $tabs;
	}

	/*
	catch the uploaded file and remove it from the media library
	allow revisions of uploaded documents
	ensure there is only one attachment per revision
	*/
	function add_attachment( $post_id ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( $post->post_parent < 1 ) {
			return;
		}

		$post_parent = get_post( $post->post_parent );
		if ( $post_parent->post_type != $this->post_type_name ) {
			return;
		}

		$attachments = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'document_file' and post_parent = %d", $post->post_parent ) );
		if ( ! empty( $attachments ) ) {
			$revision = wp_save_post_revision( $post->post_parent );
			if ( ! empty( $revision ) ) {
				foreach ( $attachments as $att_id ) {
					$wpdb->update( $wpdb->posts, array( 'post_parent' => $revision ), array( 'ID' => $att_id ) );
				}
			}
		}
		$wpdb->update( $wpdb->posts, array( 'post_type' => 'document_file' ), array( 'ID' => $post_id ) );
		$wpdb->update( $wpdb->posts, array(
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 )
		), array( 'ID' => $post->post_parent ) );

		printf( '<a href="#" onclick="ra_close_media(); return false;">%s</a>', __( 'Finished.', 'document-repository' ) );
		exit;
	}

	/*
	handle document permalink request
	*/
	function wp() {
		global $wpdb;
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$object = get_queried_object();
		if ( $object->post_type != $this->post_type_name || ! property_exists( $object, 'ID' ) ) {
			return;
		}

		if ( class_exists( 'CWS_PageLinksTo ' ) ) {
		    $plt = CWS_PageLinksTo::get_instance();
		    $is_redirected = $plt::get_post_meta( $object->ID, $plt::LINK_META_KEY );

		    if ( ! empty( $is_redirected ) ) {
		        if ( current_user_can( 'delete_users' ) ) {
		            wp_die( 'Looks like the redirect is not empty. It looks like: ' . $is_redirected );
		        }
		        return;
		    } else {
		        if ( current_user_can( 'delete_users' ) ) {
		            wp_die( 'Looks like the redirect is empty, so we would normally start downloading the document.' );
		        }
		    }
		} else {
		    if ( current_user_can( 'delete_users' ) ) {
		        wp_die( 'Did not find Page Links To class' );
		    }
		}

		$children = $this->get_child_documents( $object->ID, true );
		if ( empty( $children ) ) {
			$this->message = 'none';

			return;
		}

		$version = str_replace( '/', '', get_query_var( 'attachment' ) );
		$count   = count( $children );
		if ( $count <= $version || $version < 0 ) {
			$this->message = 'version';

			return;
		}

		if ( empty( $version ) || $count == 1 ) {
			$attachment = array_shift( $children );
		} else {
			$child      = array_slice( $children, $count - $version, 1 );
			$attachment = array_shift( $child );
		}
		$document = get_post_meta( $attachment->attachment_id, '_wp_attached_file', true );
		if ( empty( $document ) ) {
			$this->message = 'none';
			add_filter( 'the_content', array( &$this, 'the_content' ) );

			return;
		}

		$uploads       = wp_upload_dir();
		$document_file = trailingslashit( $uploads['basedir'] ) . $document;
		if ( ! is_file( $document_file ) ) {
			$this->message = 'none';

			return;
		}

		// serve it up
		$filename  = $this->base_name( $document );
		$mime_type = 'application/octet-stream';
		if ( ! empty( $attachment->post_mime_type ) ) {
			$mime_type = $attachment->post_mime_type;
		}
		ob_clean();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $mime_type );
		header( "Content-Disposition: inline; filename={$filename}" );
		header( 'Content-Transfer-Encoding: binary' );
		if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) ) {
			header( 'Content-Length: ' . filesize( $document_file ) );
		}
		flush();
		readfile( $document_file );
		flush();
		exit;
	}

	/*
	preserve uploaded document filename by adding a filename suffix
	*/
	function wp_handle_upload_prefilter( $file ) {
		$name         = strrev( $file['name'] );
		$name         = preg_replace( '|^([^\.]+\.)|', '$1-', $name );
		$file['name'] = strrev( $name );

		return $file;
	}

	/*
	clean up attachment revisions when a document post is deleted
	*/
	function delete_post( $post_id ) {
		global $wpdb;

		if ( ! ( $post = get_post( $post_id ) ) || $post->post_type != $this->post_type_name ) {
			return;
		}

		$attachments = $this->get_child_documents( $post_id );
		if ( empty( $attachments ) ) {
			return;
		}

		$ids = array();
		foreach ( $attachments as $a ) {
			$ids[] = $a->attachment_id;
		}

		$attachments = implode( ',', $ids );
		$wpdb->query( "UPDATE {$wpdb->posts} SET post_type = 'attachment', post_status = 'trash' WHERE post_type = 'document_file' AND ID IN ({$attachments})" );
		foreach ( $ids as $id ) {
			wp_delete_attachment( $id, true );
		}
	}

	/*
	replace the built in revision metabox with our custom one
	*/
	function admin_menu() {
		add_action( 'do_meta_boxes', array( &$this, 'add_metabox' ), 9 );
	}

	function add_metabox() {
		global $post;
		if ( empty( $post ) || $this->post_type_name != $post->post_type ) {
			return;
		}
		if ( $this->attachments === null ) {
			$this->attachments = $this->get_child_documents( $post->ID, true );
		}

		if ( ! empty( $this->attachments ) ) {
			add_meta_box( $this->handle, __( 'Document Versions', 'document-repository' ), array(
				&$this,
				'document_metabox'
			), $this->post_type_name, 'normal' );
			remove_meta_box( 'revisionsdiv', $this->post_type_name, 'normal' );
		}
	}

	/*
	custom revision metabox - show attachments & make current function
	*/
	function document_metabox() {
		global $post;

		$titlef    = _x( '%1$s by %2$s', 'post revision' );
		$revisions = wp_get_post_revisions( $post->ID );
		krsort( $revisions );

		echo '<ul>';
		$current = null;
		if ( ( $version = count( $this->attachments ) ) ) {
			$permalink = get_permalink();
			$current   = array_shift( $this->attachments );
			$datef     = _x( 'j F, Y @ G:i', 'revision date format' );
			printf( __( '<li>%s / Current Version: %d - <a href="%s">%s</a></li>', 'document-repository' ), date_i18n( $datef, strtotime( $current->post_modified ) ), $version --, $permalink, $this->base_name( get_post_meta( $current->attachment_id, '_wp_attached_file', true ) ) );
			unset( $current );
		}
		foreach ( $revisions as $r ) {
			if ( empty( $current ) && ! empty( $this->attachments ) ) {
				$current = array_shift( $this->attachments );
			}

			$date = wp_post_revision_title( $r->ID );
			$name = get_the_author_meta( 'display_name', $r->post_author );
			echo '<li>';
			printf( $titlef, $date, $name );
			if ( ! empty( $current ) && $current->ID == $r->ID ) {
				$version_link = $permalink . 'version/' . $version;
				$current_link = wp_nonce_url( add_query_arg( array(
					'post_id'       => $post->ID,
					'attachment_id' => $current->attachment_id
				), admin_url( 'index.php?ra-make-current=1' ) ), 'doc-make-current' );
				$file_name    = esc_html( $this->base_name( get_post_meta( $current->attachment_id, '_wp_attached_file', true ) ) );
				printf( __( ' / Version: %d - <a href="%s">Make document current</a> - <a href="%s">%s</a></li>', 'document-repository' ), $version --, $current_link, $version_link, $file_name );
				unset( $current );
			}
		}
		echo '</ul>';
	}

	/*
	make the selected document the current version
	*/
	function make_current() {
		global $wpdb;

		$post_id       = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		$attachment_id = isset( $_GET['attachment_id'] ) ? (int) $_GET['attachment_id'] : 0;
		if ( ! $post_id || ! $attachment_id ) {
			return;
		}

		$post = get_post( $post_id );
		if ( empty( $post ) || $post->post_type != $this->post_type_name ) {
			return;
		}

		$attachment = get_post( $attachment_id );
		if ( empty( $attachment ) || $attachment->post_type != 'document_file' ) {
			return;
		}

		$attachments = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'document_file' and post_parent = %d", $post->ID ) );
		if ( ! empty( $attachments ) ) {
			$revision = wp_save_post_revision( $post->ID );
			if ( ! empty( $revision ) ) {
				foreach ( $attachments as $att_id ) {
					$wpdb->update( $wpdb->posts, array( 'post_parent' => $revision ), array( 'ID' => $att_id ) );
				}
			}
		}
		$wpdb->update( $wpdb->posts, array( 'post_parent' => $post_id ), array( 'ID' => $attachment_id ) );
		// redirect
		wp_redirect( wp_get_referer() );
	}

	/*
	remove suffix from uploaded file name
	*/
	function base_name( $filename = '' ) {
		$filename = strrev( basename( $filename ) );
		$filename = preg_replace( '|^([^\.]+)\.[0-9]*\-|', '$1.', $filename );

		return strrev( $filename );
	}

	/*
	get a list of document post type revisions with attachments
	*/
	function get_child_documents( $post_id, $sorted = false ) {
		global $wpdb;
		$children = $wpdb->get_results( $wpdb->prepare( "SELECT r.*,a.ID as attachment_id FROM {$wpdb->posts} a JOIN {$wpdb->posts} r ON a.post_parent = r.ID WHERE a.post_type = 'document_file' AND (r.ID = %d OR (r.post_type = 'revision' AND r.post_parent = %d))", $post_id, $post_id ) );

		if ( ! $sorted || empty( $children ) ) {
			return $children;
		}

		$documents = array();
		foreach ( (array) $children as $v ) {
			if ( $v->post_status == 'publish' ) {
				$documents['zzz'] = $v;
				continue;
			}
			$revision = 0;
			if ( preg_match( '|[0-9]+\-revision\-([0-9]+)|', $v->post_name, $m ) ) {
				$revision = 100000000 + $m[1];
			} //more than 99,999,999 revisions of a document will have an issue

			$documents[ $revision ] = $v;
		}
		krsort( $documents, SORT_STRING );

		return $documents;
	}

	/*
	custom messages for the document post type
	*/
	function post_updated_messages( $messages ) {

		global $post, $post_ID;

		if ( empty( $post ) || empty( $post_ID ) ) {
			return $messages;
		}

		$messages['umw_document'] = array(
			1  => sprintf( __( 'Document updated. <a href="%s">View document</a>', 'document-repository' ), esc_url( get_permalink( $post_ID ) ) ),
			4  => __( 'Document updated.', 'document-repository' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Document restored to revision from %s', 'document-repository' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Document published. <a href="%s">View document</a>', 'document-repository' ), esc_url( get_permalink( $post_ID ) ) ),
			7  => __( 'Document saved.', 'document-repository' ),
			8  => sprintf( __( 'Document submitted. <a target="_blank" href="%s">Preview document</a>', 'document-repository' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9  => sprintf( __( 'Document scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview document</a>', 'document-repository' ),
				date_i18n( __( 'M j, Y @ G:i', 'document-repository' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Document draft updated. <a target="_blank" href="%s">Preview document</a>', 'document-repository' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;

	}

	/*
	add document post type rewrite rules on activation
	*/
	function rewrite_flush() {
		if ( ! post_type_exists( $this->post_type_name ) ) {
			$this->init();
		}

		flush_rewrite_rules();
	}

	/*
	override the defualt rewrite rules for the document post type
	*/
	function umw_document_rewrite_rules( $rules ) {
		global $wp_rewrite;

		$base_struct = ltrim( str_replace( "%{$this->post_type_name}%", '', $wp_rewrite->get_extra_permastruct( $this->post_type_name ) ), '/' );

		return array(
			$base_struct . '([^/]+)(/[0-9]+)?/?$'         => 'index.php?umw_document=$matches[1]&page=$matches[2]',
			$base_struct . '([^/]+)/version(/[0-9]+)?/?$' => 'index.php?umw_document=$matches[1]&attachment=$matches[2]'
		);
	}

	/*
	one single document posts, the_content is only called when the requested document does not exist (no attachment or non-existent version)
	depending on the situation add information to the content
	*/
	function the_content( $content ) {
		global $post;

		if ( $post->post_type != $this->post_type_name ) {
			return $content;
		}

		$messages = array(
			'none'    => __( 'Document not available', 'document-repository' ),
			'version' => sprintf( __( 'The version you requested is not available - <a href="%s">Download the current version</a>', 'document-repository' ), get_permalink() )
		);

		if ( ! empty( $this->message ) && ! empty( $messages[ $this->message ] ) ) {
			return '<h4>' . $messages[ $this->message ] . '</h4>' . $content;
		}

		if ( $this->media_library ) {
			return $content;
		}

		return $content . '<h4><a href="' . get_permalink() . '" title="' . get_the_title() . '">' . __( 'Download', 'document-repository' ) . '</a></h4>';
	}

	function media_buttons() {
		global $wp_version, $typenow;
		if ( ! isset( $typenow ) || $typenow != 'umw_document' || version_compare( $wp_version, '3.5', '<' ) ) {
			return;
		}

		$post = get_post();
		if ( ! $post && ! empty( $GLOBALS['post_ID'] ) ) {
			$post = $GLOBALS['post_ID'];
		}

		$post_id = is_numeric( $post ) ? $post : $post->ID;
		?>
        <a href='media-upload.php?post_id=<?php echo $post_id; ?>&#038;TB_iframe=1' id='add_media' class='thickbox'
           title='Add Media'><img src='images/media-button-other.gif?ver=20100531' alt='Add Media'
                                  onclick='return false;'/></a>
        <script type="text/javascript">
            //<!--
            jQuery(document).ready(function () {
                jQuery('#add_media').siblings('a.add_media').hide();
            });
            //-->
        </script>
		<?php
	}
}
