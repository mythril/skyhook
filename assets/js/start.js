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
