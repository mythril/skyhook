Comet.open('/price', function (price) {
	$('.fiat-amount').text(CurrencyData.symbol + String(price));
});

$(function () {
	MBP.hideUrlBarOnLoad();
	
	function goToAccountPage() {
		window.location.replace('/account');
	}
	
	function bind() {
		$(document.body).on('click', goToAccountPage);
	}
	
	function unbind() {
		$(document.body).off('click', goToAccountPage);
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
	
	function buildSelector() {
		if (selector) {
			return;
		}
		var root = $('<div id="languages"></div>');
		$.each(Languages, function (k, lang) {
			$(template(lang))
				.on('click', selectLanguage(lang))
				.appendTo(root);
		});
		root
			.on('click', function (e) {
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
	
	$('#language-selector').on('click', showLanguageSelector);
});
