<?php
/*
replace the default search widget with an extensible one
*/
class RA_Document_Widget_Search extends WP_Widget {
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_search', 'description' => __( 'A document search form for your site', 'document-repository' ) );
		parent::__construct( 'search', __( 'Document Search', 'document-repository' ), $widget_ops);
	}
	
	function widget( $args, $instance ) {
		global $ra_document_library, $wp_query;
		
		$label_class = 'screen-reader-text';
		$primary_class = $extras_class = '';
		$js_class = $ra_document_library->js_class;
		extract( $args );
		
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$tags = !empty( $wp_query->query_vars['tag'] ) ? esc_attr( $wp_query->query_vars['tag'] ) : '';
		
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		
		echo '<form role="search" method="get" id="searchform" action="' . home_url( '/' ) . '" >
	<div><div class="' . esc_attr( $primary_class ) . '"><label class="' . esc_attr( $label_class ) . '" for="s">' . __( 'Search for:', 'document-repository' ) . '</label>
	<input type="text" value="' . get_search_query() . '" name="s" id="s" /><br />
	<label class="' . $label_class . '" for="tag">' . __( 'Tags:', 'document-repository' ) . '</label>
	<input type="text" value="' . $tags . '" name="tag" id="tag" /><br /></div>';
		echo '<div class="' . esc_attr( $extras_class ) .'">';
		do_action( 'document_search_widget', $js_class );
		echo '</div><input type="submit" id="searchsubmit" class="' . esc_attr( $js_class ) . '" value="'. esc_attr__( 'Search Documents', 'document-repository' ) .'" />
		<div class="clear"></div>
	</div>
	</form>';
		
		echo $after_widget;
	}
	
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = $instance['title'];
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'document-repository' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}
}