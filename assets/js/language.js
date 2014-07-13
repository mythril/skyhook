var Language = (function (globals) {
	var gt = new Gettext({domain: 'secondary'});
	
	globals._ = function (msgid) {
		return gt.gettext(msgid);
	};
	
	function load(lang) {
		gt = null;
		$('link[rel=gettext]').remove();
		$('<link rel="gettext" href="/locales/' + lang + '/LC_MESSAGES/secondary.po" />')
			.appendTo(document.body);
		gt = new Gettext({domain: 'secondary'});
	}
	
	return {
		load: load
	}
}(this));


