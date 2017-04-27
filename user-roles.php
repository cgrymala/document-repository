<?php
/*
Plugin Name: Document Repository Custom Roles
Plugin URI: http://wpmututorials.com/plugins/document-repository/
Description: Optional Custom Roles for the document repository. Custom roles are combined with WP roles to manage contributor, author & editor permissions.
Author: Ron Rennick
Version: 0.5
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

if ( ! class_exists( 'RA_Document_User_Roles' ) ) {
    require_once( plugin_dir_path( __FILE__ ) . 'classes/class-ra-document-user-roles.php' );
}

$ra_document_user_roles = new RA_Document_User_Roles();

register_activation_hook( __FILE__, array( 'RA_Document_User_Roles', 'activate' ) );
