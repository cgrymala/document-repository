<?php

class RA_Document_Extras {
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
	 * @access	private
	 * @var		\RA_Document_Extras
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   0.5
	 * @return	\RA_Document_Extras
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/*
	load text domain
	*/
	function plugins_loaded() {
		if ( ! class_exists( 'RA_Document_Post_Type' ) ) {
			load_plugin_textdomain( 'document-repository', false, '/languages/' );
		}
	}

	/*
	enqueue script for the edit post area
	*/
	function admin_init() {

		global $wp_version, $pagenow;

		if ( ! isset( $_GET['post'] ) && ! isset( $_GET['post_type'] ) && $pagenow != 'post-new.php' ) {
			return;
		}

		if ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) {
			wp_enqueue_script( 'ra-document', plugin_dir_url( dirname( __FILE__ ) ) . 'js/media.js', array( 'jquery' ), RA_DOCUMENT_REPO_VERSION, true );
		}

		if ( version_compare( $wp_version, '3.5', '<' ) ) {
			add_filter( 'media_buttons_context', array( 'RA_Document_Extras', 'media_buttons_context' ) );
		} else {
			add_action( 'media_buttons', array( 'RA_Document_Extras', 'media_buttons' ) );
		}

	}

	/*
	add the admin bar menu item
	*/
	function admin_bar_menu() {
		global $wp_admin_bar;

		if ( ! is_admin() || ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! defined( 'RA_DOCUMENT_REPO_BLOG_ID' ) &&
             ( ! defined( 'RA_DOCUMENT_REPO' ) || ! is_numeric( 'RA_DOCUMENT_REPO' ) ) ) {
		    return;
        }

		$user = get_current_user();
		if ( empty( $user->doc_role ) &&
             ( ! defined( 'RA_DOCUMENT_REPO_BLOG_ID' ) || ! current_user_can_for_blog( 'manage_options', RA_DOCUMENT_REPO_BLOG_ID ) ) &&
             ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_menu( array( 'id'    => 'umw',
										'title' => __( 'Document Admin', 'document-repository' ),
										'href'  => RA_DOCUMENT_REPO_URL . '/wp-admin/edit.php?post_type=umw_document'
		) );
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
	function media_buttons( $editor ) {

		printf( '<span class="ra-document-library-%s">%s</span>', sanitize_html_class( $editor ), self::media_buttons_context( '' ) );

	}

	function media_buttons_context( $context ) {

		global $typenow, $wp_version;
		if ( $typenow == 'umw_document' ) {
			return $context;
		}

		if ( version_compare( $wp_version, '3.5', '<' ) ) {

			$media_button = preg_replace( '|^(.*src=[\'"])' . admin_url( '/' ) . '(.*)$|', ' $1$2', _media_button( __( 'Insert Document', 'document-repository' ), plugin_dir_url( __FILE__ ) . 'images/doc.jpg', 'document', 'document' ) );

		} else {

			$post = get_post();
			if ( ! $post && ! empty( $GLOBALS['post_ID'] ) ) {
				$post = $GLOBALS['post_ID'];
			}

			$post_id = is_numeric( $post ) ? $post : $post->ID;

			$media_button = sprintf( '<a href="media-upload.php?post_id=%d&type=document&tab=document&#038;TB_iframe=1" id="add_media" class="thickbox" title="%s"><img src="images/media-button-other.gif?ver=20100531" alt="Add Media" onclick="return false;" /></a>', $post_id, __( 'Insert Document', 'document-repository' ) );

		}

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
		<script type="text/javascript"
				src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'js/media.js?ver=' . RA_DOCUMENT_REPO_VERSION; ?>"></script>
	<?php }
}
