<?php
/*
Plugin Name: Document Repository Custom Roles
Plugin URI: http://wpmututorials.com/plugins/document-repository/
Description: Optional Custom Roles for the document repository. Custom roles are combined with WP roles to manage contributor, author & editor permissions.
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

class RA_Document_User_Roles {
	var $post_type_name = 'umw_document';
	/*
	use constructor to hook into WP 
	*/
	function __construct() {
		add_action( 'init', array( &$this, 'init' ), 12 );
	}
	function init() {
		if( !is_admin() || !post_type_exists( $this->post_type_name ) )
			return;

		if( current_user_can( 'manage_options' ) )
			add_action( 'admin_menu', array( &$this, 'add_admin_page' ), 20 );
			
		add_filter( 'map_meta_cap', array( &$this, 'map_meta_cap' ), 10, 4 );
		if( !current_user_can( 'manage_users' ) )
			return;

		add_filter( 'manage_posts_columns', array( &$this, 'manage_posts_columns' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( &$this, 'manage_posts_custom_column' ), 1, 2 );
		add_filter( 'manage_users_columns', array( &$this, 'manage_users_columns' ) );
		add_filter( 'manage_users_custom_column', array( &$this, 'manage_users_custom_column' ), 1, 3 );
		add_action( 'personal_options', array( &$this, 'personal_options' ) );
		add_action( 'personal_options_update', array( &$this, 'update_profile' ) );
		add_action( 'edit_user_profile_update', array( &$this, 'update_profile' ) );
	}
	/*
	add post column for admins
	*/ 
	function manage_posts_columns( $columns, $post_type ) {
		if( $post_type == $this->post_type_name )
			$columns[$post_type] = __( 'Document Role', 'document-repository' );
	
		return $columns;
	}
	function manage_posts_custom_column( $column_name, $post_id ) {
		if( $column_name != $this->post_type_name )
			return;
			
		$roles = $this->get_roles();
		$post = get_post( $post_id );
		$user = get_userdata( $post->post_author );
		if( !empty( $user->doc_role ) && !empty( $roles[$user->doc_role] ) )
			echo $roles[$user->doc_role];
	}
	/*
	add user column for admins
	*/ 
	function manage_users_columns( $columns ) {
		$columns[$this->post_type_name] = __( 'Document Role', 'document-repository' );
		return $columns;
	}
	function manage_users_custom_column( $content, $column_name, $user_id ) {
		if( $column_name != $this->post_type_name )
			return;
	
		$roles = $this->get_roles();
		$user = get_userdata( $user_id );
		if( !empty( $user->doc_role ) && !empty( $roles[$user->doc_role] ) )
			$content .= $roles[$user->doc_role];
	
		return $content;
	}
	/*
	role admin page
	*/
	function admin_page() {
		global $wpdb;
		
		$wpdb->doc_roles = $wpdb->base_prefix . 'doc_roles';
		
		if( !empty( $_GET['delete_role'] ) ) {
			$role = (int)$_GET['delete_role'];
			if( $role && wp_verify_nonce( $_GET['_wpnonce'], 'delete' . $role ) )
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->doc_roles} WHERE role_id = %d", $role ) );
		} 
		if( !empty( $_POST['new_role'] ) )
			$wpdb->insert( $wpdb->doc_roles, array( 'role_desc' => trim( $_POST['new_role'] ) ) );
		
		if( !empty( $_POST['doc_role'] ) && !empty( $_POST['original_doc_role'] ) ) {
			foreach( (array)$_POST['doc_role'] as $id => $desc ) {
				if( isset( $_POST['original_doc_role'][$id] ) && $_POST['original_doc_role'][$id] != $desc )
					$wpdb->update( $wpdb->doc_roles, array( 'role_desc' => trim( $desc ) ), array( 'role_id' => $id ) );
			}
		}
		$roles = $this->get_roles( true );
		?><div class="wrap"><h3><?php _e( 'Document Roles', 'document-repository' ); ?></h3>
			<form method="POST">
				<table class="widefat"><thead><td><?php _e( 'ID', 'document-repository' ); ?></td><td><?php _e( 'Description', 'document-repository' ); ?></td><td><?php _e( 'Actions', 'document-repository' ); ?></td></thead><tbody><?php
		if( !empty( $roles ) ) {
			foreach( $roles as $id => $role ) {
				echo '<tr><td>' . $id . '</td><td><input type="text" name="doc_role[' . $id . ']" value="' . esc_attr( $role ) . '" />';
				echo '<input type="hidden" name="original_doc_role[' . $id . ']" value="' . esc_attr( $role ) . '" /></td>';
				printf( '<td><a href="%s">%s<a></td></tr>', wp_nonce_url( add_query_arg( array( 'delete_role' => $id ) ), 'delete' . $id ), __( 'Delete', 'document-repository' ) );
			}
		}
				?></tbody></table>
			<p><?php _e( 'Add Document Role', 'document-repository' ); ?>&nbsp;<input type='text' name='new_role' value='' /></p>
				<input type='submit' value='<?php _e( 'Update', 'document-repository' ); ?>' />
			</form>
		</div><?php
	}
	function add_admin_page() {
		add_submenu_page( 'edit.php?post_type=' . $this->post_type_name, __( 'Document Roles', 'document-repository' ), __( 'Document Roles', 'document-repository' ), 'manage_options', 'ra_doc_roles_admin', array( &$this, 'admin_page' ) );
	}
	/*
	profile field
	*/
	function personal_options( $user ) {
		$role = isset( $user->doc_role ) ? $user->doc_role : 0;	
		?><tr><th scope="row"><?php _e( 'Document Role', 'document-repository' ); ?></th>
			<td><?php $this->role_select( $role ); ?></td>
		</tr><?php
	}
	function update_profile( $user_id ) {
		if( isset( $_POST['doc_role'] ) ) {
			$role = (int)$_POST['doc_role'];
			if( $role )
				update_usermeta( $user_id, 'doc_role', $role );
			else
				delete_usermeta( $user_id, 'doc_role' );
		}
	}	
	function role_select( $role = 0 ) {
		$roles = $this->get_roles();
		if( is_array( $roles ) ) { 
			$roles[0] = __( ' -- No Document Role -- ', 'document-repository' );
			asort( $roles );
			?><select name="doc_role"><?php
			
			foreach( $roles as $key => $value ) {
				 			
				?><option value="<?php  echo $key; ?>"<?php selected( $role, $key ); ?>><?php echo $value; ?></option><?php
				
			} 
			?></select><?php
		}
	}
	/*
	helper function to retrieve the doc roles from the DB
	*/
	function get_roles( $force = false ) {
		global $wpdb;
		static $roles = false;
		if( !$roles || $force ) {
			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}doc_roles ORDER BY role_desc ASC" );
			if( $rows ) {
				$roles = array();
				foreach( $rows as $r )
					$roles[$r->role_id] = $r->role_desc;
			}
		}
		return $roles;
	}
	/*
	restrict access to editing documents based on WP role & document role
	*/
	function map_meta_cap( $caps, $cap, $user_id, $objects = array() ) {
		global $wpdb;

		foreach( array( 'edit', 'publish' ) as $c ) {
			if( $cap == "{$c}_{$this->post_type_name}s" ) {
				$user = new WP_User( $user_id );
				if( !empty( $user->doc_role ) || $user->has_cap( 'manage_options' ) )
					return array( "{$c}_posts" );
			}
		}
	
		if( ( $cap != 'edit_post' && $cap != 'delete_post' ) || empty( $objects ) || count( $objects ) > 1 )
			return $caps;
	
		$post = get_post( current( $objects ) );
	
		if( empty( $post->post_type ) || $post->post_type != $this->post_type_name )
			return $caps;
			
		if(  $post->post_author == $user_id )
			return array( 'edit_posts' );
	
		$user = new WP_User( $user_id );
		if( $user->has_cap( 'manage_options' ) )
			return array( 'edit_posts' );
	
		if( !empty( $user->doc_role ) && $user->has_cap( 'edit_others_posts' ) ) {
			$author_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'doc_role' AND meta_value = %s", $post->post_author, $user->doc_role ) );
			if( !empty( $author_id ) )
				return array( 'edit_others_posts' );
		}
			
		$caps[] = 'do_no_allow';
		return $caps;
	}
	/*
	create global table for document roles on activation
	*/	
	function activate() {
		global $wpdb;
	
		$wpdb->doc_roles = $wpdb->base_prefix . 'doc_roles';
		
		if( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->doc_roles}'" ) !== $wpdb->doc_roles ) {
			$charset_collate = '';
			if ( !empty( $wpdb->charset ) )
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( !empty( $wpdb->collate ) )
				$charset_collate .= " COLLATE $wpdb->collate";
	
			$wpdb->query( "CREATE TABLE `{$wpdb->doc_roles}` (
	`role_id` SMALLINT( 2 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`role_desc` VARCHAR( 20 ) NOT NULL
	) $charset_collate;" );
		}
	}
}

$ra_document_user_roles = new RA_Document_User_Roles();

register_activation_hook( __FILE__, array( 'RA_Document_User_Roles', 'activate' ) );
