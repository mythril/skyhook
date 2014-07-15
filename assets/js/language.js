var Language = (function (globals) {
	var gt = new Gettext({domain: 'secondary'});
        var lastLangChange = (new Date()).getTime();
	
	globals._ = function (msgid, opt_arg_array) {
		if (typeof opt_arg_array === "undefined") {
			return gt.gettext(msgid);
		} else {
			return gt.strargs(msgid, opt_arg_array);
		}
	};
	
	function load(lang) {
          lastLangChange = (new Date()).getTime();         
          gt.try_load_lang_po('/locales/' + lang + '/LC_MESSAGES/secondary.po');
	}

        function getLastLangChange() {
          return lastLangChange;
        }
	
	return {
		load: load,
		getLastLangChange: getLastLangChange
	}
}(this));


