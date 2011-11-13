=== Document Repository ===
Contributors: wpmuguru
Tags: custom, post, revision, media
Requires at least: 3.2
Tested up to: 3.2.1
Stable tag: 0.2.4

Turn a WordPress site into a revisioned document repository.

== Description ==

The document repository is designed to provide a central revisioned repository for documents in WordPress network being implemented as a content management system. However, it can be used in single WP sites and supports cross domain implementation via JSON.

*Features*

*	*Download via permlink* - The document post permalink delivers a direct download of the current version of the uploaded document. 
*	*Document revisions* - On upload of each document, a new revision of the document post is created. Previous versions remain attached to prior revisions.
*	*Version independence* - The permalink to the most recent version of the document doesn't change which enables one time internal or external linking to the document.
*	*Revision download via permalink* - Prior versions of the document each have a distinct permalink which delivers a direct download.
*	*Optional custom taxonomies* - A custom taxonomy plugin is included to model implementing custom taxonomies with the document post type 
*	*Optional custom roles* - A custom role plugin is included which permits restricting contributor, author & editor access to document posts based on organizational role
*	*Optiona extras* - The extras plugin is included to add a link to document admin to document maintainers' admin bar & adds a document media type to the edit post area across the network.

[User instructions/documentation provided by University of Mary Washington](http://technology.umw.edu/wordpress101/document-repository/)

This plugin was written by [Ron Rennick](http://ronandandrea.com/) in collaboration with the [University of Mary Washington](http://umw.edu/).

[Plugin Page for details](http://wpmututorials.com/plugins/document-repository/)

== Installation ==

1. Upload the entire `document-repository` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. In a WordPress network add the repository site URL to your wp-config.php
`define( 'RA_DOCUMENT_REPO_URL', 'http://domain.com' );`
1. If using the Extras plugin in a WordPress network, network activate it and add the repository blog ID to your wp-config.php
`define( 'RA_DOCUMENT_REPO_BLOG_ID', 1 );`

== Changelog ==

= 0.2.4 =
* script fixes to 0.2.3, crossdomain IE support

= 0.2.3 =
* add filter for custom taxonomy caps
* use selected text in post edit as link text when inserting document link
* extras script fixes for media library iframe
* translation support
* small fixes to document-repository plugin

= 0.2.2 =
* Rounded out feature set to beta.

= 0.2.1 =
* Original version.

