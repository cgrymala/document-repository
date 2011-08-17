function ra_insert_document(doc) {
	var el=jQuery(doc);

	window.parent.ra_send_to_editor(el.attr('href'),el.attr('title'));
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
function ra_send_to_editor(href,title) {
	var l,m=tinyMCE.activeEditor,t=m?m.selection.getContent():'';

	try {	
		if(t)
			l={ text: t };
		else
			l=jQuery('#editorcontainer #content').getSelection();
	} catch(e) {
		l={ text: '' };
	}		

	var linktext=(l.text.length?l.text:title);

	send_to_editor('<a href="'+href+'" title="'+title+'">'+linktext+'</a>');
}
/*
 * jQuery plugin: fieldSelection - v0.1.0 - last change: 2006-12-16
 * (c) 2006 Alex Brem <alex@0xab.cd> - http://blog.0xab.cd
 */

(function() {

	var fieldSelection = {

		getSelection: function() {

			var e = this.jquery ? this[0] : this;

			return (

				/* mozilla / dom 3.0 */
				('selectionStart' in e && function() {
					var l = e.selectionEnd - e.selectionStart;
					return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
				}) ||

				/* exploder */
				(document.selection && function() {

					e.focus();

					var r = document.selection.createRange();
					if (r == null) {
						return { start: 0, end: e.value.length, length: 0 }
					}

					var re = e.createTextRange();
					var rc = re.duplicate();
					re.moveToBookmark(r.getBookmark());
					rc.setEndPoint('EndToStart', re);

					return { start: rc.text.length, end: rc.text.length + r.text.length, length: r.text.length, text: r.text };
				}) ||

				/* browser not supported */
				function() {
					return { start: 0, end: e.value.length, length: 0 };
				}

			)();

		},

		replaceSelection: function() {

			var e = this.jquery ? this[0] : this;
			var text = arguments[0] || '';

			return (

				/* mozilla / dom 3.0 */
				('selectionStart' in e && function() {
					e.value = e.value.substr(0, e.selectionStart) + text + e.value.substr(e.selectionEnd, e.value.length);
					return this;
				}) ||

				/* exploder */
				(document.selection && function() {
					e.focus();
					document.selection.createRange().text = text;
					return this;
				}) ||

				/* browser not supported */
				function() {
					e.value += text;
					return this;
				}

			)();

		}

	};

	jQuery.each(fieldSelection, function(i) { jQuery.fn[i] = this; });

})();
