function ra_insert_document(doc) {
	var el=jQuery(doc);
	var title=el.attr('title');
	// from press this
	var d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,s=(e?e():(k)?k():(x?x.createRange().text:0));
	var l=new String(e(s)),linktext=(l.length?l:title);

	window.parent.send_to_editor('<a href="'+el.attr('href')+'" title="'+title+'">'+linktext+'</a>');
}
function ra_refresh_document() {
	tb_remove();
	var href=window.location.href;
	if(href.indexOf('post.php') > 0) {
		location.reload(true);
		return;
	}
	var url=href.substring(0,href.indexOf('post-new.php'));
	var post_ID=jQuery('#post_ID').val();
	if(url.length && post_ID > 0) {
		location.replace(url+'post.php?post='+post_ID+'&action=edit');
	}
}
function ra_close_media() {
	window.parent.ra_refresh_document();
}
