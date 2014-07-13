var Language = (function (globals) {
	var gt = new Gettext({domain: 'secondary'});
	
	globals._ = function (msgid) {
		return gt.gettext(msgid);
	};
	
	function load(lang) {
		gt.try_load_lang_po('/locales/' + lang + '/LC_MESSAGES/secondary.po');
	}
	
	return {
		load: load
	}
}(this));


