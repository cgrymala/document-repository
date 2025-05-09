<?php
class RA_Document_Search_Shortcode {
	/**
	 * @var RA_Document_Search_Shortcode $instance holds the single instance of this class
	 * @access private
	 */
	private static RA_Document_Search_Shortcode $instance;

	function __construct() {
		add_shortcode( 'document-search', array( $this, 'document_search_shortcode' ) );
	}

	public static function instance(): RA_Document_Search_Shortcode {
		if ( ! isset( self::$instance ) ) {
			$className      = __CLASS__;
			self::$instance = new $className;
		}

		return self::$instance;
	}

	public function document_search_shortcode( $atts = array(), $content = null ): string {
		$atts = shortcode_atts( array(
			'title' => '',
		), $atts, 'document-search' );

		$rt = new RA_Document_Widget_Search();
		ob_start();
		$rt->widget( array(
			'before_widget' => '<div class="document-search">',
			'after_widget'  => '</div>',
			'before_title' => '<h3>',
			'after_title'  => '</h3>',
		), $atts );
		return ob_get_clean();
	}
}