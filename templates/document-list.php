<?php

class RA_Document_List_Template {
	public static RA_Document_List_Template $instance;

	private function __construct() {
		add_action( 'wp', array( $this, 'init' ), 99 );
	}

	public static function instance(): RA_Document_List_Template {
		if ( ! isset( self::$instance ) ) {
			$className      = __CLASS__;
			self::$instance = new $className;
		}

		return self::$instance;
	}

	public function init() {
		if ( is_post_type_archive( 'umw_document' )
		     || is_tax( 'post_tag' )
		     || is_tax( 'degree' )
		     || is_tax( 'audience' )
		     || is_tax( 'division' )
		     || is_tax( 'process' )
		     || is_search() ) {
            /*if ( defined( 'UMW_CB_VERSION' ) ) {
                do_action( 'qm/info', 'This appears to be in the UMW Custom Blocks archive template' );
                add_filter( 'umw_cb_blog_loop', array( $this, 'do_loop_item' ) );
                add_action( 'umw_cb_blog_loop_open', array( $this, 'open_loop' ) );
	            add_action( 'umw_cb_blog_loop_close', array( $this, 'close_loop' ) );
                add_action( 'umw_cb_blog_loop_no_results', array( $this, 'no_results' ) );
            } else */if ( function_exists( 'genesis' ) ) {
                do_action( 'qm/info', 'This appears to be in a standard Genesis archive template' );
				remove_all_actions( 'genesis_loop' );
				add_action( 'genesis_loop', array( $this, 'loop' ) );
			} else {
	            do_action( 'qm/info', 'This appears to be in a standard WP archive template' );
				add_filter( 'the_content', array( $this, 'the_content' ) );
			}
		} else {
            do_action( 'qm/info', 'This does not appear to be a Document Repository archive of any type' );
        }
	}

	public function the_content( $content = '' ): string {
		ob_start();
		?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title() ?></a></h2>
        <p>
            <span class="date"><?php the_date() ?></span>
        </p>
		<?php
		return ob_get_clean();
	}

	public function loop() {
		global $post;

		if ( have_posts() ) :
			$this->open_loop();
			while ( have_posts() ) : the_post();
				echo $this->do_loop_item();
			endwhile;
			$this->close_loop();
		else :
			$this->no_results();
		endif;
	}

    public function open_loop() {
        print( '<div class="umw-block-content">' );
        print( '<div class="entry-content">' );
        printf( '<h2 class="wp-block-heading">%s</h2>', __( 'List of Documents', 'document-repository' ) );
        echo '<ul class="document-list">';
    }

    public function close_loop() {
        echo '</ul>';
        print( '</div></div>' );
    }

	/**
	 * Output a single loop item
     *
     * @access public
     * @since  0.1
     * @return string the HTML for the loop item
	 */
    public function do_loop_item() {
        global $post;
        ob_start();
	    ?>
        <li>
            <h3 class="wp-block-heading"><a href="<?php the_permalink(); ?>"><?php the_title() ?></a></h3>
            <p>
                <span class="date"><?php the_date() ?></span>
            </p>
        </li>
	    <?php
        return ob_get_clean();
    }

    public function no_results() {
        print( '<div class="umw-block-content"><div class="entry-content">' );
        _e( '<h2 class="wp-block-heading">No Results</h2>', 'document-repository' );
	    _e( '<p>No documents could be located matching your criteria.</p>', 'document-repository' );
        print( '</div></div>' );
    }

	/**
	 * Truncate an excerpt to a specific number of words
	 *
	 * @param string $content the content being truncated
	 * @param int $len the maximum number of words allowed in the content
	 *
	 * @access public
	 * @return string the updated excerpt
	 * @since  0.1
	 */
	public function truncate_excerpt( string $content, int $len ): string {
		$content = strip_tags( $content );
		if ( str_word_count( $content ) <= $len ) {
			return $content;
		}

		$content = explode( ' ', $content );
		$content = array_slice( $content, 0, ( $len - 1 ) );

		return implode( ' ', $content );
	}

}