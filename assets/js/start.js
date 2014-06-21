Comet.open('/price', function (price) {
	$('.fiat-amount').text(CurrencyData.symbol + String(price));
});

$(function () {
	MBP.hideUrlBarOnLoad();
	
	function goToAccountPage() {
		window.location.replace('/account');
	}
	
	function bind() {
		$(document.body).on('touchstart mousedown', goToAccountPage);
	}
	
	function unbind() {
		$(document.body).off('touchstart mousedown', goToAccountPage);
	}
	
	bind();
	
	HelpPanel.onOpen = unbind;
	HelpPanel.onClose = bind;
	
	window.setTimeout(function () {
		window.location.reload();
	}, 60*60*1000);
});

$(function () {
	var selector;
	
	function template(lang) {
		return [
			'<a class="language" href="#">',
			'<img src="/assets/flags/', lang.icon, '.png" />',
			'<span class="langname">', lang.label, '</span>',
			'</a>'
		].join('');
	}
	
	function selectLanguage(lang) {
		return function (e) {
			e.preventDefault();
			e.stopPropagation();
			Cookies.set('lang', lang.locale_name);
			window.location.reload(true);
		};
	}
	
	(function preload() {
		$.each(Languages, function (k, lang) {
			var img = new Image();
			img.src = '/assets/flags/' + lang.icon + '.png';
		});
	}());
	
	function buildSelector() {
		if (selector) {
			return;
		}
		var root = $('<div id="languages"><h1>Language Selector</h1></div>');
		$('<a href="#" class="button closer">&times;</a>')
			.on('touchstart mousedown', function (e) {
				e.preventDefault();
				e.stopPropagation();
				root.hide();
			})
			.appendTo(root);
		$.each(Languages, function (k, lang) {
			$(template(lang))
				.on('touchstart mousedown', selectLanguage(lang))
				.appendTo(root);
		});
		root
			.on('touchstart mousedown', function (e) {
				e.preventDefault();
				e.stopPropagation();
			})
			.appendTo(document.body);
		selector = root;
	}
	
	function showLanguageSelector(e) {
		e.preventDefault();
		e.stopPropagation();
		buildSelector();
		selector.show();
	}
	
	$('#language-selector').on('touchstart mousedown', showLanguageSelector);
});
